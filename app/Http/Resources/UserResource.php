<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'profile_pic' => $this->profile_pic,
            'purchase_goals' => PurchaseGoalResource::collection($this->whenLoaded('purchaseGoals')),
        ];

        if ($this->pivot) {
            $data['joined_at'] = $this->pivot->joined_at ?? null;
            $data['contributed_amount'] = $this->pivot->contributed_amount ?? null;
            $data['status'] = $this->pivot->status;
        }

        return $data;
    }
}
