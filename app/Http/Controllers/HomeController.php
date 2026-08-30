<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

final class HomeController
{
    public function __invoke(): View
    {
        return view('home');
    }
}
