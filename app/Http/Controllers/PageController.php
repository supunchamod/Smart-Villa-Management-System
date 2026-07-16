<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function dashboard(): View
    {
        return view('dashboard');
    }

    public function calendar(): View
    {
        return view('calendar');
    }

    public function projects(): View
    {
        return view('projects');
    }

    public function chat(): View
    {
        return view('chat');
    }

    public function inbox(): View
    {
        return view('inbox');
    }

    public function fileManager(): View
    {
        return view('file-manager');
    }

    public function products(): View
    {
        return view('products');
    }

    public function settings(): View
    {
        return view('settings');
    }

    public function reports(): View
    {
        return view('reports');
    }

    public function team(): View
    {
        return view('team');
    }
}
