<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Solo extends Model
{
    protected $table = 'solo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'structure_info',
    ];

    public function mappings(): HasMany
    {
        return $this->hasMany(QuestionBloomSoloMapping::class, 'solo_id');
    }
}
