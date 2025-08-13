<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'password' => 'required|min:6|confirmed',
            'phone'    => 'nullable|min:5',
            'role'     => 'required|string|in:parent,doctor,nurse',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, "Validation failed", $validator->errors(), 400);
        }

        // Check if user already exists
        if (User::where('email', $request->email)->exists()) {
            return apiResponse(false, "User already exists!", null, 400);
        }

        // Create user
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
        ]);

        // Assign role
        $role = Role::where('name', $request->role)->first();
        if (!$role) {
            return apiResponse(false, "User role is not correct", null, 400);
        }

        $user->roles()->attach($role->id);
        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return apiResponse(true, "User registered successfully", [
            'user'  => new UserResource($user->load('roles')), // include roles
            'token' => $token
        ], 200);
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

            return apiResponse(true,"User with Role",new UserResource($user),200);

    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
