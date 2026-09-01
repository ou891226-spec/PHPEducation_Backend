<?php

namespace App\Support;

/**
 * 填空／除錯／解讀比對用。全形標點與英數轉半形，避免中文輸入法誤判。
 */
final class AnswerText
{
    public static function same(string $student, string $expected): bool
    {
        $left = self::normalize($student);
        $right = self::normalize($expected);

        return $left !== '' && $left === $right;
    }

    public static function normalize(string $value): string
    {
        $value = trim(str_replace("\r\n", "\n", $value));
        $value = str_replace("\u{3000}", ' ', $value);
        $value = strtr($value, [
            "\u{201C}" => '"',
            "\u{201D}" => '"',
            "\u{2018}" => "'",
            "\u{2019}" => "'",
        ]);

        $converted = preg_replace_callback(
            '/[\x{FF01}-\x{FF5E}]/u',
            static fn (array $match): string => mb_chr(mb_ord($match[0]) - 0xFEE0),
            $value,
        );

        return $converted ?? $value;
    }
}
