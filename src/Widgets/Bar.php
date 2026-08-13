<?php

namespace Aura\Base\Widgets;

use Illuminate\Contracts\View\View;

class Bar extends Sparkline
{
    public function render(): View
    {
        return view('aura::components.widgets.bar');
    }
}
