<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChildToken;
use Illuminate\Http\Request;

class ChildController extends Controller
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
        //
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

    public function checkregister(string $id)
    {
        $token = ChildToken::where('uuid',$id)
            ->first();

        if ($token==null)
        {
            return apiResponse(false,'Token not found',null,500);
        }

        if($token->isused == null || $token->isused == false){
            return apiResponse(true,'Is not register',false,200);
        }else{
            return apiResponse(true,'Is register',true,200);
        }
    }
}
