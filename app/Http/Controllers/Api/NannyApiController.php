<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NannyResource;
use App\Models\Nanny;
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
            'gender' => 'required|string',
            'location_id' => 'nullable|exists:locations,id',
            'years_experience' => 'required|integer',
            'working_hours' => 'nullable|string',
            'days_available' => 'nullable|string',
            'commitment_type' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric',
            'fixed_package_description' => 'nullable|string',
            'contact_enabled' => 'boolean',
            'booking_type' => 'nullable|string',
            'availability_calendar' => 'nullable|array',
            'is_verified' => 'boolean',
            'video_intro_url' => 'nullable|string',
            'resume_url' => 'nullable|string',

            'languages' => 'nullable|array',
            'services' => 'nullable|array',
            'degrees' => 'nullable|array',
            'translations' => 'nullable|array',
            'photos' => 'nullable|array',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request) {
            $nanny = Nanny::create($request->only([
                'gender', 'location_id', 'years_experience', 'working_hours',
                'days_available', 'commitment_type', 'hourly_rate', 'fixed_package_description',
                'contact_enabled', 'booking_type', 'availability_calendar',
                'is_verified', 'video_intro_url', 'resume_url',
            ]));

            //$nanny->languages()->sync($request->languages ?? []);
            //$nanny->services()->sync($request->services ?? []);
            //$nanny->degrees()->sync($request->degrees ?? []);

            //if ($request->has('translations')) {
            //    foreach ($request->translations as $tr) {
            //        $nanny->translations()->create($tr);
            //    }
            //}

            //if ($request->has('photos')) {
            //    foreach ($request->photos as $photo) {
            //        $nanny->photos()->create($photo);
            //    }
            //}

            //return new NannyResource(
            //    $nanny->load([
            //        'location', 'languages', 'services.translations',
            //        'degrees.translations', 'translations', 'photos'
            //    ])
            //);
        });
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
