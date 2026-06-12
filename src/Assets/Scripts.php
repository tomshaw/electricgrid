<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Assets;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Scripts extends Component
{
    public function render(): View
    {
        // @phpstan-ignore argument.type (namespaced package views are registered at runtime)
        return view('electricgrid::assets.scripts');
    }
}
