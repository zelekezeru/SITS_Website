<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramUpdateRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:50',
            'email' => 'sometimes|required|email|unique:programs,email,' . $this->program,
            'phone' => 'sometimes|required|string|max:15',
            'title' => 'sometimes|required|string|max:100',
            'message' => 'sometimes|required|string',
        ];
    }
}
