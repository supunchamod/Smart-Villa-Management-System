@php
    $openSection = match (true) {
        request()->routeIs('dashboard') => 'dashboards',
        request()->routeIs(['projects.index', 'file-manager', 'calendar', 'chat', 'inbox']) => 'apps',
        request()->routeIs(['customers.index', 'customers.show', 'products.index', 'invoices.index', 'invoices.show']) => 'commerce',
        request()->routeIs(['settings', 'team', 'reports']) => 'pages',
        request()->routeIs(['login', 'register']) => 'auth',
        default => null,
    };
@endphp
<aside class="mini-rail" aria-label="Quick navigation">
    <a href="index.html" aria-label="Dashboard"><i class="bi bi-grid-1x2"></i></a>
    <a href="analytics.html" aria-label="Analytics"><i class="bi bi-bar-chart-line"></i></a>
    <a href="{{ route('projects.index') }}" aria-label="Projects"><i class="bi bi-kanban"></i></a>
    <a href="{{ route('customers.index') }}" aria-label="Customers"><i class="bi bi-people"></i></a>
    <a href="{{ route('settings') }}" aria-label="Settings"><i class="bi bi-gear"></i></a>
  </aside>
    <aside class="sidebar" id="sidebar">
      <a class="brand" href="index.html" aria-label="Dashora home">
        <span class="brand-mark">D</span>
        <div><strong>Dashora</strong><small>Admin Suite</small></div>
      </a>
      <nav class="sidebar-nav">
        <div class="nav-section nav-accordion {{ $openSection === 'dashboards' ? 'open' : '' }}">
          <button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="{{ $openSection === 'dashboards' ? 'true' : 'false' }}"><span><i class="bi bi-speedometer2"></i>Dashboards</span><i class="bi bi-chevron-down"></i></button>
          <div class="nav-accordion-panel">
            <a class="nav-link " href="index.html"><i class="bi bi-grid-1x2"></i><span>Default</span></a>
            <a class="nav-link " href="analytics.html"><i class="bi bi-bar-chart-line"></i><span>Analytics</span></a>
            <a class="nav-link " href="ecommerce.html"><i class="bi bi-bag-check"></i><span>eCommerce</span></a>
            <a class="nav-link " href="crm.html"><i class="bi bi-diagram-3"></i><span>CRM</span></a>
            <a class="nav-link " href="dashboard-crypto.html"><i class="bi bi-currency-bitcoin"></i><span>Crypto</span></a>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i><span>Projects</span></a>
            <a class="nav-link " href="dashboard-job.html"><i class="bi bi-briefcase"></i><span>Job</span></a>

          </div>
        </div>

        <div class="nav-section nav-accordion ">
          <button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="false"><span><i class="bi bi-layout-text-window-reverse"></i>Layouts</span><i class="bi bi-chevron-down"></i></button>
          <div class="nav-accordion-panel">
            <a class="nav-link " href="layouts.html"><i class="bi bi-layout-text-window-reverse"></i><span>Layout Options</span></a>
            <div class="sidebar-layout-switch">
    <span>Layouts</span>
    <button type="button" data-layout-option="default">Default</button>
    <button type="button" data-layout-option="horizontal">Horizontal</button>
    <button type="button" data-layout-option="detached">Detached</button>
    <button type="button" data-layout-option="two-column">Two Column</button>
    <button type="button" data-layout-option="hovered">Hovered</button>
  </div>
          </div>
        </div>

        <div class="nav-section nav-accordion {{ $openSection === 'apps' ? 'open' : '' }}">
          <button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="{{ $openSection === 'apps' ? 'true' : 'false' }}"><span><i class="bi bi-grid"></i>Apps</span><i class="bi bi-chevron-down"></i></button>
          <div class="nav-accordion-panel">
            <a class="nav-link {{ request()->routeIs('projects.index') ? 'active' : '' }}" href="{{ route('projects.index') }}"><i class="bi bi-kanban"></i><span>Projects</span></a>
            <a class="nav-link " href="tasks.html"><i class="bi bi-check2-square"></i><span>Tasks</span></a>
            <a class="nav-link " href="kanban.html"><i class="bi bi-columns-gap"></i><span>Kanban</span></a>
            <a class="nav-link {{ request()->routeIs('file-manager') ? 'active' : '' }}" href="{{ route('file-manager') }}"><i class="bi bi-folder2-open"></i><span>File Manager</span></a>
            <a class="nav-link {{ request()->routeIs('calendar') ? 'active' : '' }}" href="{{ route('calendar') }}"><i class="bi bi-calendar3"></i><span>Calendar</span></a>
            <a class="nav-link {{ request()->routeIs('chat') ? 'active' : '' }}" href="{{ route('chat') }}"><i class="bi bi-chat-dots"></i><span>Chat</span></a>
            <a class="nav-link {{ request()->routeIs('inbox') ? 'active' : '' }}" href="{{ route('inbox') }}"><i class="bi bi-inbox"></i><span>Inbox</span></a>
            <a class="nav-link " href="comments.html"><i class="bi bi-chat-left-text"></i><span>Comments</span></a>

          </div>
        </div>

        <div class="nav-section nav-accordion {{ $openSection === 'commerce' ? 'open' : '' }}">
          <button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="{{ $openSection === 'commerce' ? 'true' : 'false' }}"><span><i class="bi bi-bag"></i>Commerce</span><i class="bi bi-chevron-down"></i></button>
          <div class="nav-accordion-panel">
            <a class="nav-link {{ request()->routeIs('customers.index') ? 'active' : '' }}" href="{{ route('customers.index') }}"><i class="bi bi-people"></i><span>Customers</span></a>
            <a class="nav-link {{ request()->routeIs('customers.show') ? 'active' : '' }}" href="{{ route('customers.show') }}"><i class="bi bi-person-vcard"></i><span>Customer Details</span></a>
            <a class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}" href="{{ route('products.index') }}"><i class="bi bi-box-seam"></i><span>Products</span></a>
            <a class="nav-link " href="orders.html"><i class="bi bi-receipt"></i><span>Orders</span></a>
            <a class="nav-link {{ request()->routeIs('invoices.index') ? 'active' : '' }}" href="{{ route('invoices.index') }}"><i class="bi bi-file-earmark-text"></i><span>Invoice List</span></a>
            <a class="nav-link {{ request()->routeIs('invoices.show') ? 'active' : '' }}" href="{{ route('invoices.show') }}"><i class="bi bi-file-richtext"></i><span>Invoice Details</span></a>

          </div>
        </div>

        <div class="nav-section nav-accordion {{ $openSection === 'pages' ? 'open' : '' }}">
          <button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="{{ $openSection === 'pages' ? 'true' : 'false' }}"><span><i class="bi bi-layers"></i>Pages</span><i class="bi bi-chevron-down"></i></button>
          <div class="nav-accordion-panel">
            <a class="nav-link " href="pricing.html"><i class="bi bi-tags"></i><span>Pricing</span></a>
            <a class="nav-link " href="profile.html"><i class="bi bi-person-circle"></i><span>Profile</span></a>
            <a class="nav-link {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}"><i class="bi bi-gear"></i><span>Settings</span></a>
            <a class="nav-link {{ request()->routeIs('team') ? 'active' : '' }}" href="{{ route('team') }}"><i class="bi bi-person-workspace"></i><span>Team</span></a>
            <a class="nav-link " href="notifications.html"><i class="bi bi-bell"></i><span>Notifications</span></a>
            <a class="nav-link {{ request()->routeIs('reports') ? 'active' : '' }}" href="{{ route('reports') }}"><i class="bi bi-clipboard-data"></i><span>Reports</span></a>
            <a class="nav-link " href="faq.html"><i class="bi bi-question-circle"></i><span>FAQ</span></a>

          </div>
        </div>

        <div class="nav-section nav-accordion {{ $openSection === 'auth' ? 'open' : '' }}">
          <button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="{{ $openSection === 'auth' ? 'true' : 'false' }}"><span><i class="bi bi-shield-lock"></i>Auth</span><i class="bi bi-chevron-down"></i></button>
          <div class="nav-accordion-panel">
            <a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i><span>Login</span></a>
            <a class="nav-link {{ request()->routeIs('register') ? 'active' : '' }}" href="{{ route('register') }}"><i class="bi bi-person-plus"></i><span>Register</span></a>
            <a class="nav-link " href="forgot-password.html"><i class="bi bi-key"></i><span>Forgot Password</span></a>
            <a class="nav-link " href="404.html"><i class="bi bi-exclamation-diamond"></i><span>404</span></a>
            <a class="nav-link " href="coming-soon.html"><i class="bi bi-hourglass-split"></i><span>Coming Soon</span></a>

          </div>
        </div></nav>
    </aside>
