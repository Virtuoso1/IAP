<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\User;

class UpdateWarningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For now, allow any authenticated user
        // TODO: Implement proper role-based authorization
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(['formal', 'informal', 'final'])],
            'level' => 'nullable|integer|min:1|max:5',
            'reason' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:2000',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Warning type must be one of: formal, informal, final',
            'level.min' => 'Warning level must be at least 1',
            'level.max' => 'Warning level must not exceed 5',
            'expires_at.after' => 'Expiration date must be in the future',
        ];
    }

    /**
     * Get validated data with custom processing.
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        
        // Add moderator ID if authenticated
        if (Auth::check()) {
            $data['moderator_id'] = Auth::id();
        }
        
        // Check if user can escalate warning level
        if (isset($data['level']) && $data['level'] > 3) {
            // For now, allow all users to escalate
            // TODO: Implement proper role-based restrictions
        }
        
        return $data;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'updated_at' => now(),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('expires_at') && $this->has('is_active')) {
                if ($this->is_active && $this->expires_at && $this->expires_at->isPast()) {
                    $validator->errors()->add('expires_at', 'Cannot set an active warning with a past expiration date.');
                }
            }
        });
    }
}