<?php

declare(strict_types=1);

namespace App\Domain\Ai;

enum AiModelPurpose: string
{
    case Chat = 'chat';
    case Embedding = 'embedding';
    case Moderation = 'moderation';
    case Tts = 'tts';
    case Stt = 'stt';
    case Image = 'image';
    case Video = 'video';
}
