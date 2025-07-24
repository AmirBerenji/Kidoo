<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NannyPhotoApicontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->hasFile('photos')) {

            //foreach ($request->file('photos') as $index => $file) {
            //    $path = $file->store('nanny_photos', 'public'); // stores in storage/app/public/nanny_photos
            //    $nanny->photos()->create([
            //        'photo_url' => $path,
            //        'is_profile' => $index == $request->input('profile_photo_index', -1), // mark profile if index matches
            //    ]);
           // }
        }
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
