<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Assets;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Styles extends Component
{
    public function render(): View
    {
        return view('electricgrid::assets.styles');
    }
}
