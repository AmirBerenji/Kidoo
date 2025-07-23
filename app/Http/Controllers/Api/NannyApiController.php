<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NannyResource;
use App\Models\Nanny;
use App\Models\NannyTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NannyApiController extends Controller
{
    public function index()
    {
        return NannyResource::collection(
            Nanny::with([
                'location',
                'languages',
                'services.translations',
                'degrees.translations',
                'translations',
                'photos'
            ])->get()
        );
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

            'nannytranslation' => 'nullable|array',
            'nannytranslation.*.language_code' => 'required|string',
            'nannytranslation.*.full_name' => 'required|string',
            'nannytranslation.*.specialization' => 'nullable|string',
            'nannytranslation.*.age_groups' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'profile_photo_index' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $nanny = DB::transaction(function () use ($request) {
                // Normalize gender and commitment_type input values
                $normalized = $request->only([
                    'gender', 'location_id', 'years_experience', 'working_hours',
                    'days_available', 'commitment_type', 'hourly_rate',
                    'fixed_package_description', 'contact_enabled',
                    'booking_type', 'availability_calendar',
                    'is_verified', 'video_intro_url', 'resume_url',
                ]);


                $nanny = Nanny::create($normalized);

                // Handle translations
                if ($request->has('nannytranslation')) {
                    $nanny->translations()->createMany($request->nannytranslation);
                }



                return $nanny->load([
                    'location',
                    'translations',
                    'photos', // if you plan to support later
                ]);
            });

            return new NannyResource($nanny);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error occurred while creating nanny.',
                'error' => $e->getMessage()
            ], 500);
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
}
