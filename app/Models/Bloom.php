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

    public function mappings(): HasMany
    {
        return $this->hasMany(QuestionBloomSoloMapping::class, 'bloom_id');
    }
}
