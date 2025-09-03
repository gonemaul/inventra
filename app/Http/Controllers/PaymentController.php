<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        return Inertia::render('Keuangan/index');
    }

    public function detail()
    {
        return Inertia::render('Keuangan/detail');
    }
}
