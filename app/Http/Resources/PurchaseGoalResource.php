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
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'target_amount' => $this->target_amount,
            'amount_per_person' => $this->amount_per_person,
            'status' => $this->status ?? 'open',
            'group_link' => $this->group_link,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // if (UserInPurchaseGoalResource::collection($this->whenLoaded('participants')) != null) {
        //     logger(UserInPurchaseGoalResource::collection($this->whenLoaded('participants')));
        //     array_merge($data, ['number_of_participants' => count(UserInPurchaseGoalResource::collection($this->whenLoaded('participants')))]);
        // }

        return $data;
    }
}
