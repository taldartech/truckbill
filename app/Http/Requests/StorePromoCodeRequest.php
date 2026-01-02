<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromoCodeRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:255|unique:promo_codes,code',
            'discount_type' => 'required|in:percentage,absolute',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'The promo code is required.',
            'code.unique' => 'This promo code already exists.',
            'discount_type.required' => 'The discount type is required.',
            'discount_type.in' => 'The discount type must be either percentage or absolute.',
            'discount_value.required' => 'The discount value is required.',
            'discount_value.numeric' => 'The discount value must be a number.',
            'discount_value.min' => 'The discount value must be at least 0.',
            'start_date.required' => 'The start date is required.',
            'start_date.date' => 'The start date must be a valid date.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'max_uses.integer' => 'The max uses must be an integer.',
            'max_uses.min' => 'The max uses must be at least 1.',
            'is_active.boolean' => 'The active status must be true or false.',
        ];
    }
}
