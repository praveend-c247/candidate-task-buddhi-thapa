<?php

namespace App\Http\Requests;

class StoreTaskRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'status' => ['sometimes', 'in:pending,in_progress,completed'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'The project_id parameter is required.',
            'project_id.exists' => 'The selected project does not exist.',
            'title.required' => 'The title parameter is required.',
            'title.string' => 'The title must be a string.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'description.string' => 'The description must be a string.',
            'priority.required' => 'The priority parameter is required.',
            'priority.in' => 'The priority must be one of: low, medium, high.',
            'due_date.date' => 'The due_date must be a valid date.',
            'due_date.after_or_equal' => 'The due_date must be today or a future date.',
            'status.in' => 'The status must be one of: pending, in_progress, completed.',
            'assigned_user_id.exists' => 'The selected assigned user does not exist.',
            'images.array' => 'The images must be an array.',
            'images.*.image' => 'Each image must be a valid image file.',
            'images.*.mimes' => 'Each image must be a file of type: jpeg, png, jpg, gif.',
            'images.*.max' => 'Each image may not be greater than 5MB.',
        ];
    }
}
