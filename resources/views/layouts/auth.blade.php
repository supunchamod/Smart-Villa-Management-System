<!doctype html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Dashora Admin Dashboard')</title>
  <meta name="description" content="Dashora premium responsive HTML admin dashboard template.">
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg') }}">
  <link rel="shortcut icon" href="{{ asset('assets/img/favicon.svg') }}">
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
  @stack('styles')
</head>
<body class="auth-layout">
  @yield('content')
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/chart/chart.umd.min.js') }}"></script>
  @stack('scripts')
  <script src="{{ asset('assets/js/main.js') }}"></script>
</body>
</html>
