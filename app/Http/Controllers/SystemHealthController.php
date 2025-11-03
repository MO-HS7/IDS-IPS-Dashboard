<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemHealthController extends Controller
{
    public function index()
    {
        return Inertia::render('SystemHealth/Index');
    }
}
