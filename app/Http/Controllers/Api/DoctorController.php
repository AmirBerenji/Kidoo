<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    /**
     * Display a listing of doctors
     */
    public function index(Request $request)
    {
        $languageCode = $request->header('Accept-Language', 'en');
        app()->setLocale($languageCode);

        $language = Language::where('code', $languageCode)->first();

        $doctors = Doctor::with([
            'translations' => function($query) use ($language) {
                if ($language) {
                    $query->where('language_id', $language->id);
                }
            },
            'translations.language',
            'location',
            'user:id,name,email,photo'
        ])
            ->when($request->status, function($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->specialization, function($query) use ($request) {
                $query->where('specialization', $request->specialization);
            })
            ->when($request->location_id, function($query) use ($request) {
                $query->where('location_id', $request->location_id);
            })
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $doctors->map(function($doctor) use ($languageCode) {
                return $this->formatDoctor($doctor, $languageCode);
            }),
            'pagination' => [
                'total' => $doctors->total(),
                'per_page' => $doctors->perPage(),
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created doctor
     */
    public function store(Request $request)
    {
        // Get authenticated user ID
        $userId = auth()->id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login first.'
            ], 401);
        }

        // Check if user already has a doctor profile
        $existingDoctor = Doctor::where('user_id', $userId)->first();
        if ($existingDoctor) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a doctor profile. Please use the update endpoint.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:doctors,email',
            'phone' => 'nullable|string',
            'specialization' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'license_number' => 'nullable|string|unique:doctors,license_number',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'location_id' => 'nullable|exists:locations,id',
            'status' => 'nullable|in:active,inactive,suspended',
            'translations' => 'required|array|min:1',
            'translations.*.language_id' => 'required|exists:languages,id',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.bio' => 'nullable|string',
            'translations.*.education' => 'nullable|string',
            'translations.*.address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('translations', 'image');
        $data['user_id'] = $userId; // Set user_id from authenticated user

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('doctors', 'public');
        }

        $doctor = Doctor::create($data);

        // Create translations
        foreach ($request->translations as $translation) {
            $doctor->translations()->create($translation);
        }

        return response()->json([
            'success' => true,
            'message' => 'Doctor created successfully',
            'data' => $this->formatDoctor($doctor->load('translations.language', 'location', 'user'))
        ], 201);
    }


    /**
     * Display the specified doctor
     */
    public function show(Request $request, $id)
    {
        $languageCode = $request->header('Accept-Language', 'en');
        app()->setLocale($languageCode);

        $doctor = Doctor::with('translations.language', 'location', 'user')->find($id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatDoctor($doctor, $languageCode)
        ]);
    }

    /**
     * Update the specified doctor
     */
    public function update(Request $request, $id)
    {
        $doctor = Doctor::find($id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id|unique:doctors,user_id,' . $id,
            'email' => 'sometimes|email|unique:doctors,email,' . $id,
            'phone' => 'nullable|string',
            'specialization' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'license_number' => 'nullable|string|unique:doctors,license_number,' . $id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'location_id' => 'nullable|exists:locations,id',
            'status' => 'nullable|in:active,inactive,suspended',
            'translations' => 'sometimes|array',
            'translations.*.language_id' => 'required_with:translations|exists:languages,id',
            'translations.*.name' => 'required_with:translations|string|max:255',
            'translations.*.bio' => 'nullable|string',
            'translations.*.education' => 'nullable|string',
            'translations.*.address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('translations', 'image');

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($doctor->image) {
                Storage::disk('public')->delete($doctor->image);
            }
            $data['image'] = $request->file('image')->store('doctors', 'public');
        }

        $doctor->update($data);

        // Update translations
        if ($request->has('translations')) {
            foreach ($request->translations as $translation) {
                $doctor->translations()->updateOrCreate(
                    ['language_id' => $translation['language_id']],
                    $translation
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Doctor updated successfully',
            'data' => $this->formatDoctor($doctor->load('translations.language', 'location', 'user'))
        ]);
    }

    /**
     * Remove the specified doctor
     */
    public function destroy($id)
    {
        $doctor = Doctor::find($id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found'
            ], 404);
        }

        // Delete image
        if ($doctor->image) {
            Storage::disk('public')->delete($doctor->image);
        }

        $doctor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Doctor deleted successfully'
        ]);
    }

    public function showByUserId()
    {
        try{
            $userId = Auth::id();
            $languageCode = null;
            $doctor = Doctor::with('translations.language', 'location', 'user')->where('user_id', $userId)->first();

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatDoctor($doctor, $languageCode)
            ]);
        }
        catch(\Exception $e)
        {
            return apiResponse(false,"",null,500);
        }
    }

    /**
     * Format doctor data for response
     */
    private function formatDoctor($doctor, $languageCode = null)
    {
        $languageCode = $languageCode ?: app()->getLocale();

        $translation = $doctor->translations->first(function($trans) use ($languageCode) {
            return $trans->language->code === $languageCode;
        });

        return [
            'id' => $doctor->id,
            'user_id' => $doctor->user_id,
            'user' => $doctor->user ? [
                'id' => $doctor->user->id,
                'name' => $doctor->user->name,
                'email' => $doctor->user->email,
            ] : null,
            'email' => $doctor->email,
            'phone' => $doctor->phone,
            'specialization' => $doctor->specialization,
            'experience_years' => $doctor->experience_years,
            'license_number' => $doctor->license_number,
            'image' => $doctor->user->photo ?? null,
            'status' => $doctor->status,
            'location' => $doctor->location ? [
                'id' => $doctor->location->id,
                'city' => $doctor->location->city,
                'district' => $doctor->location->district,
                'postal_code' => $doctor->location->postal_code,
            ] : null,
            'name' => $translation?->name ?? '',
            'bio' => $translation?->bio ?? '',
            'education' => $translation?->education ?? '',
            'address' => $translation?->address ?? '',
            'translations' => $doctor->translations->map(function($trans) {
                return [
                    'id' => $trans->id,
                    'language_id' => $trans->language_id,
                    'language_code' => $trans->language->code,
                    'language_name' => $trans->language->name,
                    'name' => $trans->name,
                    'bio' => $trans->bio,
                    'education' => $trans->education,
                    'address' => $trans->address,
                ];
            }),
            'created_at' => $doctor->created_at,
            'updated_at' => $doctor->updated_at,
        ];
    }
}
