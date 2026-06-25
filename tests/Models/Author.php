<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Author extends Model
{
    protected $guarded = [];

    public function testModels(): HasMany
    {
        return $this->hasMany(TestModel::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
