<?php

namespace App\Http\Controllers\epayment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PDF;

class SiteController extends Controller
{
    /**
     * Générer le PDF des conditions d'utilisation
     */
    public function generateConditions()
    {
        try {
            $pdfcond = PDF::loadView('layouts.conditions')->setPaper('A4', 'portrait');
            return $pdfcond->stream();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF des conditions'
            ], 500);
        }
    }
}