<?php

namespace App\Services\Providers;

use Illuminate\Support\Str;

class EmailProviderMock
{
    public function send(string $email, string $message): array
    {
        $success = rand(1, 100) > 8;

        usleep(rand(80000, 250000));

        if ($success) {
            return [
                'success' => true,
                'provider_message_id' => Str::uuid()->toString(),
                'status' => 'delivered'
            ];
        }

        return [
            'success' => false,
            'error' => 'Invalid email address',
            'status' => 'bounced'
        ];
    }
}
