<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\User;

class UpdateAppealRequest extends FormRequest
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
            'status' => ['required', Rule::in(['pending', 'under_review', 'approved', 'rejected'])],
            'reviewer_id' => 'nullable|integer|exists:users,id',
            'review_notes' => 'nullable|string|max:2000',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: pending, under_review, approved, rejected',
            'reviewer_id.exists' => 'The selected reviewer is invalid',
        ];
    }

    /**
     * Get validated data with custom processing.
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        
        // Add reviewer ID if authenticated
        if (Auth::check() && !isset($data['reviewer_id'])) {
            $data['reviewer_id'] = Auth::id();
        }
        
        // Add review timestamp if status is being changed
        if (isset($data['status']) && in_array($data['status'], ['approved', 'rejected'])) {
            $data['reviewed_at'] = now();
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
            // Check if user can approve/reject based on role
            if (Auth::check() && isset($this->status)) {
                $user = Auth::user();
                
                if (in_array($this->status, ['approved', 'rejected'])) {
                    // For now, allow all authenticated users to approve/reject
                    // TODO: Implement proper role-based restrictions
                    $validator->errors()->add('status', 
                        'Only senior moderators or admins can approve or reject appeals');
                }
            }
        });
    }
}