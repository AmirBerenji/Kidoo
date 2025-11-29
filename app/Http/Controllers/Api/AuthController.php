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


            // 🔍 CRITICAL DEBUG - Add this RIGHT after try
            Log::info('=== MIME Type Debug ===');

            $allFiles = $request->allFiles();
            Log::info('All Files:', $allFiles);

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                Log::info('Photo file details:', [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'client_mime' => $file->getClientMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                    'size' => $file->getSize(),
                    'is_valid' => $file->isValid(),
                    'error' => $file->getError(),
                    'real_path' => $file->getRealPath(),
                ]);
            }
            Log::info('Request Content-Type', ['content_type' => $request->header('Content-Type')]);
            Log::info('====================');

            $user = Auth::user();
            if (!$user) {
                return apiResponse(false, "User not authenticated", null, 401);
            }
            // First, find the file
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

            // If no file found in common fields, check all files
            if (!$foundFile) {
                $allFiles = $request->files->all();
                if (!empty($allFiles)) {
                    $firstFileKey = array_keys($allFiles)[0];
                    $foundFile = $allFiles[$firstFileKey];
                    $foundFieldName = $firstFileKey;
                }
            }

            // Check if file exists before validation
            if (!$foundFile) {
                return apiResponse(false, "No photo file provided", null, 400);
            }

            // Validate the file upload itself first
            if (!$foundFile->isValid()) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                ];

                $errorCode = $foundFile->getError();
                $errorMessage = $errorMessages[$errorCode] ?? 'Unknown upload error';
                return apiResponse(false, "Invalid file upload: {$errorMessage}", null, 400);
            }

            // Validate file properties
            $mimeType = $foundFile->getMimeType();
            $allowedMimes = [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',  // ✅ Added
                'image/avif'   // ✅ Added
            ];

            if (!in_array($mimeType, $allowedMimes)) {
                return apiResponse(false, "Invalid MIME type: {$mimeType}. File must be an image.", null, 400);
            }

// Update extension validation
            $extension = strtolower($foundFile->getClientOriginalExtension());
            $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif', 'webp', 'avif']; // ✅ Added webp, avif

            if (!in_array($extension, $allowedExtensions)) {
                return apiResponse(false, "Invalid file type. Allowed: jpeg, jpg, png, gif, webp, avif", null, 400);
            }

            // Validate actual image content
            try {
                $imageInfo = @getimagesize($foundFile->getRealPath());
                if ($imageInfo === false) {
                    return apiResponse(false, "File is not a valid image", null, 400);
                }

                // Additional check: verify image dimensions are reasonable
                if ($imageInfo[0] < 1 || $imageInfo[1] < 1) {
                    return apiResponse(false, "Invalid image dimensions", null, 400);
                }
            } catch (\Exception $e) {
                return apiResponse(false, "Invalid image file: " . $e->getMessage(), null, 400);
            }

            // Delete old photo if exists
            if ($user->photo) {
                try {
                    if (Storage::disk('public')->exists($user->photo)) {
                        Storage::disk('public')->delete($user->photo);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old photo: ' . $e->getMessage());
                }
            }

            // Store new photo
            try {
                if (!Storage::disk('public')->exists('photos')) {
                    Storage::disk('public')->makeDirectory('photos');
                }

                $path = $foundFile->store('photos', 'public');

                if (!$path) {
                    return apiResponse(false, "Failed to store photo", null, 500);
                }

                // Verify storage
                if (!Storage::disk('public')->exists($path)) {
                    return apiResponse(false, "Photo storage verification failed", null, 500);
                }

            } catch (\Exception $e) {
                Log::error('Photo storage error: ' . $e->getMessage());
                return apiResponse(false, "Failed to store photo", null, 500);
            }

            // Update user record
            try {
                $oldPhotoPath = $user->photo;
                $user->photo = $path;
                $saved = $user->save();

                if (!$saved) {
                    // Cleanup uploaded file
                    try {
                        Storage::disk('public')->delete($path);
                    } catch (\Exception $cleanupException) {
                        Log::error('Cleanup failed: ' . $cleanupException->getMessage());
                    }
                    return apiResponse(false, "Failed to update user record", null, 500);
                }

            } catch (\Exception $e) {
                // Cleanup uploaded file
                try {
                    Storage::disk('public')->delete($path);
                } catch (\Exception $cleanupException) {
                    Log::error('Cleanup failed: ' . $cleanupException->getMessage());
                }

                Log::error('User update error: ' . $e->getMessage());
                return apiResponse(false, "Failed to update user record", null, 500);
            }

            // Return user with photo URL
            $userData = $user->toArray();
            $userData['photo_url'] = $user->photo ? Storage::disk('public')->url($user->photo) : null;

            return apiResponse(true, "Photo updated successfully", $userData, 200);

        } catch (\Exception $e) {
            Log::error('Unexpected error in updatePhoto: ' . $e->getMessage());
            return apiResponse(false, "An unexpected error occurred", null, 500);
        }
    }




}
