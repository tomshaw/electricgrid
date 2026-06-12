<?php

namespace TomShaw\ElectricGrid\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    protected $guarded = [];

    public function testModels(): HasMany
    {
        return $this->hasMany(TestModel::class);
    }
}
