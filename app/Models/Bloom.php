<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bloom extends Model
{
    protected $table = 'bloom';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'cognition_info',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'bloom_id');
    }

    /** 創造（期末作品）與後設認知（後測問卷）不用題目編碼 */
    public const LEVELS = [
        1 => '記憶瞭解',
        2 => '應用',
        3 => '分析',
        4 => '評價',
    ];

    public const KNOWLEDGE_TYPES = [
        1 => '事實',
        2 => '概念',
        3 => '程序',
    ];

    /**
     * 出題碼 B11–B43：第一碼認知層級，第二碼知識類型。
     *
     * @return list<string>
     */
    public static function teacherChoiceIds(): array
    {
        $ids = [];
        foreach (array_keys(self::LEVELS) as $level) {
            foreach (array_keys(self::KNOWLEDGE_TYPES) as $type) {
                $ids[] = 'B'.$level.$type;
            }
        }

        return $ids;
    }

    public static function levelOf(?string $id): ?int
    {
        if ($id === null || preg_match('/^B([1-4])[1-3]?$/i', $id, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * 選擇／是非題答對的預設 SOLO：B11 為 S2，其他為 S3。
     */
    public static function defaultCorrectSolo(?string $id): int
    {
        return $id === 'B11' ? 2 : 3;
    }

    public static function teacherChoiceTitle(string $id): string
    {
        if (preg_match('/^B([1-4])([1-3])$/', $id, $matches) !== 1) {
            return $id;
        }

        return self::LEVELS[(int) $matches[1]].'／'.self::KNOWLEDGE_TYPES[(int) $matches[2]];
    }
}
