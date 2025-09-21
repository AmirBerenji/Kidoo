<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name'  => $this->name,
            'email' => $this->email,
            'roles' => $this->roles->pluck('name'), // Returns array of role names
            'phone' => $this->phone,
            'photo' => $this->photo,
            'photoUrl' => $this->photo ? Storage::disk('public')->url($this->photo) : null
        ];

    }
}
