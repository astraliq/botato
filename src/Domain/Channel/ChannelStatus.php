<?php

declare(strict_types=1);

namespace App\Domain\Channel;

enum ChannelStatus: string
{
    case Connected = 'connected';
    case Disconnected = 'disconnected';
    case Error = 'error';
}
