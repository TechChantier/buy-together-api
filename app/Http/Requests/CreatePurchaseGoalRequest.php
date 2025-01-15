<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePurchaseGoalRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'target_amount' => ['required', 'numeric', 'min:0'],
            'amount_per_person' => ['nullable', 'numeric', 'min:0'],

            // validation for product under purchase goal
            'product_name' => ['required', 'string'],
            'product_description' => ['required', 'string'],
            'product_unit_price' => ['required', 'numeric', 'min:0'],
            'product_bulk_price' => ['nullable', 'numeric', 'min:0'],
            'product_quantity' => ['required', 'numeric', 'min:0'],
            'product_image' => ['nullable', 'file', 'max:51200'],

            'group_link' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
