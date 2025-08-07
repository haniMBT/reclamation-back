@extends('layouts.home')

@section('title', 'Échec de Paiement')

@section('content')
<style>
    .content-header, .content {
        width: 90%;
        max-width: 1000px;
        margin: 0 auto;
        font-family: sans-serif;
    }

    .breadcrumb {
        list-style: none;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding: 0;
        margin: 0;
        font-size: 14px;
    }

    .breadcrumb-item::after {
        content: "/";
        margin: 0 5px;
        color: #888;
    }

    .breadcrumb-item:last-child::after {
        content: "";
    }

    .breadcrumb a {
        text-decoration: none;
        color: #007BFF;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .col-half {
        width: 48%;
    }

    .centered-row {
        display: flex;
        justify-content: center;
        margin-top: 40px;
    }

    .card {
        width: 100%;
        max-width: 700px;
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: #fff0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .card-header {
        background-color: #b00020;
        color: white;
        padding: 15px;
        text-align: center;
    }

    .card-body {
        padding: 30px;
        background-color: #fff;
    }

    .alert {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        font-weight: bold;
        text-align: center;
    }

    .alert-danger {
        background-color: #ffe5e5;
        color: #a80000;
    }

    .alert-warning {
        background-color: #fff8e5;
        color: #8a6d3b;
    }

    .payment-info {
        text-align: center;
        margin-top: 30px;
    }

    .payment-info img {
        max-height: 100px;
        margin-top: 15px;
    }

    .btn {
        display: inline-block;
        margin-top: 25px;
        padding: 10px 20px;
        background-color: #f0ad4e;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s;
    }

    .btn:hover {
        background-color: #ec971f;
    }
</style>

<!-- Content Header -->
<section class="content-header">
  <div class="row">
    <div class="col-half">
      <h1>Échec de Paiement</h1>
    </div>
    <div class="col-half">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
        <li class="breadcrumb-item active">Échec de Paiement</li>
      </ol>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="content">
  <div class="centered-row">
    <div class="card">
      <div class="card-header">
        <h3>La transaction n’a pas pu être établie</h3>
      </div>
      <div class="card-body">
        @if($orderstatus == "3")
          <div class="alert alert-danger">
            Votre transaction a été rejetée.<br>
            <em>Your transaction was rejected / تم رفض معاملتك</em>
          </div>
        @else
          <div class="alert alert-warning">
            {{ $message }}
          </div>
        @endif

        <div class="payment-info">
          <p>
            En cas de problème de paiement, contactez la SATIM sur le numéro vert :
            <strong>3020</strong>
          </p>
          <img src="{{ asset('dist/img/satim.png') }}" alt="SATIM">
        </div>

        <div class="payment-info">
          <a href="{{ route('facture.payer', $facture->id) }}" class="btn">
              Réessayer le Paiement
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
