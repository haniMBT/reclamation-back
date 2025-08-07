@extends('layouts.home')

@section('content')
<style>
  body {
    font-family: 'Poppins', sans-serif;
    background-color: #f5f6fa;
    color: #333;
  }

  .alert {
    padding: 15px;
    margin: 20px auto;
    max-width: 800px;
    border-radius: 4px;
    text-align: center;
  }

  .alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }

  .breadcrumb {
    list-style: none;
    padding: 0;
    display: flex;
    gap: 8px;
    font-size: 14px;
    justify-content: flex-end;
  }

  .breadcrumb li::after {
    content: "/";
    margin-left: 8px;
    color: #999;
  }

  .breadcrumb li:last-child::after {
    content: "";
  }

  .breadcrumb a {
    color: #007bff;
    text-decoration: none;
  }

  .section-header {
    max-width: 1000px;
    margin: 30px auto 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .card {
    max-width: 700px;
    margin: 20px auto;
    border: 1px solid #28a745;
    border-left: 5px solid #28a745;
    background-color: #fefefe;
    border-radius: 6px;
    padding: 20px;
  }

  .card-header h3 {
    color: #28a745;
    text-align: center;
  }

  .form-group {
    margin-bottom: 15px;
  }

  .form-group label {
    font-weight: bold;
    display: block;
  }

  .actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 15px;
    margin-top: 30px;
  }

  .btn {
    padding: 10px 18px;
    text-decoration: none;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s;
  }

  .btn-success {
    background-color: #28a745;
    color: white;
  }

  .btn-info {
    background-color: #17a2b8;
    color: white;
  }

  .btn-warning {
    background-color: #ffc107;
    color: black;
  }

  .btn-secondary {
    background-color: #6c757d;
    color: white;
  }

  .btn-primary {
    background-color: #007bff;
    color: white;
  }

  .modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
  }

  .modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    max-width: 500px;
    border-radius: 8px;
    position: relative;
  }

  .modal-header, .modal-footer {
    margin-bottom: 15px;
  }

  .modal-header h5 {
    margin: 0;
  }

  .close {
    position: absolute;
    right: 15px;
    top: 15px;
    font-size: 22px;
    cursor: pointer;
    color: #888;
  }

  input[type="email"] {
    width: 100%;
    padding: 10px;
    margin-top: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }

  .satim-logo {
    max-height: 80px;
    margin-top: 10px;
  }

</style>

<div class="alert alert-success">
  <p>Le Reçu de paiement vous a été envoyé par e-mail !</p>
</div>

@if(session('info'))
  <div class="alert alert-success">
    {{ session('info') }}
  </div>
@endif

<div class="section-header">
  <h1>Transaction Réussie</h1>
  <ol class="breadcrumb">
    <li><a href="#">Home</a></li>
    <li>Paiement Effectué</li>
  </ol>
</div>

<div class="card">
  <div class="card-header">
    <h3>{{ $obj->params->respCode_desc ?? 'Transaction réussie' }}</h3>
  </div>
  <div class="card-body">
    <div class="form-group">
      <label>Date et Heure de la Transaction:</label> {{ $dateValable }}
    </div>
    <div class="form-group">
      <label>Identifiant de la Transaction:</label> {{ $orderId }}
    </div>
    <div class="form-group">
      <label>Numéro de la Transaction:</label> {{ $obj->OrderNumber ?? 'N/A' }}
    </div>
    <div class="form-group">
      <label>Numéro d'autorisation:</label> {{ $obj->approvalCode ?? 'N/A' }}
    </div>
    <div class="form-group">
      <label>Montant de paiement:</label> {{ number_format($facture->facttc, 2, ',', '.') }} DA
    </div>
    <div class="form-group">
      <label>Mode de Paiement:</label> Carte CIB/Edhahabia
    </div>
    <div class="form-group">
      <label>En cas de problème de paiement, Contactez la SATIM sur le Numéro vert : 3020</label><br>
      <img src="{{ asset('dist/img/satim.png') }}" alt="SATIM" class="satim-logo">
    </div>
  </div>
</div>

<div class="actions">
  <a href="{{ route('payment.printrecu', $order->recuId) }}" class="btn btn-success">🖨 Imprimer le Reçu</a>
  <a href="{{ route('payment.downloadrecu', $order->recuId) }}" class="btn btn-info">⬇ Télécharger en PDF</a>
  <button id="btnEmail" class="btn btn-warning">✉ Envoyer le Reçu par e-mail</button>
</div>

<!-- Modal personnalisé sans Bootstrap -->
<div class="modal" id="emailModal">
  <div class="modal-content">
    <form action="{{ route('payment.sendmail', $order->recuId) }}" method="POST">
      @csrf

      <div class="modal-header">
        <h5>Envoyer votre reçu par e-mail</h5>
        <span class="close" onclick="closeModal()">&times;</span>
      </div>

      <div class="modal-body">
        <label for="emailInput">Adresse e-mail</label>
        <input type="email" id="emailInput" name="email" required placeholder="Entrez votre e-mail">
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Fermer</button>
        <button type="submit" class="btn btn-primary">Envoyer</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('script-footer')
<script>
  function closeModal() {
    document.getElementById('emailModal').style.display = 'none';
  }

  document.getElementById('btnEmail').addEventListener('click', function () {
    document.getElementById('emailModal').style.display = 'block';
  });
</script>
@endsection
