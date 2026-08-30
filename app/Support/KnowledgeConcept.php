<?php

namespace App\Support;

use App\Models\KnowledgeCard;

/**
 * 知識卡應是獨立知識點（變數命名、define()），不是章名或「實作變數01」。
 */
final class KnowledgeConcept
{
    /**
     * @var list<string>
     */
    private const GENERIC_SECTIONS = ['說明', '內容', '介紹', '課文', '重點', '摘要', '講解'];

    public static function fromUnitName(string $name): ?string
    {
        $name = trim($name);
        if ($name === '' || self::isGenericSection($name)) {
            return null;
        }

        if (preg_match('/^(實作|作業|練習|範例)\s*(.*)$/u', $name, $matches) === 1) {
            $rest = trim($matches[2]);
            $rest = preg_replace('/[\s_\-]*0*\d+$/u', '', $rest) ?? $rest;
            $rest = trim($rest);

            return $rest !== '' ? $rest : null;
        }

        return $name;
    }

    public static function isGenericSection(string $name): bool
    {
        return in_array(trim($name), self::GENERIC_SECTIONS, true);
    }

    public static function isPracticeSection(string $name): bool
    {
        return preg_match('/^(實作|作業|練習|範例)/u', trim($name)) === 1;
    }

    public static function looksLikeConceptTitle(string $title): bool
    {
        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 24) {
            return false;
        }

        if (preg_match('/^第[一二三四五六七八九十0-9]+章/u', $title) === 1) {
            return false;
        }

        if (preg_match('/[。．！？，、；：]|是用來|用來儲存/u', $title) === 1) {
            return false;
        }

        return true;
    }

    public static function displayTitle(KnowledgeCard $card): ?string
    {
        $unitName = trim((string) $card->unit?->name);
        $fromUnit = self::fromUnitName($unitName);

        if ($fromUnit !== null && ! self::isPracticeSection($unitName)) {
            $stored = trim((string) $card->title);
            if (self::looksLikeConceptTitle($stored) && $stored !== $fromUnit) {
                return $stored;
            }

            return $fromUnit;
        }

        if ($fromUnit !== null) {
            return $fromUnit;
        }

        $practiceNames = [];
        foreach ($card->unit?->chapter?->units ?? [] as $unit) {
            if (! self::isPracticeSection((string) $unit->name)) {
                continue;
            }
            $concept = self::fromUnitName((string) $unit->name);
            if ($concept !== null) {
                $practiceNames[$concept] = true;
            }
        }
        $practiceNames = array_keys($practiceNames);
        if (count($practiceNames) === 1) {
            return $practiceNames[0];
        }

        $title = trim((string) $card->title);
        if (self::looksLikeConceptTitle($title)) {
            return $title;
        }

        return null;
    }
}
