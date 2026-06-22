<?php

namespace App\Support;

/**
 * Nettoie les chaines issues de processus Windows (ipconfig, nmap) pour JSON UTF-8.
 */
class TextEncoding
{
    public static function toUtf8(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $fromWindows = @iconv('CP1252', 'UTF-8//IGNORE', $value);
            if ($fromWindows !== false && mb_check_encoding($fromWindows, 'UTF-8')) {
                return $fromWindows;
            }
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return $clean !== false ? $clean : preg_replace('/[^\x20-\x7E\xA0-\x{10FFFF}]/u', '', $value);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = self::toUtf8($value);
            } elseif (is_array($value)) {
                $data[$key] = self::sanitizeArray($value);
            }
        }

        return $data;
    }
}
