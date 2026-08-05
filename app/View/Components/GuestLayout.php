<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    // Render guest layout view
    public function render(): View
    {
        return view('layouts.guest');
    }
}
