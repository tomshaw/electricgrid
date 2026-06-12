<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Tests\Components;

use TomShaw\ElectricGrid\Tests\Models\TestModel;

class TestComponentWithRowClick extends TestComponent
{
    public function rowClick(): ?\Closure
    {
        return fn (TestModel $model) => $model->status === 1 ? '/test/'.$model->id : null;
    }
}
