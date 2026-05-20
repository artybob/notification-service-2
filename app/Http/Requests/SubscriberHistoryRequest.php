<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscriberHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscriber_id' => 'required|string|max:255',
            'limit' => 'sometimes|integer|min:1|max:500'
        ];
    }
}
