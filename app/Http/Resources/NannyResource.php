<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NannyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,
            'gender' => $this->gender,
            'user' => new UserResource($this->whenLoaded('user')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'years_experience' => $this->years_experience,
            'working_hours' => $this->working_hours,
            'days_available' => $this->days_available,
            'commitment_type' => $this->commitment_type,
            'hourly_rate' => $this->hourly_rate,
            'fixed_package_description' => $this->fixed_package_description,
            'contact_enabled' => $this->contact_enabled,
            'booking_type' => $this->booking_type,
            'availability_calendar' => $this->availability_calendar,
            'is_verified' => $this->is_verified,
            'video_intro_url' => $this->video_intro_url,
            'resume_url' => $this->resume_url,
            'age_groups'=>$this->age_groups,
            'languages' => LanguageResource::collection($this->whenLoaded('languages')),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'degrees' => DegreeResource::collection($this->whenLoaded('degrees')),
            'translations' => $this->translations,
            'photos' => $this->photos,
        ];
    }
}
