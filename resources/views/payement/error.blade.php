@extends('layouts.home')

@section('title', 'Erreur de Paiement')

@section('content')
    <style>
        .error-wrapper {
            max-width: 600px;
            margin: 60px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
            font-family: sans-serif;
        }

        .error-header {
            text-align: center;
            color: #b00020;
            margin-bottom: 20px;
        }

        .error-body {
            text-align: center;
        }

        .error-message {
            color: #b00020;
            font-weight: bold;
        }

        .error-alert {
            background-color: #ffdddd;
            color: #a94442;
            padding: 10px;
            margin-top: 15px;
            border-radius: 4px;
        }

        .back-button {
            display: inline-block;
            margin-top: 25px;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .back-button:hover {
            background-color: #0056b3;
        }
    </style>

    <div class="error-wrapper">
        <div class="error-header">
            <h2>Erreur de Paiement</h2>
        </div>

        <div class="error-body">
            <p class="error-message">Votre paiement n'a pas pu être traité.</p>
            <p>Une erreur est survenue lors du processus de paiement. Veuillez réessayer.</p>

            @if (session('error'))
                <div class="error-alert">
                    {{ session('error') }}
                </div>
            @endif
            <a href="{{ env('FRONTEND_URL') . '/epayment/factures' }}" class="back-button">Retour à l'accueil</a>
        </div>
    </div>
@endsection
