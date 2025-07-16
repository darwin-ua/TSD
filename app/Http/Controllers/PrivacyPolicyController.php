<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    public function index()
    {
        return view('privacy_policy');
    }

    public function dataDeletionInstructions()
    {
        return view('data_deletion_instructions');
    }
}
