<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreAppealRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'report_id' => [
                'required',
                'integer',
                'exists:reports,id',
                function ($attribute, $value, $fail) {
                    // Ensure user can appeal this report
                    $report = \App\Models\Report::find($value);
                    if ($report && $report->reporter_id !== Auth::id()) {
                        $fail('You can only appeal reports you have submitted.');
                    }
                }
            ],
            'reason' => 'required|string|max:1000',
            'description' => 'required|string|max:2000',
            'evidence' => 'nullable|array',
            'evidence.*.type' => 'required|string|in:screenshot,chat_log,email,other',
            'evidence.*.description' => 'required|string|max:500',
            'evidence.*.file_path' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'report_id.exists' => 'The selected report is invalid',
            'report_id.required' => 'A report ID is required',
            'reason.required' => 'An appeal reason is required',
            'description.required' => 'A detailed description is required',
            'evidence.*.type.in' => 'Evidence type must be one of: screenshot, chat_log, email, other',
        ];
    }

    /**
     * Get validated data with custom processing.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        
        // Add user ID and metadata
        $data['user_id'] = Auth::id();
        $data['status'] = 'pending';
        $data['submitted_at'] = now();
        
        // Filter out empty evidence items
        if (isset($data['evidence'])) {
            $data['evidence'] = array_filter($data['evidence'], function($item) {
                return !empty($item['type']) && !empty($item['description']);
            });
        }
        
        return $data;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}