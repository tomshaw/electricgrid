<?php

namespace TomShaw\ElectricGrid\Assets;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Scripts extends Component
{
    public function render(): View
    {
        return view('electricgrid::assets.scripts');
    }
}
