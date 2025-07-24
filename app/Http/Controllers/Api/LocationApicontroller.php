<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationApicontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = LocationResource::collection(Location::all());
        return apiResponse(true,"success",$locations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city' => 'required|string|max:255',
            'district' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location = Location::create([
            'city' => $request->city,
            'district' => $request->district,
            'postal_code' => $request->postal_code,
        ]);

        return response()->json([
            'message' => 'Location created successfully.',
            'location' => $location
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
