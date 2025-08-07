<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>e-Paiement | EPAL</title>

  <!-- Google Font: Poppins -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,600&display=fallback">

  @yield('script-header')

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f5f6fa;
      color: #333;
      margin: 0;
      padding: 0;
    }

    .content-wrapper {
      padding: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .user-avatar {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      margin-right: 8px;
    }

    .user-name {
      font-weight: bold;
      color: white;
    }

    .dropdown-menu {
      width: 200px;
      border: 1px solid #ccc;
      border-radius: 4px;
      background: white;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .dropdown-item {
      padding: 10px 15px;
      text-decoration: none;
      display: block;
      color: #333;
    }

    .dropdown-item.bg-warning {
      background-color: red;
      color: white;
    }

    .dropdown-item.bg-warning:hover {
      background-color: darkred;
    }

    /* Simple responsive helper */
    @media (max-width: 768px) {
      .content-wrapper {
        padding: 15px;
      }
    }
  </style>
</head>

<body>
  <div class="wrapper">

    <!-- Content Wrapper -->
    <div class="content-wrapper">
      @yield('content')
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
  @yield('script-footer')
</body>

</html>
