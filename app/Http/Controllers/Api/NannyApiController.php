<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NannyResource;
use App\Models\Nanny;
use App\Models\NannyTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NannyApiController extends Controller
{

    public function index(Request $request)
    {
        try {
            // Start with base query
            $query = Nanny::query();

            // Essential relationships - load only what's needed
            $query->with([
                'location:id,name,state,country',
                'translations' => function($q) {
                    $q->select('id', 'nanny_id', 'language_code', 'full_name', 'specialization', 'age_groups');
                },
                'photos' => function($q) {
                    $q->select('id', 'nanny_id', 'photo_url', 'is_profile_photo', 'order')
                        ->orderBy('order');
                },
                'languages:id,name,code'
            ]);

            // Performance optimization - select only needed columns
            $query->select([
                'id', 'user_id', 'gender', 'location_id', 'years_experience',
                'working_hours', 'days_available', 'commitment_type', 'hourly_rate',
                'contact_enabled', 'booking_type', 'is_verified', 'created_at', 'updated_at'
            ]);

            // Core filters
            $this->applyFilters($query, $request);

            // Sorting
            $this->applySorting($query, $request);

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $nannies = $query->paginate($perPage);

            return apiResponse(true,"",[
                'nannies' => NannyResource::collection($nannies->items()),
                'pagination' => [
                    'current_page' => $nannies->currentPage(),
                    'last_page' => $nannies->lastPage(),
                    'per_page' => $nannies->perPage(),
                    'total' => $nannies->total(),
                    'has_more_pages' => $nannies->hasMorePages(),
                ],
                'filters_applied' => $this->getAppliedFilters($request),
                'total_available' => $nannies->total()
            ],200);

        } catch (\Exception $e) {
            Log::error('Nannies Index Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->except(['photos'])
            ]);

            return apiResponse(false,'Unable to retrieve nannies. Please try again.',null,500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gender' => 'required|in:Male,Female,Other',
            'location_id' => 'nullable|exists:locations,id',
            'years_experience' => 'required|integer|min:0',
            'working_hours' => 'nullable|string',
            'days_available' => 'nullable|string',
            'commitment_type' => 'nullable|in:Short-term,Long-term,short_term,long_term,temporary',
            'hourly_rate' => 'nullable|numeric|min:0',
            'fixed_package_description' => 'nullable|string',
            'contact_enabled' => 'required|boolean',
            'booking_type' => 'nullable|in:direct,Interview,on_request',
            'availability_calendar' => 'nullable|array',
            'availability_calendar.*' => 'date',
            'is_verified' => 'required|boolean',
            'video_intro_url' => 'nullable|url',
            'resume_url' => 'nullable|url',
            'age_groups' => 'nullable|string',

            'nannytranslation' => 'nullable|array',
            'nannytranslation.*.language_code' => 'required|exists:languages,id',
            'nannytranslation.*.full_name' => 'required|string',
            'nannytranslation.*.specialization' => 'nullable|string',


            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'profile_photo_index' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $nanny = DB::transaction(function () use ($request) {
                // Normalize values
                $normalized = $request->only([
                    'gender', 'location_id', 'years_experience', 'working_hours',
                    'days_available', 'hourly_rate', 'fixed_package_description',
                    'contact_enabled', 'booking_type', 'availability_calendar',
                    'is_verified', 'video_intro_url', 'resume_url','age_groups',
                ]);

                // Normalize commitment_type to consistent format
                if ($request->has('commitment_type')) {
                    $normalized['commitment_type'] = ucfirst(strtolower(str_replace('_', '-', $request->commitment_type)));
                }

                // Add the authenticated user ID
                $normalized['user_id'] = Auth::id();

                // Convert empty URLs to null
                if (empty($normalized['video_intro_url'])) {
                    $normalized['video_intro_url'] = null;
                }
                if (empty($normalized['resume_url'])) {
                    $normalized['resume_url'] = null;
                }

                $nanny = Nanny::create($normalized);

                // Handle translations and languages
                if ($request->has('nannytranslation')) {
                    // Create translations
                    $nanny->translations()->createMany($request->nannytranslation);

                    // Attach languages (using language IDs)
                    $languageIds = collect($request->nannytranslation)->pluck('language_code')->unique();
                    $nanny->languages()->attach($languageIds);
                }

                // Handle photo uploads
                if ($request->hasFile('photos')) {
                    $profilePhotoIndex = $request->input('profile_photo_index', 0);

                    foreach ($request->file('photos') as $index => $photo) {
                        $filename = time() . '_' . $index . '.' . $photo->getClientOriginalExtension();
                        $path = $photo->storeAs('nanny_photos', $filename, 'public');

                        $nanny->photos()->create([
                            'photo_url' => Storage::url($path),
                            'is_profile_photo' => $index == $profilePhotoIndex,
                            'order' => $index,
                        ]);
                    }
                }

                return $nanny->load([
                    'location',
                    'translations',
                    'languages',
                    'photos',
                ]);
            });

            return apiResponse(true, "Your information saved correctly", new NannyResource($nanny), 200);

        } catch (\Exception $e) {
            Log::error('Error creating nanny: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['photos']) // Exclude files from logs
            ]);

            return apiResponse(false, 'Error occurred while creating nanny.', null, 500);
        }
    }

    public function show(Nanny $nanny)
    {
        return new NannyResource(
            $nanny->load([
                'location', 'languages', 'services.translations',
                'degrees.translations', 'translations', 'photos'
            ])
        );
    }

    public function update(Request $request, Nanny $nanny)
    {
        $nanny->update($request->only([
            'gender', 'location_id', 'years_experience', 'working_hours',
            'days_available', 'commitment_type', 'hourly_rate', 'fixed_package_description',
            'contact_enabled', 'booking_type', 'availability_calendar',
            'is_verified', 'video_intro_url', 'resume_url',
        ]));

        if ($request->has('languages')) {
            $nanny->languages()->sync($request->languages);
        }

        if ($request->has('services')) {
            $nanny->services()->sync($request->services);
        }

        if ($request->has('degrees')) {
            $nanny->degrees()->sync($request->degrees);
        }

        if ($request->has('translations')) {
            $nanny->translations()->delete();
            foreach ($request->translations as $tr) {
                $nanny->translations()->create($tr);
            }
        }

        if ($request->has('photos')) {
            $nanny->photos()->delete();
            foreach ($request->photos as $photo) {
                $nanny->photos()->create($photo);
            }
        }

        return new NannyResource(
            $nanny->load([
                'location', 'languages', 'services.translations',
                'degrees.translations', 'translations', 'photos'
            ])
        );
    }

    public function destroy(Nanny $nanny)
    {
        $nanny->delete();
        return response()->noContent();
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters($query, Request $request)
    {
        // Only verified nannies by default
        if ($request->get('include_unverified') !== 'true') {
            $query->where('is_verified', true);
        }

        // Location filter
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Gender filter
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Experience filters
        if ($request->filled('min_experience')) {
            $query->where('years_experience', '>=', $request->min_experience);
        }
        if ($request->filled('max_experience')) {
            $query->where('years_experience', '<=', $request->max_experience);
        }

        // Rate filters
        if ($request->filled('min_rate')) {
            $query->where('hourly_rate', '>=', $request->min_rate);
        }
        if ($request->filled('max_rate')) {
            $query->where('hourly_rate', '<=', $request->max_rate);
        }

        // Commitment type
        if ($request->filled('commitment_type')) {
            $query->where('commitment_type', $request->commitment_type);
        }

        // Booking type
        if ($request->filled('booking_type')) {
            $query->where('booking_type', $request->booking_type);
        }

        // Only contactable nannies
        if ($request->get('contactable_only') === 'true') {
            $query->where('contact_enabled', true);
        }

        // Available on specific days
        if ($request->filled('available_days')) {
            $days = explode(',', $request->available_days);
            foreach ($days as $day) {
                $query->where('days_available', 'LIKE', '%' . trim($day) . '%');
            }
        }

        // Search in name/specialization
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('translations', function($q) use ($searchTerm) {
                $q->where(function($subQuery) use ($searchTerm) {
                    $subQuery->where('full_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('specialization', 'LIKE', "%{$searchTerm}%");
                });
            });
        }

        // Language filter
        if ($request->filled('languages')) {
            $languageIds = explode(',', $request->languages);
            $query->whereHas('languages', function($q) use ($languageIds) {
                $q->whereIn('languages.id', $languageIds);
            });
        }
    }

    /**
     * Apply sorting to the query
     */
    private function applySorting($query, Request $request)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSortFields = [
            'created_at', 'updated_at', 'years_experience',
            'hourly_rate', 'gender'
        ];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        // Always add a secondary sort for consistency
        if ($sortBy !== 'created_at') {
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Get applied filters for response
     */
    private function getAppliedFilters(Request $request)
    {
        return array_filter([
            'location_id' => $request->location_id,
            'gender' => $request->gender,
            'min_experience' => $request->min_experience,
            'max_experience' => $request->max_experience,
            'min_rate' => $request->min_rate,
            'max_rate' => $request->max_rate,
            'commitment_type' => $request->commitment_type,
            'booking_type' => $request->booking_type,
            'search' => $request->search,
            'languages' => $request->languages,
            'available_days' => $request->available_days,
            'contactable_only' => $request->get('contactable_only') === 'true',
            'include_unverified' => $request->get('include_unverified') === 'true'
        ]);
    }

}
