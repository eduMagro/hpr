<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AyudaController extends Controller
{
    public function index()
    {
        return view('ayuda.index');
    }
}
