<?php

namespace App\Services\Providers;

use Illuminate\Support\Str;

class SmsProviderMock
{
    public function send(string $phoneNumber, string $message): array
    {
        $success = rand(1, 100) > 5;

        usleep(rand(50000, 200000));

        if ($success) {
            return [
                'success' => true,
                'provider_message_id' => Str::uuid()->toString(),
                'status' => 'sent'
            ];
        }

        return [
            'success' => false,
            'error' => 'Invalid phone number format',
            'status' => 'failed'
        ];
    }
}
