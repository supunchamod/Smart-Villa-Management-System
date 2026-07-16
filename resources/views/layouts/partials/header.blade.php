@php
    $openSection = match (true) {
        request()->routeIs('dashboard') => 'dashboards',
        request()->routeIs(['projects.index', 'file-manager', 'calendar', 'chat', 'inbox']) => 'apps',
        request()->routeIs(['customers.index', 'customers.show', 'products.index', 'invoices.index', 'invoices.show']) => 'commerce',
        request()->routeIs(['settings', 'team']) => 'pages',
        request()->routeIs(['login', 'register']) => 'auth',
        default => null,
    };
@endphp
<header class="topbar">
        <button class="icon-btn d-lg-none" data-sidebar-toggle aria-label="Open menu"><i class="bi bi-list"></i></button>
        <button class="search command-search-trigger" type="button" data-command-search-open aria-label="Open search"><i class="bi bi-search"></i><span>Search</span><kbd>CTRL + K</kbd></button>

        <div class="topbar-actions">
          <div class="layout-dropdown-wrap">
            <button class="layout-btn" data-bs-toggle="dropdown" data-bs-display="static" aria-label="Layout switcher"><i class="bi bi-layout-three-columns"></i><span>Layout</span><i class="bi bi-chevron-down"></i></button>
            <div class="dropdown-menu soft-dropdown layout-menu">
            <button class="dropdown-item" type="button" data-layout-option="default"><i class="bi bi-layout-sidebar"></i> Default</button>
            <button class="dropdown-item" type="button" data-layout-option="horizontal"><i class="bi bi-layout-text-window-reverse"></i> Horizontal</button>
            <button class="dropdown-item" type="button" data-layout-option="detached"><i class="bi bi-window-sidebar"></i> Detached</button>
            <button class="dropdown-item" type="button" data-layout-option="two-column"><i class="bi bi-layout-split"></i> Two Column</button>
            <button class="dropdown-item" type="button" data-layout-option="hovered"><i class="bi bi-layout-sidebar-inset"></i> Hovered</button>
            </div>
          </div>
          <button class="icon-btn" data-dir-toggle aria-label="Toggle RTL"><i class="bi bi-translate"></i></button>
          <button class="icon-btn" data-theme-toggle aria-label="Toggle theme"><i class="bi bi-moon-stars"></i></button>
          <button class="icon-btn position-relative" data-bs-toggle="dropdown" data-bs-display="static" aria-label="Notifications"><i class="bi bi-bell"></i><span class="pulse-dot"></span></button>
          <div class="dropdown-menu dropdown-menu-end soft-dropdown notification-menu">
            <div class="notification-head"><h3>Notification</h3><span>8 New</span><a href="{{ route('inbox') }}" aria-label="Open inbox"><i class="bi bi-envelope"></i></a></div>
            <div class="notification-list">
              <a class="notification-item unread" href="notifications.html"><span class="notify-avatar photo has-photo"><img src="{{ asset('assets/img/team/team-2.jpg') }}" alt="Sara Ahmed"></span><div><strong>Congratulation Lettie</strong><p>Won the monthly best seller gold badge</p><small>1h ago</small></div><i></i></a>
              <a class="notification-item unread" href="{{ route('customers.index') }}"><span class="notify-avatar initials has-photo"><img src="{{ asset('assets/img/team/team-6.jpg') }}" alt="Charles Franklin"></span><div><strong>Charles Franklin</strong><p>Accepted your connection</p><small>12hr ago</small></div><i></i></a>
              <a class="notification-item" href="{{ route('inbox') }}"><span class="notify-avatar photo alt has-photo"><img src="{{ asset('assets/img/team/team-5.jpg') }}" alt="Natalie Reed"></span><div><strong>New Message</strong><p>You have new message from Natalie</p><small>1h ago</small></div></a>
              <a class="notification-item unread" href="orders.html"><span class="notify-avatar icon"><i class="bi bi-cart-check"></i></span><div><strong>Whoo! You have new order</strong><p>ACME Inc. made new order $1,154</p><small>1 day ago</small></div><i></i></a>
            </div>
            <a class="notification-action" href="notifications.html">View all notifications</a>
          </div>
          <button class="profile-trigger" data-bs-toggle="dropdown" data-bs-display="static" aria-label="Account menu"><span class="profile-photo has-photo"><img src="{{ asset('assets/img/team/team-1.jpg') }}" alt="John Doe"><i></i></span></button>
          <div class="dropdown-menu dropdown-menu-end soft-dropdown profile-menu">
            <div class="profile-menu-head"><span class="profile-photo lg has-photo"><img src="{{ asset('assets/img/team/team-1.jpg') }}" alt="John Doe"><i></i></span><div><h3>John Doe</h3><p>Admin</p></div></div>
            <div class="profile-menu-list">
              <a href="profile.html"><i class="bi bi-person"></i><span>My Profile</span></a>
              <a href="{{ route('settings') }}"><i class="bi bi-gear"></i><span>Settings</span></a>
              <a href="{{ route('invoices.index') }}"><i class="bi bi-file-earmark-dollar"></i><span>Billing</span><em>4</em></a>
            </div>
            <div class="profile-menu-list secondary">
              <a href="pricing.html"><i class="bi bi-currency-dollar"></i><span>Pricing</span></a>
              <a href="faq.html"><i class="bi bi-question-lg"></i><span>FAQ</span></a>
            </div>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="profile-logout">Logout <i class="bi bi-box-arrow-right"></i></button>
            </form>
          </div>
        </div>
      </header>
      <nav class="layout-nav-horizontal" aria-label="Horizontal navigation">
    <div class="horizontal-dropdown">
        <button class="nav-link {{ $openSection === 'dashboards' ? 'active' : '' }}" type="button" data-bs-toggle="dropdown" data-bs-display="static"><i class="bi bi-speedometer2"></i><span>Dashboards</span><i class="bi bi-chevron-down"></i></button>
        <div class="dropdown-menu soft-dropdown horizontal-menu">
          <a class="dropdown-item " href="index.html"><i class="bi bi-grid-1x2"></i>Default</a><a class="dropdown-item " href="analytics.html"><i class="bi bi-bar-chart-line"></i>Analytics</a><a class="dropdown-item " href="ecommerce.html"><i class="bi bi-bag-check"></i>eCommerce</a><a class="dropdown-item " href="crm.html"><i class="bi bi-diagram-3"></i>CRM</a><a class="dropdown-item " href="dashboard-crypto.html"><i class="bi bi-currency-bitcoin"></i>Crypto</a><a class="dropdown-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i>Projects</a><a class="dropdown-item " href="dashboard-job.html"><i class="bi bi-briefcase"></i>Job</a>

        </div>
      </div><div class="horizontal-dropdown">
        <button class="nav-link " type="button" data-bs-toggle="dropdown" data-bs-display="static"><i class="bi bi-layout-text-window-reverse"></i><span>Layouts</span><i class="bi bi-chevron-down"></i></button>
        <div class="dropdown-menu soft-dropdown horizontal-menu">
          <a class="dropdown-item " href="layouts.html"><i class="bi bi-layout-text-window-reverse"></i>Layout Options</a>
          <button class="dropdown-item" type="button" data-layout-option="default"><i class="bi bi-layout-sidebar"></i>Default</button><button class="dropdown-item" type="button" data-layout-option="detached"><i class="bi bi-window-sidebar"></i>Detached</button><button class="dropdown-item" type="button" data-layout-option="two-column"><i class="bi bi-layout-split"></i>Two Column</button><button class="dropdown-item" type="button" data-layout-option="hovered"><i class="bi bi-layout-sidebar-inset"></i>Hovered</button>
        </div>
      </div><div class="horizontal-dropdown">
        <button class="nav-link {{ $openSection === 'apps' ? 'active' : '' }}" type="button" data-bs-toggle="dropdown" data-bs-display="static"><i class="bi bi-grid"></i><span>Apps</span><i class="bi bi-chevron-down"></i></button>
        <div class="dropdown-menu soft-dropdown horizontal-menu">
          <a class="dropdown-item {{ request()->routeIs('projects.index') ? 'active' : '' }}" href="{{ route('projects.index') }}"><i class="bi bi-kanban"></i>Projects</a><a class="dropdown-item " href="tasks.html"><i class="bi bi-check2-square"></i>Tasks</a><a class="dropdown-item " href="kanban.html"><i class="bi bi-columns-gap"></i>Kanban</a><a class="dropdown-item {{ request()->routeIs('file-manager') ? 'active' : '' }}" href="{{ route('file-manager') }}"><i class="bi bi-folder2-open"></i>File Manager</a><a class="dropdown-item {{ request()->routeIs('calendar') ? 'active' : '' }}" href="{{ route('calendar') }}"><i class="bi bi-calendar3"></i>Calendar</a><a class="dropdown-item {{ request()->routeIs('chat') ? 'active' : '' }}" href="{{ route('chat') }}"><i class="bi bi-chat-dots"></i>Chat</a><a class="dropdown-item {{ request()->routeIs('inbox') ? 'active' : '' }}" href="{{ route('inbox') }}"><i class="bi bi-inbox"></i>Inbox</a><a class="dropdown-item " href="comments.html"><i class="bi bi-chat-left-text"></i>Comments</a>

        </div>
      </div><div class="horizontal-dropdown">
        <button class="nav-link {{ $openSection === 'commerce' ? 'active' : '' }}" type="button" data-bs-toggle="dropdown" data-bs-display="static"><i class="bi bi-bag"></i><span>Commerce</span><i class="bi bi-chevron-down"></i></button>
        <div class="dropdown-menu soft-dropdown horizontal-menu">
          <a class="dropdown-item {{ request()->routeIs('customers.index') ? 'active' : '' }}" href="{{ route('customers.index') }}"><i class="bi bi-people"></i>Customers</a><a class="dropdown-item {{ request()->routeIs('customers.show') ? 'active' : '' }}" href="{{ route('customers.show') }}"><i class="bi bi-person-vcard"></i>Customer Details</a><a class="dropdown-item {{ request()->routeIs('products.index') ? 'active' : '' }}" href="{{ route('products.index') }}"><i class="bi bi-box-seam"></i>Products</a><a class="dropdown-item " href="orders.html"><i class="bi bi-receipt"></i>Orders</a><a class="dropdown-item {{ request()->routeIs('invoices.index') ? 'active' : '' }}" href="{{ route('invoices.index') }}"><i class="bi bi-file-earmark-text"></i>Invoice List</a><a class="dropdown-item {{ request()->routeIs('invoices.show') ? 'active' : '' }}" href="{{ route('invoices.show') }}"><i class="bi bi-file-richtext"></i>Invoice Details</a>

        </div>
      </div><div class="horizontal-dropdown">
        <button class="nav-link {{ $openSection === 'pages' ? 'active' : '' }}" type="button" data-bs-toggle="dropdown" data-bs-display="static"><i class="bi bi-layers"></i><span>Pages</span><i class="bi bi-chevron-down"></i></button>
        <div class="dropdown-menu soft-dropdown horizontal-menu">
          <a class="dropdown-item " href="pricing.html"><i class="bi bi-tags"></i>Pricing</a><a class="dropdown-item " href="profile.html"><i class="bi bi-person-circle"></i>Profile</a><a class="dropdown-item {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}"><i class="bi bi-gear"></i>Settings</a><a class="dropdown-item {{ request()->routeIs('team') ? 'active' : '' }}" href="{{ route('team') }}"><i class="bi bi-person-workspace"></i>Team</a><a class="dropdown-item " href="notifications.html"><i class="bi bi-bell"></i>Notifications</a><a class="dropdown-item {{ request()->routeIs('reports') ? 'active' : '' }}" href="{{ route('reports') }}"><i class="bi bi-clipboard-data"></i>Reports</a><a class="dropdown-item " href="faq.html"><i class="bi bi-question-circle"></i>FAQ</a>

        </div>
      </div><div class="horizontal-dropdown">
        <button class="nav-link {{ $openSection === 'auth' ? 'active' : '' }}" type="button" data-bs-toggle="dropdown" data-bs-display="static"><i class="bi bi-shield-lock"></i><span>Auth</span><i class="bi bi-chevron-down"></i></button>
        <div class="dropdown-menu soft-dropdown horizontal-menu">
          <a class="dropdown-item {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i>Login</a><a class="dropdown-item {{ request()->routeIs('register') ? 'active' : '' }}" href="{{ route('register') }}"><i class="bi bi-person-plus"></i>Register</a><a class="dropdown-item " href="forgot-password.html"><i class="bi bi-key"></i>Forgot Password</a><a class="dropdown-item " href="404.html"><i class="bi bi-exclamation-diamond"></i>404</a><a class="dropdown-item " href="coming-soon.html"><i class="bi bi-hourglass-split"></i>Coming Soon</a>

        </div>
      </div>
  </nav>
