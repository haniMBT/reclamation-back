@extends('layouts.home')

@section('script-header')

{{-- Ajout de style CSS personnalisé --}}

@endsection

@section('content')
<style>
  .container-form {
    background: linear-gradient(135deg, #d0f0ff, #0072ff);
    /* Dégradé de bleu très clair à bleu foncé */
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    /* Ombre douce */
    color: white;
  }

  .card {
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    background-color: #fff;
    padding: 20px;
  }

  .card-header {
    background-color: #007bff;
    color: white;
    padding: 10px 15px;
    border-radius: 12px 12px 0 0;
  }

  .form-control {
    border-radius: 8px;
    border: 1px solid #ddd;
    transition: border-color 0.3s ease;
  }

  .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
  }

  .btn-primary {
    background-color: #007bff;
    border-color: #007bff;
    transition: background-color 0.3s ease;
  }

  .btn-primary:hover {
    background-color: #0056b3;
  }

  .btn-lg {
    padding: 10px 20px;
    border-radius: 8px;
  }

  /* Feedback pour erreurs */
  .alert {
    font-size: 0.9em;
    margin-top: 10px;
  }

  /* Bordure du CAPTCHA */
  .g-recaptcha {
    margin-top: 10px;
  }

  /* Animation d'erreurs */
  .fade-in {
    animation: fadeIn 0.5s ease-in-out;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }

    to {
      opacity: 1;
    }
  }
</style>
{!! NoCaptcha::renderJs() !!}
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        {{-- Titre si nécessaire --}}
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Paiement Facture</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid container-form">
    <div class="row">
      <div class="col-md-6" style="margin: auto;">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Formulaire de Paiement</h3>
          </div>
          <form action="{{route('payment.process',$facture->id)}}" method="POST">
            @csrf
            <div class="card-body">
              {{-- Informations de la facture --}}
              <div class="form-group">
                <label for="facnum">Vous êtes sur le point de payer la facture N° :</label>
                <input type="text" class="form-control form-control-lg" id="facnum"
                  value="{{$facture->facrfe}} du {{date_format(date_create($facture->facdat)," d/m/Y")}}" disabled>
              </div>

              <div class="form-group">
                <label for="facttc">Montant TTC à payer :</label>
                <strong style="font-size: 2rem; color: #333;">{{ number_format($facture->facttc, 2, ",", ".") }}
                  DA</strong>
              </div>

              {{-- CAPTCHA --}}
              <div class="form-group">
                {!! NoCaptcha::display() !!}
                @if ($errors->has('g-recaptcha-response'))
                <div class="alert alert-danger fade-in">
                  <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
                </div>
                @endif
              </div>

              {{-- Conditions d'utilisation --}}
              <div class="form-group mb-0">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" name="terms" class="custom-control-input" id="terms">
                  <label class="custom-control-label" for="terms">
                    J'accepte <a href="{{route('conditions')}}" style="text-decoration: underline;" target="_blank">les conditions
                      d'utilisation</a>.
                  </label>
                </div>
                @if ($errors->has('terms'))
                <div class="alert alert-danger fade-in">
                  <strong>{{ $errors->first('terms') }}</strong>
                </div>
                @endif
              </div>
            </div>

            {{-- Footer: Méthode de paiement + Bouton de validation --}}
            <div class="card-footer d-flex justify-content-between">
              <p class="lead" style="align-self: center;">Méthode de Paiement :
                <img src="{{ asset('../../dist/img/Logo_Interoperabilite_Final.png') }}" style="width: 30%;" alt="CIB">
              </p>
              <button type="submit" class="btn btn-primary btn-lg">Valider</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('script-footer')
<script type="text/javascript">
  var onloadCallback = function() {
      console.log("reCAPTCHA is ready!");
    };
</script>
@endsection