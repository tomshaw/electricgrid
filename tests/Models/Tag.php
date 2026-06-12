<?php

namespace TomShaw\ElectricGrid\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $guarded = [];

    public function testModels(): BelongsToMany
    {
        return $this->belongsToMany(TestModel::class);
    }
}
