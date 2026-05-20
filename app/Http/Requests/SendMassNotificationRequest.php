<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMassNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => 'required|in:sms,email',
            'message' => 'required|string|max:5000',
            'recipients' => 'required|array|min:1|max:1000',
            'recipients.*' => 'required|string|max:255',
            'priority' => 'sometimes|integer|min:0|max:10',
            'idempotency_key' => 'required|string|max:255'
        ];
    }
    
    public function messages(): array
    {
        return [
            'channel.required' => 'Channel is required',
            'channel.in' => 'Channel must be sms or email',
            'message.required' => 'Message is required',
            'recipients.required' => 'At least one recipient is required',
            'recipients.min' => 'At least one recipient is required',
            'recipients.max' => 'Cannot send to more than 1000 recipients at once',
            'priority.max' => 'Priority must be between 0 and 10',
            'priority.min' => 'Priority must be between 0 and 10',
            'idempotency_key.required' => 'Idempotency key is required'
        ];
    }
}
