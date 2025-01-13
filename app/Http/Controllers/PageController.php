<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
	 /**
     * Handle the root route.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
     public function index()
    {
        // Verifica si el usuario está autenticado
        if (auth()->check()) {
            // Redirige al dashboard si está autenticado
            return redirect()->route('dashboard');
        }

        // Redirige al login si no está autenticado
        return redirect()->route('login');
    }
}
