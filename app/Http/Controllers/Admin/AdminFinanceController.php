<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminFinanceController extends Controller
{

    public function index()
    {
        return view('admin.payments.index', compact( ));
    }


}