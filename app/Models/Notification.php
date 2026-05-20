<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'idempotency_key',
        'subscriber_id',
        'channel',
        'message',
        'status',
        'provider_response',
        'sent_at',
        'delivered_at',
        'retry_count'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'retry_count' => 'integer'
    ];
}
