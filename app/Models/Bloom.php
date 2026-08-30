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

    /**
     * 出題碼 B11–B63。
     *
     * @return list<string>
     */
    public static function teacherChoiceIds(): array
    {
        $ids = [];
        foreach (range(1, 6) as $bloom) {
            foreach (range(1, 3) as $solo) {
                $ids[] = 'B'.$bloom.$solo;
            }
        }

        return $ids;
    }

    /**
     * @return array{title: string, usage: string}|null
     */
    public static function levelInfo(int $level): ?array
    {
        return match ($level) {
            1 => ['title' => '記憶', 'usage' => '事實/定義'],
            2 => ['title' => '理解', 'usage' => '解釋/說明'],
            3 => ['title' => '應用', 'usage' => '程式實作/填空'],
            4 => ['title' => '分析', 'usage' => '程式除錯/判讀'],
            5 => ['title' => '評鑑', 'usage' => '判斷/評論'],
            6 => ['title' => '創造', 'usage' => '設計/產出'],
            default => null,
        };
    }

    public static function teacherChoiceTitle(string $id): string
    {
        if (preg_match('/^B([1-6])([1-3])$/', $id, $matches) !== 1) {
            return $id;
        }

        $info = self::levelInfo((int) $matches[1]);
        if ($info === null) {
            return $id;
        }

        return $info['title'].'（'.$info['usage'].'）／SOLO '.$matches[2];
    }
}
