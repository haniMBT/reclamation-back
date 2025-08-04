@extends('layouts.payment')

@section('title', 'Échec de Paiement - EPAL')

@section('card-class', 'status-error')

@section('content')
<div class="card-header">
    <h1 class="h3 mb-3">
        <i class="fas fa-times-circle fa-2x mb-3"></i><br>
        Échec de Paiement
    </h1>
    <p class="mb-0">Votre paiement n'a pas pu être traité</p>
</div>

<div class="card-body">
    @if(session('error'))
    <div class="alert alert-danger mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ session('error') }}
    </div>
    @endif

    <div class="alert alert-danger mb-4">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Échec :</strong> Votre paiement n'a pas pu être traité.
    </div>

    <div class="mb-4">
        <p class="text-muted">
            Une erreur est survenue lors du processus de paiement. Nous vous prions de bien vouloir réessayer.
        </p>
        
        @if(isset($errorMessage))
        <div class="alert alert-warning">
            <strong>Détails de l'erreur :</strong><br>
            {{ $errorMessage }}
        </div>
        @endif
    </div>

    @if(isset($facture))
    <div class="info-group">
        <div class="info-label">Facture concernée :</div>
        <div class="info-value">{{ $facture->facnum ?? 'N/A' }}</div>
    </div>
    
    <div class="info-group">
        <div class="info-label">Montant :</div>
        <div class="info-value">{{ number_format($facture->facttc ?? 0, 2, ',', '.') }} DA</div>
    </div>
    @endif

    <div class="alert alert-info mt-4">
        <i class="fas fa-lightbulb me-2"></i>
        <strong>Que faire maintenant ?</strong><br>
        • Vérifiez votre solde de carte<br>
        • Assurez-vous que votre carte est activée pour les paiements en ligne<br>
        • Contactez votre banque si le problème persiste
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-phone me-2"></i>
        <strong>Support SATIM :</strong> En cas de problème, contactez le <strong>3020</strong>
    </div>

    <div class="actions-container">
        <div class="row g-2">
            <div class="col-md-6">
                @if(isset($id))
                <a href="{{ route('facture.payer', ['id' => $id]) }}" 
                   class="btn btn-warning btn-action w-100">
                    <i class="fas fa-redo me-2"></i>
                    Réessayer le Paiement
                </a>
                @endif
            </div>
            
            <div class="col-md-6">
                <a href="/" class="btn btn-primary btn-action w-100">
                    <i class="fas fa-home me-2"></i>
                    Retour à l'accueil
                </a>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="mailto:support@epal.dz" class="btn btn-outline-info btn-action">
                <i class="fas fa-envelope me-2"></i>
                Contacter le Support
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Animation d'entrée
    $('.payment-card').hide().fadeIn(800);
});
</script>
@endsection