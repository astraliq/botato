<?php

declare(strict_types=1);

namespace App\Web\Shared;

use App\Shared\ArrayHelper;
use Psr\Http\Message\ServerRequestInterface;

final class JsonBodyParser
{
    /** @return array<string, mixed> */
    public static function parse(ServerRequestInterface $request): array
    {
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && $parsedBody !== []) {
            return ArrayHelper::toStringKeyArray($parsedBody);
        }

        $body = $request->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $payload = json_decode($body->getContents(), true);

        return is_array($payload) ? ArrayHelper::toStringKeyArray($payload) : [];
    }
}
