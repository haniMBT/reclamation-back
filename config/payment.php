<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration des paiements SATIM
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration avec le système de paiement SATIM CIB
    |
    */

    'username' => env('SATIM_USERNAME', 'test_username'),
    'password' => env('SATIM_PASSWORD', 'test_password'),
    
    // URLs des APIs de paiement
    'register_url' => env('SATIM_REGISTER_URL', 'https://test.satim.dz/payment/rest/register.do'),
    'status_url' => env('SATIM_STATUS_URL', 'https://test.satim.dz/payment/rest/getOrderStatus.do'),
    
    // Configuration du terminal
    'terminal_id' => env('SATIM_TERMINAL_ID', 'E004000135'),
    
    // URLs de retour (fallback si frontend_url n'est pas définie)
    'return_url' => env('APP_FRONTEND_URL', 'http://localhost:8080'),
    
    // Devise (DZD)
    'currency' => '012',
    
    // Timeout des requêtes en secondes
    'timeout' => 30,
    
    // Mode (test|production)
    'mode' => env('SATIM_MODE', 'test')
];