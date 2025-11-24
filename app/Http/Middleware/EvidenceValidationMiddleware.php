<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class EvidenceValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            $validation = $this->validateEvidence($request);
            
            if ($validation->fails()) {
                return response()->json([
                    'message' => 'Evidence validation failed',
                    'errors' => $validation->errors(),
                    'code' => 'evidence_validation_failed'
                ], 422);
            }
        }

        return $next($request);
    }

    /**
     * Validate evidence data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Validation\Validator
     */
    protected function validateEvidence(Request $request)
    {
        $rules = [
            'evidence' => 'required|array|max:10',
            'evidence.*.type' => 'required|in:screenshot,chat_log,video,audio,document,other',
            'evidence.*.description' => 'required|string|max:1000',
            'evidence.*.file' => 'required_if:evidence.*.type,screenshot,video,audio,document|file|max:10240', // 10MB max
            'evidence.*.url' => 'required_if:evidence.*.type,other|url|max:500',
            'evidence.*.timestamp' => 'nullable|date',
            'evidence.*.metadata' => 'nullable|array|max:1000',
        ];

        $messages = [
            'evidence.required' => 'At least one piece of evidence is required',
            'evidence.max' => 'Maximum 10 evidence files allowed',
            'evidence.*.type.in' => 'Invalid evidence type. Allowed types: screenshot, chat_log, video, audio, document, other',
            'evidence.*.file.max' => 'Evidence file size cannot exceed 10MB',
            'evidence.*.file.required_if' => 'File is required for this evidence type',
            'evidence.*.url.required_if' => 'URL is required for "other" evidence type',
            'evidence.*.url.url' => 'Please provide a valid URL',
            'evidence.*.description.max' => 'Evidence description cannot exceed 1000 characters',
        ];

        return Validator::make($request->all(), $rules, $messages);
    }
}