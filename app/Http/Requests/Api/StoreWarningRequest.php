<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\User;

class StoreWarningRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'type' => ['required', Rule::in(['formal', 'informal', 'final'])],
            'level' => 'required|integer|min:1|max:5',
            'reason' => 'required|string|max:1000',
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
            'user_id.exists' => 'The selected user is invalid',
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
        
        // Set default values
        $data['is_active'] = $data['is_active'] ?? true;
        
        return $data;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'created_at' => now(),
        ]);
    }
}