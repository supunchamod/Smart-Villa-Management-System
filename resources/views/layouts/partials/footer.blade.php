<footer class="dashboard-footer" id="dashboardFooter">
        <p>&copy; <span data-current-year>2026</span> All rights reserved.</p>
        <nav aria-label="Footer navigation">
          <a href="index.html">Home</a>
          <a href="faq.html">FAQ</a>
          <a href="mailto:{{ $globalSettings->email ?? 'support@' . request()->getHost() }}">Support</a>
        </nav>
      </footer>
