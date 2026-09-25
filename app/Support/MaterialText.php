<?php

namespace App\Support;

/**
 * 匯入比對知識卡用的正規化；預覽與正式匯入都必須走這裡，結果才會一致。
 */
final class MaterialText
{
    public static function normalize(?string $value): string
    {
        $text = strip_tags((string) $value);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\s\x{00A0}\x{3000}]+/u', ' ', $text) ?? $text;

        return mb_strtolower(trim($text));
    }

    public static function titleKey(string $title, ?string $type): string
    {
        return self::normalize($title)."\0".self::normalize($type ?: 'keyword');
    }

    public static function contentKey(string $title, ?string $type, ?string $content): string
    {
        return self::titleKey($title, $type)."\0".self::normalize($content);
    }
}
