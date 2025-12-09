<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AtajosController extends Controller
{
    public function index()
    {
        return view('atajos.index');
    }
}
