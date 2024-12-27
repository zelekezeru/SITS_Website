<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventUpdateRequest extends FormRequest
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
            'title' => 'required|string|max:50', // Event title, required, string, max length of 50 characters
            'description' => 'nullable|string|max:255', // Optional description, string, max length of 255 characters
            'date' => 'required|date|after_or_equal:today', // Event date, required, must be a valid date and not in the past
            'start_time' => 'required|date_format:H:i', // Start time, required, must match the HH:mm format
            'end_time' => 'required|date_format:H:i|after:start_time', // End time, required, must match the HH:mm format and be after start_time
            'location' => 'required|string|max:50', // Event location, required, string, max length of 50 characters
            'remark' => 'nullable|string|max:50', // Optional remark, string, max length of 50 characters
        ];
    }
}
