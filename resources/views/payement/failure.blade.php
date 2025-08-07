@extends('layouts.home')

@section('title', 'Erreur de Paiement')

@section('content')
<style>
  .error-container {
    max-width: 800px;
    margin: 60px auto;
    padding: 25px;
    border: 1px solid #ccc;
    border-left: 5px solid #d9534f;
    border-radius: 8px;
    background-color: #fff8f8;
    font-family: 'Poppins', sans-serif;
  }

  .error-header {
    text-align: center;
    margin-bottom: 20px;
  }

  .error-header h2 {
    color: #d9534f;
    margin: 0;
  }

  .error-body {
    text-align: center;
  }

  .error-body p {
    margin-bottom: 15px;
    font-size: 16px;
  }

  .text-danger {
    color: #a94442;
    font-weight: bold;
  }

  .alert {
    padding: 12px;
    margin: 20px auto;
    max-width: 600px;
    border-radius: 4px;
    text-align: center;
    font-weight: bold;
  }

  .alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }

  .button-group {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 25px;
  }

  .btn {
    padding: 10px 18px;
    text-decoration: none;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s;
  }

  .btn-primary {
    background-color: #007bff;
    color: white;
  }

  .btn-primary:hover {
    background-color: #0056b3;
  }

  .btn-warning {
    background-color: #ffc107;
    color: black;
  }

  .btn-warning:hover {
    background-color: #e0a800;
  }
</style>

<div class="error-container">
  <div class="error-header">
    <h2>Échec de Paiement</h2>
  </div>

  <div class="error-body">
    <p class="text-danger"><strong>Échec : Votre paiement n'a pas pu être traité.</strong></p>
    <p>Une erreur est survenue lors du processus de paiement. Nous vous prions de bien vouloir réessayer.</p>
    <p>{{ $errorMessage }}</p>

    @if(session('error'))
      <div class="alert alert-danger">
        {{ session('error') }}
      </div>
    @endif

    <div class="button-group">
      <a href="{{ route('facture.index') }}" class="btn btn-primary">Retour à l'accueil</a>
      <a href="{{ route('facture.payer', ['id' => $id ?? null]) }}" class="btn btn-warning">Réessayer le Paiement</a>
    </div>
  </div>
</div>
@endsection
