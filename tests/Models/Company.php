<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $guarded = [];

    public function authors(): HasMany
    {
        return $this->hasMany(Author::class);
    }
}
