<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\User;

class UpdateReportRequest extends FormRequest
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
            'status' => ['required', Rule::in(['pending', 'under_review', 'resolved', 'dismissed'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'resolution' => 'nullable|string|max:2000',
            'moderator_notes' => 'nullable|string|max:2000',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: pending, under_review, resolved, dismissed',
            'priority.in' => 'Priority must be one of: low, medium, high, critical',
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
}