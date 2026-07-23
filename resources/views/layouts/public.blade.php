<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Book Direct')</title>
  <meta name="description" content="@yield('meta_description', 'Book your stay direct - the best rate, guaranteed.')">
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/public-villa.css') }}" rel="stylesheet">
  @stack('styles')
</head>
<body class="pv-body">
  @yield('content')
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script defer src="{{ asset('assets/vendor/alpinejs/alpine.min.js') }}"></script>
  @stack('scripts')
</body>
</html>
