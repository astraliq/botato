<?php

declare(strict_types=1);

namespace App\Domain\Channel;

enum ChannelProvider: string
{
    case Telegram = 'telegram';
    case Vk = 'vk';
    case Instagram = 'instagram';
}
