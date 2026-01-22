<?php

namespace App\Http\Requests;

class AssignTaskRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_user_id' => ['required', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_user_id.required' => 'The assigned_user_id parameter is required.',
            'assigned_user_id.exists' => 'The selected assigned user does not exist.',
        ];
    }
}
