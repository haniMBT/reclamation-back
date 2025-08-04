@extends('layouts.payment')

@section('title', 'Erreur de Paiement - EPAL')

@section('card-class', 'status-warning')

@section('content')
    <div class="card-header">
        <h1 class="h3 mb-3">
            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>
            Erreur de Paiement
        </h1>
        <p class="mb-0">Une erreur technique est survenue</p>
    </div>

    <div class="card-body">
        @if (session('error'))
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Votre paiement n'a pas pu être traité.</strong>
        </div>

        <div class="mb-4">
            <p class="text-muted">
                Une erreur technique est survenue lors du processus de paiement.
                Cette erreur peut être temporaire.
            </p>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-lightbulb me-2"></i>
            <strong>Solutions recommandées :</strong><br>
            • Actualisez la page et réessayez<br>
            • Vérifiez votre connexion internet<br>
            • Essayez à nouveau dans quelques minutes<br>
            • Contactez le support technique si le problème persiste
        </div>

        <div class="alert alert-warning">
            <i class="fas fa-phone me-2"></i>
            <strong>Support SATIM :</strong> En cas de problème technique, contactez le <strong>3020</strong>
        </div>

        <div class="actions-container">
            <div class="row g-2">
                <div class="col-md-4">

                </div>

                <div class="col-md-4">
                    <a href="{{ rtrim(env('FRONTEND_URL'), '/') }}/epayment/factures"
                        class="btn btn-primary btn-action w-100">
                        <i class="fas fa-home me-2"></i>
                        Accueil
                    </a>

                </div>

                <div class="col-md-4">

                </div>
            </div>

            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>
                    Si vous rencontrez des difficultés persistantes,
                    nos équipes techniques sont disponibles pour vous aider.
                </small>
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
