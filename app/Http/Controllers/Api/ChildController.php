<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\ChildToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChildController extends Controller
{

    /**
     * List all children of authenticated user
     */
    public function index(Request $request)
    {
        $children = Child::where('user_id', auth()->id())
            ->latest()
            ->get();

        return apiResponse(true, "Children list", $children, 200);
    }
    /**
     * Store a new child
     */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'address'    => 'nullable|string|max:255',
            'birthday'   => 'nullable|date',
            'blood_type' => 'nullable|string|max:10',
            'gender'     => 'nullable|string|max:10',
            'uuid'       => 'string|max:255',
            'image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $path = $file->storeAs('children', $filename, 'public');
            $validated['image'] = $path;
        }

        $validated['user_id'] = auth()->id();

        // Create the child record
        $child = Child::create($validated);

        // If uuid is provided, update the corresponding ChildToken
        if (!empty($validated['uuid'])) {
            $token = ChildToken::where('uuid', $validated['uuid'])->first();

            if ($token) {
                if($token->isused == true)
                {
                    return apiResponse(false,'This tag is not valid for add',null,500);
                }else {
                    // Example update fields — modify based on your real schema
                    $token->update([
                        'isused' => true,
                        'useddate' => now(),
                    ]);
                }
            }else{
                return apiResponse(false,'Your tag id is not fount. ' ,null,500);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'        => $child->id,
                'name'      => $child->name,
                'last_name' => $child->last_name,
                'image_url' => $child->image
                    ? Storage::disk('public')->url($child->image)
                    : null,
                'gender'    => $child->gender,
            ]
        ], 201);
    }

    /**
     * Show a single child (only if belongs to user)
     */
    public function show(Request $request, $id)
    {
        $child = Child::find($id);
        if (!$child) {
            return apiResponse(false, "Child is not available", null, 404);
        }
         $this->authorizeChild($request, $child);
        return apiResponse(true, "Child", $child, 200);
    }

    /**
     * Update child
     */
    public function update(Request $request, Child $child)
    {
        $this->authorizeChild($request, $child);

        $validated = $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'last_name'  => 'sometimes|required|string|max:255',
            'image'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // ← file, not string
            'address'    => 'nullable|string',
            'gender'     => 'nullable|in:Male,Female',  // ← match your frontend values (capital M/F)
            'birthday'   => 'nullable|date',
            'blood_type' => 'nullable|string|max:10',
        ]);

        // Handle image upload separately
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($child->image) {
                Storage::disk('public')->delete($child->image);
            }
            $validated['image'] = $request->file('image')->store('children', 'public');
        } else {
            // Don't overwrite existing image if no new one is sent
            unset($validated['image']);
        }

        $child->update($validated);

        return apiResponse(
            true,
            'Child updated successfully',
            $child->fresh(),
            200
        );
    }
    /**
     * Delete child
     */
    public function destroy(Request $request, Child $child)
    {
        $this->authorizeChild($request, $child);

        $child->delete();

        return response()->json([
            'message' => 'Child deleted successfully'
        ]);
    }

    /**
     * Ensure child belongs to authenticated user
     */
    private function authorizeChild(Request $request, Child $child): void
    {
        if ($child->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access');
        }
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

    public function getchildbytoken(string $childtoken)
    {
        $child = Child::with('user')->where('uuid',$childtoken)->first();

        if ($child==null)
        {
            return apiResponse(false,'Token not found',null,500);
        }else
        {
            return apiResponse(true,'',$child,200);
        }
    }

    public function getchildbychildid(int $childid)
    {
        $child = Child::with('user')->where('id',$childid)->first();

        if ($child==null)
        {
            return apiResponse(false,'Token not found',null,500);
        }else
        {
            return apiResponse(true,'',$child,200);
        }
    }
}
