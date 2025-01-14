<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseGoalResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'product_id' => $this->product_id,
            'target_amount' => $this->target_amount,
            'collected_amount' => $this->collected_amount,
            'status' => $this->status,
            'group_link' => $this->group_link,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
