<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'city' => $this->city,
            'district' => $this->district,
            'postal_code' => $this->postal_code
        ];
    }
}
