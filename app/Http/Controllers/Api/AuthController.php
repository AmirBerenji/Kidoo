<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|min:5',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, "Validation failed", $validator->errors(), 400);
        }

        $user = auth()->user(); // current logged-in user

        if (!$user) {
            return apiResponse(false, "User not found", null, 404);
        }

        $user->name  = $request->name;
        $user->phone = $request->phone;
        $user->save();

        return apiResponse(true, "Profile updated successfully", $user, 200);
    }

    public function updatePhoto(Request $request)
    {
        try {

            $rawInput = $request->getContent();

            $user = Auth::user();
            if (!$user) {
                return apiResponse(false, "User not authenticated", null, 401);
            }

            $allFileFields = [];
            foreach ($request->files->all() as $key => $file) {
                $allFileFields[$key] = $file;
            }

            $possibleFileFields = ['photo', 'image', 'file', 'avatar', 'picture'];
            $foundFile = null;
            $foundFieldName = null;

            foreach ($possibleFileFields as $fieldName) {
                if ($request->hasFile($fieldName)) {
                    $foundFile = $request->file($fieldName);
                    $foundFieldName = $fieldName;
                    break;
                }
            }

            if (!$foundFile) {
                $allFiles = $request->files->all();
                if (!empty($allFiles)) {
                    $firstFileKey = array_keys($allFiles)[0];
                    $foundFile = $allFiles[$firstFileKey];
                    $foundFieldName = $firstFileKey;
                }
            }

            // Enhanced validation with better error messages
            try {
                // Try validation with the found field name first
                if ($foundFile && $foundFieldName) {
                    $validationRules = [$foundFieldName => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'];

                } else {
                    $validationRules = ['photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'];

                }

                $request->validate($validationRules);
                Log::info('Validation passed successfully');
            } catch (\Illuminate\Validation\ValidationException $e) {

                return apiResponse(false, "Validation failed", $e->errors(), 422);
            }

            // Get the photo file (try multiple field names)
            $photoFile = $foundFile ?? $request->file('photo') ?? $request->file('image');

            if (!$photoFile) {

                return apiResponse(false, "No photo file provided", null, 400);
            }
            // Enhanced file validation
            if (!$photoFile->isValid()) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                ];

                $errorCode = $photoFile->getError();
                $errorMessage = $errorMessages[$errorCode] ?? 'Unknown upload error';

                return apiResponse(false, "Invalid file upload: {$errorMessage}", null, 400);
            }

            // Additional file content validation
            try {
                $imageInfo = getimagesize($photoFile->getRealPath());
                if ($imageInfo === false) {
                    Log::error('File is not a valid image');
                    return apiResponse(false, "File is not a valid image", null, 400);
                }

            } catch (\Exception $e) {

                return apiResponse(false, "Invalid image file", null, 400);
            }

            // Delete old photo if exists
            if ($user->photo) {

                try {
                    if (Storage::disk('public')->exists($user->photo)) {
                        $deleted = Storage::disk('public')->delete($user->photo);

                    } else {
                        Log::warning('Old photo file does not exist:', ['path' => $user->photo]);
                    }
                } catch (\Exception $e) {

                    // Continue with upload even if old photo deletion fails
                }
            } else {
                Log::info('No existing photo to delete');
            }

            // Store new photo with enhanced error handling
            try {
                Log::info('Attempting to store new photo');

                if (!Storage::disk('public')->exists('photos')) {
                    Storage::disk('public')->makeDirectory('photos');
                    Log::info('Created photos directory');
                }

                $path = $photoFile->store('photos', 'public');

                if (!$path) {
                    Log::error('Failed to store photo - store() returned false');
                    return apiResponse(false, "Failed to store photo", null, 500);
                }

                // Verify the file was actually stored
                if (!Storage::disk('public')->exists($path)) {
                    Log::error('Photo was not found after storage:', ['path' => $path]);
                    return apiResponse(false, "Photo storage verification failed", null, 500);
                }

            } catch (\Exception $e) {
                return apiResponse(false, "Failed to store photo", null, 500);
            }

            // Update user record
            try {
                $oldPhotoPath = $user->photo;
                $user->photo = $path;
                $saved = $user->save();

                if (!$saved) {
                    Log::error('Failed to save user record');
                    // Try to cleanup the uploaded file since user update failed
                    try {
                        Storage::disk('public')->delete($path);
                        Log::info('Cleaned up uploaded file after user save failure');
                    } catch (\Exception $cleanupException) {
                        Log::error('Failed to cleanup uploaded file:', [
                            'error' => $cleanupException->getMessage()
                        ]);
                    }
                    return apiResponse(false, "Failed to update user record", null, 500);
                }

            } catch (\Exception $e) {

                try {
                    Storage::disk('public')->delete($path);
                    Log::info('Cleaned up uploaded file after user update exception');
                } catch (\Exception $cleanupException) {
                    Log::error('Failed to cleanup uploaded file:', [
                        'error' => $cleanupException->getMessage()
                    ]);
                }

                return apiResponse(false, "Failed to update user record", null, 500);
            }

            // Return user with photo URL for frontend
            $userData = $user->toArray();
            $userData['photo_url'] = $user->photo ? Storage::disk('public')->url($user->photo) : null;

            return apiResponse(true, "Photo updated successfully", $userData, 200);

        } catch (\Exception $e) {

            return apiResponse(false, "An unexpected error occurred", null, 500);
        }
    }

}
