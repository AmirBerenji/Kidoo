<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            return apiResponse(false,"User already exists!",null,400);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return apiResponse(true,"User registered successfully",new UserResource($user),200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return apiResponse(false,"Invalid credentials",401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return apiResponse(true,"Login successfully",[
            'user '=>new UserResource($user),
            'token' => $token],200);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return apiResponse(false,"Unauthorized",null,401);
        }
            return apiResponse(true,"test",new UserResource($user),200);

    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
