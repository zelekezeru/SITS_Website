<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseUpdateRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:50',
            'description' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'credit_hours' => 'required|numeric',
            'amount_paid' => 'required|numeric',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
