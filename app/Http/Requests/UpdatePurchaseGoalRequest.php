<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseGoalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'target_amount' => ['sometimes', 'numeric', 'min:0'],
            'amount_per_person' => ['nullable', 'numeric', 'min:0'],

            // validation for product under purchase goal
            'product_name' => ['sometimes', 'string'],
            'product_description' => ['sometimes', 'string'],
            'product_unit_price' => ['sometimes', 'numeric', 'min:0'],
            'product_bulk_price' => ['nullable', 'numeric', 'min:0'],
            'product_quantity' => ['sometimes', 'numeric', 'min:0'],
            'product_image' => ['nullable', 'file', 'max:51200'],

            'group_link' => ['nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
