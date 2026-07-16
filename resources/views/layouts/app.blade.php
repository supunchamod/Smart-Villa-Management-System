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
<body class="app-layout">
  @include('layouts.partials.sidebar')
    <div class="sidebar-backdrop" data-sidebar-close></div>
    <main class="main">
      @include('layouts.partials.header')
      <section class="content">
        @yield('content')
      </section>
      @include('layouts.partials.footer')
    </main>
  <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content dash-modal">
        <div class="modal-header">
          <div><span class="eyebrow">Quick Action</span><h2 class="modal-title" id="createModalTitle">Create Item</h2></div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="createForm">
          <div class="modal-body">
            <div class="row g-3" id="createFields"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="actionToast" class="toast dash-toast" role="status" aria-live="polite" aria-atomic="true">
      <div class="toast-header"><strong class="me-auto">Dashora</strong><small>Now</small><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div>
      <div class="toast-body">Action saved in this static demo.</div>
    </div>
  </div>
  <div class="modal fade command-modal" id="commandSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content command-panel">
        <div class="command-head">
          <i class="bi bi-search"></i>
          <input type="search" data-command-search-input placeholder="Search [CTRL + K]" aria-label="Search pages">
          <span>[esc]</span>
          <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="command-body">
          <section class="command-group"><h3>Popular Searches</h3><div class="command-list"><a href="analytics.html" data-command-item data-search-text="analytics"><i class="bi bi-bar-chart-line"></i><span>Analytics</span></a><a href="crm.html" data-command-item data-search-text="crm"><i class="bi bi-diagram-3"></i><span>CRM</span></a><a href="ecommerce.html" data-command-item data-search-text="ecommerce"><i class="bi bi-bag-check"></i><span>eCommerce</span></a><a href="{{ route('customers.index') }}" data-command-item data-search-text="customers"><i class="bi bi-people"></i><span>Customers</span></a></div></section><section class="command-group"><h3>Apps &amp; Pages</h3><div class="command-list"><a href="{{ route('calendar') }}" data-command-item data-search-text="calendar"><i class="bi bi-calendar3"></i><span>Calendar</span></a><a href="{{ route('file-manager') }}" data-command-item data-search-text="file manager files folders storage"><i class="bi bi-folder2-open"></i><span>File Manager</span></a><a href="kanban.html" data-command-item data-search-text="kanban board workflow cards"><i class="bi bi-columns-gap"></i><span>Kanban</span></a><a href="{{ route('invoices.index') }}" data-command-item data-search-text="invoice list"><i class="bi bi-list-ol"></i><span>Invoice List</span></a><a href="{{ route('settings') }}" data-command-item data-search-text="account settings"><i class="bi bi-person-gear"></i><span>Account Settings</span></a><a href="orders.html" data-command-item data-search-text="orders"><i class="bi bi-receipt"></i><span>Orders</span></a></div></section><section class="command-group"><h3>User Interface</h3><div class="command-list"><a href="layouts.html" data-command-item data-search-text="layout options"><i class="bi bi-layout-text-window-reverse"></i><span>Layout Options</span></a><a href="pricing.html" data-command-item data-search-text="pricing cards"><i class="bi bi-tags"></i><span>Pricing Cards</span></a><a href="notifications.html" data-command-item data-search-text="notifications"><i class="bi bi-bell"></i><span>Notifications</span></a><a href="faq.html" data-command-item data-search-text="faq"><i class="bi bi-question-circle"></i><span>FAQ</span></a></div></section><section class="command-group"><h3>Dashboards</h3><div class="command-list"><a href="index.html" data-command-item data-search-text="default dashboard"><i class="bi bi-grid-1x2"></i><span>Default Dashboard</span></a><a href="{{ route('dashboard') }}" data-command-item data-search-text="project dashboard"><i class="bi bi-speedometer2"></i><span>Project Dashboard</span></a><a href="dashboard-crypto.html" data-command-item data-search-text="crypto dashboard"><i class="bi bi-currency-bitcoin"></i><span>Crypto Dashboard</span></a><a href="dashboard-job.html" data-command-item data-search-text="job dashboard"><i class="bi bi-briefcase"></i><span>Job Dashboard</span></a></div></section>
          <div class="command-empty" data-command-empty hidden><i class="bi bi-search"></i><h3>No results found</h3><p>Try searching dashboards, customers, orders, invoices, or settings.</p></div>
        </div>
      </div>
    </div>
  </div>
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/chart/chart.umd.min.js') }}"></script>
  @stack('scripts')
  <script src="{{ asset('assets/js/main.js') }}"></script>
</body>
</html>
