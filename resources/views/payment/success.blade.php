@extends('layouts.payment')

@section('title', 'Paiement Réussi - EPAL')

@section('card-class', 'status-success')

@section('content')
<div class="card-header">
    <h1 class="h3 mb-3">
        <i class="fas fa-check-circle fa-2x mb-3"></i><br>
        Transaction Réussie
    </h1>
    <p class="mb-0">Votre paiement a été traité avec succès</p>
</div>

<div class="card-body">
    @if(session('info'))
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        {{ session('info') }}
    </div>
    @endif

    <div class="alert alert-success mb-4">
        <i class="fas fa-envelope me-2"></i>
        Le reçu de paiement vous a été envoyé par e-mail !
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="info-group">
                <div class="info-label">Date et Heure :</div>
                <div class="info-value">{{ $dateValable ?? now()->format('d-m-Y H:i:s') }}</div>
            </div>
            
            <div class="info-group">
                <div class="info-label">ID Transaction :</div>
                <div class="info-value">{{ $orderId ?? 'N/A' }}</div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Numéro Transaction :</div>
                <div class="info-value">{{ $obj->OrderNumber ?? 'N/A' }}</div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="info-group">
                <div class="info-label">Code d'autorisation :</div>
                <div class="info-value">{{ $obj->approvalCode ?? 'N/A' }}</div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Montant :</div>
                <div class="info-value">{{ number_format($facture->facttc ?? 0, 2, ',', '.') }} DA</div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Mode de paiement :</div>
                <div class="info-value">Carte CIB/Edhahabia</div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning mt-4">
        <i class="fas fa-phone me-2"></i>
        <strong>Support SATIM :</strong> En cas de problème, contactez le <strong>3020</strong>
    </div>

    <div class="actions-container">
        <div class="row g-2">
            <div class="col-md-4">
                <a href="/receipt/{{ $order->recuId ?? 1 }}" 
                   class="btn btn-success btn-action w-100">
                    <i class="fas fa-print me-2"></i>
                    Imprimer le Reçu
                </a>
            </div>
            
            <div class="col-md-4">
                <a href="/receipt/{{ $order->recuId ?? 1 }}/download" 
                   class="btn btn-info btn-action w-100">
                    <i class="fas fa-download me-2"></i>
                    Télécharger PDF
                </a>
            </div>
            
            <div class="col-md-4">
                <button type="button" class="btn btn-warning btn-action w-100" 
                        data-bs-toggle="modal" data-bs-target="#emailModal">
                    <i class="fas fa-envelope me-2"></i>
                    Envoyer par Email
                </button>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="/" class="btn btn-outline-primary btn-action">
                <i class="fas fa-home me-2"></i>
                Retour à l'accueil
            </a>
        </div>
    </div>
</div>

<!-- Modal Email -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
                            <form action="/receipt/{{ $order->recuId ?? 1 }}/send-email" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="emailModalLabel">
                        <i class="fas fa-envelope me-2"></i>
                        Envoyer le reçu par email
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="emailInput" class="form-label">Adresse e-mail</label>
                        <input type="email" class="form-control" id="emailInput" name="email" 
                               placeholder="exemple@email.com" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Animation d'entrée
    $('.payment-card').hide().fadeIn(800);
    
    // Gestion du modal email
    $('#emailModal').on('shown.bs.modal', function () {
        $('#emailInput').focus();
    });
});
</script>
@endsection