<?php

namespace App\Http\Requests;

class LoginRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The email parameter is required.',
            'email.email' => 'The email must be a valid email address.',
            'password.required' => 'The password parameter is required.',
            'password.string' => 'The password must be a string.',
        ];
    }
}
