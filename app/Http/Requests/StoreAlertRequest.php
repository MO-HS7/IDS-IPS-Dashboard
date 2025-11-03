<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Alert::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'network_log_id' => 'required|exists:network_logs,id',
            'ml_model_id' => 'required|exists:ml_models,id',
            'attack_type' => 'required|string|max:255',
            'severity' => 'required|in:low,medium,high,critical',
            'source_ip' => 'required|ip',
            'destination_ip' => 'required|ip',
            'confidence_score' => 'required|numeric|between:0,1',
            'description' => 'required|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'network_log_id.required' => 'Please select a network log.',
            'network_log_id.exists' => 'The selected network log does not exist.',
            'ml_model_id.required' => 'Please select an ML model.',
            'ml_model_id.exists' => 'The selected ML model does not exist.',
            'attack_type.required' => 'Attack type is required.',
            'attack_type.max' => 'Attack type must not exceed 255 characters.',
            'severity.required' => 'Severity level is required.',
            'severity.in' => 'Severity must be one of: low, medium, high, critical.',
            'source_ip.required' => 'Source IP address is required.',
            'source_ip.ip' => 'Source IP must be a valid IP address.',
            'destination_ip.required' => 'Destination IP address is required.',
            'destination_ip.ip' => 'Destination IP must be a valid IP address.',
            'confidence_score.required' => 'Confidence score is required.',
            'confidence_score.numeric' => 'Confidence score must be a number.',
            'confidence_score.between' => 'Confidence score must be between 0 and 1.',
            'description.required' => 'Description is required.',
            'description.max' => 'Description must not exceed 1000 characters.',
        ];
    }
}
