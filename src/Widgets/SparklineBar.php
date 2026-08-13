<?php

namespace Aura\Base\Widgets;

use Illuminate\Contracts\View\View;

class SparklineBar extends Sparkline
{
    public function render(): View
    {
        return view('aura::components.widgets.sparkline-bar');
    }
}
