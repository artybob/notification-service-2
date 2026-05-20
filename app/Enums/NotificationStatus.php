<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case DROPPED = 'dropped';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
