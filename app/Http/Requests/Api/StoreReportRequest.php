<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public reporting endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reportable_type' => 'required|string|in:User,Message,GroupMessage,Group',
            'reportable_id' => 'required|integer',
            'category_id' => 'required|exists:report_categories,id',
            'reason' => 'required|string|max:1000',
            'description' => 'nullable|string|max:2000',
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
            'reportable_type.in' => 'The reportable type must be one of: User, Message, GroupMessage, Group',
            'category_id.exists' => 'The selected category is invalid',
            'evidence.*.type.in' => 'Evidence type must be one of: screenshot, chat_log, email, other',
        ];
    }

    /**
     * Get validated data with custom processing.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        
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
            'reporter_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}