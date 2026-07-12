<?php

declare(strict_types=1);

namespace App\Shared;

final class ArrayHelper
{
    /**
     * @param array<array-key, mixed> $array
     * @return array<string, mixed>
     */
    public static function toStringKeyArray(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
