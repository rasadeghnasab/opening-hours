<?php

namespace App\Http\Controllers;

class PagesController extends Controller
{
    public function __invoke()
    {
        return view('home');
    }
}
