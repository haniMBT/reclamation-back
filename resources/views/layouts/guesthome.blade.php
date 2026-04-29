<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>e-paiement | EPAL</title>

    @yield('script-header')

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('../../plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('../../dist/css/adminlte.min.css') }}">
</head>

<style>
    /* Full-page background */
    body {
        background-image: url('/dist/img/guesthome.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        min-height: 100vh;
        width: 100vw;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Translucent background for form */
    .card {
        background-color: rgba(255, 255, 255, 0.8);
        border-radius: 8px;
    }

    .logo {
        transition: transform 0.3s ease-in-out, opacity 0.3s;
    }

    .logo:hover {
        transform: scale(1.1);
        /* Agrandissement léger */
        opacity: 0.8;
        /* Réduction de l'opacité */
    }
</style>

<body class="hold-transition sidebar-mini">
    <div class="container">
        <!-- Page Header and Form Section -->
        <div class="row">
            <!-- Left Column for Header and Text -->
            <div class="col-md-6 text-left logo">
                <img src="{{ asset('/dist/img/logo-epal.png') }}" style="margin-left:70px;">
                <div class="text-logo">
                    <h3 style="color: blue; text-align: left; margin-top: 20px;">
                        Service de Paiement en Ligne des Factures
                    </h3>
                </div>
            </div>


            <!-- Right Column for Form -->
            <div class="col-md-6">
                <div class="card p-4">
                    <!-- Placeholder for Form Content -->
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</body>

</html>

<!-- jQuery -->
<script src="{{ asset('../../plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('../../plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- bs-custom-file-input -->
<script src="{{ asset('../../plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('../../dist/js/adminlte.min.js') }}"></script>
<!-- AdminLTE for demo purposes -->
<script src="{{ asset('../../dist/js/demo.js') }}"></script>
<!-- Page specific script -->
<script>
    $(function () {
  bsCustomFileInput.init();
});


</script>

@yield('script-footer')