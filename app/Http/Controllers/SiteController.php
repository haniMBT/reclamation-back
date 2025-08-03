<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SiteController extends Controller
{
    /**
     * Générer le PDF des conditions d'utilisation
     * 
     * @return \Illuminate\Http\Response
     */
    public function generateConditions()
    {
        $pdfcond = PDF::loadView('layouts.conditions')->setPaper('A4','portrait');
            return $pdfcond->stream();
    }
}