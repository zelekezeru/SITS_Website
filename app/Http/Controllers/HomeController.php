<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        
        return view('index');
    }

    public function about()
    {        
        return view('abouts.about');
    }

    public function elements()
    {        
        return view('layouts.elements');
    }
}
