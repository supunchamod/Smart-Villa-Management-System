(() => {
  document.querySelectorAll('[data-current-year]').forEach((year) => {
    year.textContent = new Date().getFullYear();
  });

  const storage = {
    get(key) {
      try {
        return window.localStorage.getItem(key);
      } catch (error) {
        return null;
      }
    },
    set(key, value) {
      try {
        window.localStorage.setItem(key, value);
      } catch (error) {
        return false;
      }
      return true;
    }
  };
  const storedTheme = storage.get('dashora-theme');
  if (storedTheme) document.documentElement.dataset.theme = storedTheme;
  const storedDir = storage.get('dashora-dir');
  if (storedDir) {
    document.documentElement.setAttribute('dir', storedDir);
    document.documentElement.setAttribute('lang', storedDir === 'rtl' ? 'ar' : 'en');
  }
  const storedLayout = storage.get('dashora-layout') || 'default';
  document.body.dataset.layout = storedLayout;

  document.querySelectorAll('.topbar').forEach((topbar) => {
    if (topbar.querySelector('.topbar-brand')) return;
    const brand = document.createElement('a');
    brand.className = 'topbar-brand';
    brand.href = 'index.html';
    brand.setAttribute('aria-label', 'Dashora dashboard');
    brand.innerHTML = '<span class="brand-mark">D</span>';
    const search = topbar.querySelector('.command-search-trigger');
    const sidebarToggle = topbar.querySelector('[data-sidebar-toggle]');
    topbar.insertBefore(brand, sidebarToggle || search || topbar.firstChild);
  });

  if (location.pathname.endsWith('.html')) {
  const componentItems = [
    ['accordion', 'Accordion', 'bi-chevron-bar-expand'],
    ['alerts', 'Alerts', 'bi-exclamation-triangle'],
    ['badge', 'Badge', 'bi-patch-check'],
    ['breadcrumb', 'Breadcrumb', 'bi-signpost-split'],
    ['buttons', 'Buttons', 'bi-square'],
    ['typography', 'Typography', 'bi-fonts'],
    ['button-group', 'Button Group', 'bi-segmented-nav'],
    ['card', 'Card', 'bi-card-text'],
    ['collapse', 'Collapse', 'bi-arrows-collapse'],
    ['carousel', 'Carousel', 'bi-images'],
    ['dropdowns', 'Dropdowns', 'bi-menu-button-wide'],
    ['modal', 'Modal', 'bi-window'],
    ['navbar', 'Navbar', 'bi-layout-text-sidebar-reverse'],
    ['list-group', 'List Group', 'bi-list-ul'],
    ['tabs', 'Tabs', 'bi-tabs'],
    ['offcanvas', 'Offcanvas', 'bi-layout-sidebar-reverse'],
    ['pagination', 'Pagination', 'bi-three-dots'],
    ['popovers', 'Popovers', 'bi-chat-square-text'],
    ['progress', 'Progress', 'bi-bar-chart-steps'],
    ['scrollspy', 'Scrollspy', 'bi-compass'],
    ['spinners', 'Spinners', 'bi-arrow-repeat'],
    ['toasts', 'Toasts', 'bi-app-indicator'],
    ['tooltips', 'Tooltips', 'bi-info-circle']
  ];
  const componentSidebarLinks = componentItems.map(([id, label, icon]) =>
    `<a class="nav-link" href="component-%24%7bid%7d.html"><i class="bi ${icon}"></i><span>${label}</span></a>`
  ).join('');
  const componentHorizontalLinks = componentItems.map(([id, label, icon]) =>
    `<a class="dropdown-item" href="component-%24%7bid%7d.html"><i class="bi ${icon}"></i>${label}</a>`
  ).join('');
  const sidebarNav = document.querySelector('.sidebar-nav');
  if (sidebarNav && !sidebarNav.querySelector('[data-components-nav]')) {
    const section = document.createElement('div');
    section.className = 'nav-section nav-accordion';
    section.dataset.componentsNav = '';
    section.innerHTML = `<button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="false"><span><i class="bi bi-boxes"></i>Components</span><i class="bi bi-chevron-down"></i></button><div class="nav-accordion-panel">${componentSidebarLinks}</div>`;
    const pagesSection = Array.from(sidebarNav.querySelectorAll('.nav-section')).find((item) =>
      item.querySelector('.nav-accordion-toggle')?.textContent.includes('Pages')
    );
    sidebarNav.insertBefore(section, pagesSection || null);
  }
  const horizontalNav = document.querySelector('.layout-nav-horizontal');
  if (horizontalNav && !horizontalNav.querySelector('[data-components-horizontal]')) {
    const wrap = document.createElement('div');
    wrap.className = 'horizontal-dropdown';
    wrap.dataset.componentsHorizontal = '';
    wrap.innerHTML = `<button class="nav-link" type="button" data-bs-toggle="dropdown" data-bs-display="static"><i class="bi bi-boxes"></i><span>Components</span><i class="bi bi-chevron-down"></i></button><div class="dropdown-menu soft-dropdown horizontal-menu component-nav-menu">${componentHorizontalLinks}</div>`;
    const pagesWrap = Array.from(horizontalNav.querySelectorAll('.horizontal-dropdown')).find((item) =>
      item.querySelector(':scope > .nav-link span')?.textContent === 'Pages'
    );
    horizontalNav.insertBefore(wrap, pagesWrap || null);
  }
  const commandUiGroup = Array.from(document.querySelectorAll('.command-group')).find((group) =>
    group.querySelector('h3')?.textContent === 'User Interface'
  );
  const commandUiList = commandUiGroup?.querySelector('.command-list');
  if (commandUiList && !commandUiList.querySelector('[data-components-command]')) {
    commandUiList.insertAdjacentHTML('beforeend', '<a href="components.html" data-components-command data-command-item data-search-text="components accordion alerts badge breadcrumb buttons typography cards modal tabs"><i class="bi bi-boxes"></i><span>Components</span></a>');
  }
  const componentPageMatch = location.pathname.match(/component-([a-z-]+)\.html$/);
  if (location.pathname.endsWith('https://html.themewant.com/components.html') || location.pathname.endsWith('components.html') || componentPageMatch) {
    document.querySelectorAll('.sidebar-nav .nav-accordion').forEach((item) => {
      item.classList.remove('open');
      item.querySelector('[data-nav-accordion]')?.setAttribute('aria-expanded', 'false');
      item.querySelectorAll('.nav-link').forEach((link) => link.classList.remove('active'));
    });
    const componentSection = document.querySelector('[data-components-nav]');
    componentSection?.classList.add('open');
    componentSection?.querySelector('[data-nav-accordion]')?.setAttribute('aria-expanded', 'true');
    document.querySelectorAll('.layout-nav-horizontal > .horizontal-dropdown > .nav-link').forEach((link) => link.classList.remove('active'));
    document.querySelector('[data-components-horizontal] > .nav-link')?.classList.add('active');
    const activeFile = componentPageMatch ? `component-${componentPageMatch[1]}.html` : '';
    document.querySelectorAll('[data-components-nav] .nav-link, [data-components-horizontal] .dropdown-item').forEach((link) => {
      link.classList.toggle('active', activeFile && link.getAttribute('href') === activeFile);
    });
  }
  }

  document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((trigger) => {
    if (window.bootstrap) bootstrap.Dropdown.getOrCreateInstance(trigger, {
      display: 'static',
      popperConfig: null
    });
  });

  document.querySelectorAll('.horizontal-dropdown').forEach((wrap) => {
    const trigger = wrap.querySelector('[data-bs-toggle="dropdown"]');
    if (!trigger || !window.bootstrap) return;
    const dropdown = bootstrap.Dropdown.getOrCreateInstance(trigger, {
      display: 'static',
      popperConfig: null
    });
    let closeTimer;
    const openMenu = () => {
      clearTimeout(closeTimer);
      wrap.classList.add('is-hovering');
      dropdown.show();
    };
    const closeMenu = () => {
      closeTimer = setTimeout(() => {
        wrap.classList.remove('is-hovering');
        dropdown.hide();
      }, 180);
    };
    wrap.addEventListener('mouseenter', openMenu);
    wrap.addEventListener('mouseleave', closeMenu);
    trigger.addEventListener('focus', openMenu);
    trigger.addEventListener('blur', closeMenu);
  });

  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((trigger) => {
    if (window.bootstrap) bootstrap.Tooltip.getOrCreateInstance(trigger);
  });
  document.querySelectorAll('[data-bs-toggle="popover"]').forEach((trigger) => {
    if (window.bootstrap) bootstrap.Popover.getOrCreateInstance(trigger);
  });
  document.querySelectorAll('[data-bs-spy="scroll"]').forEach((element) => {
    if (window.bootstrap) bootstrap.ScrollSpy.getOrCreateInstance(element);
  });
  const componentToastEl = document.getElementById('componentToast');
  const componentToast = componentToastEl && window.bootstrap ? bootstrap.Toast.getOrCreateInstance(componentToastEl) : null;
  document.querySelector('[data-component-toast]')?.addEventListener('click', () => componentToast?.show());

  document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      document.documentElement.dataset.theme = next;
      storage.set('dashora-theme', next);
    });
  });

  document.querySelectorAll('[data-dir-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('dir') === 'rtl' ? 'rtl' : 'ltr';
      const next = current === 'rtl' ? 'ltr' : 'rtl';
      document.documentElement.setAttribute('dir', next);
      document.documentElement.setAttribute('lang', next === 'rtl' ? 'ar' : 'en');
      storage.set('dashora-dir', next);
    });
  });

  const commandModalEl = document.getElementById('commandSearchModal');
  const commandInput = document.querySelector('[data-command-search-input]');
  const commandItems = Array.from(document.querySelectorAll('[data-command-item]'));
  const commandEmpty = document.querySelector('[data-command-empty]');
  const commandModal = commandModalEl && window.bootstrap ? new bootstrap.Modal(commandModalEl) : null;
  const filterCommandItems = () => {
    const query = (commandInput?.value || '').trim().toLowerCase();
    let visibleCount = 0;
    commandItems.forEach((item) => {
      const visible = !query || (item.dataset.searchText || item.textContent || '').toLowerCase().includes(query);
      item.hidden = !visible;
      if (visible) visibleCount += 1;
    });
    document.querySelectorAll('.command-group').forEach((group) => {
      group.hidden = !group.querySelector('[data-command-item]:not([hidden])');
    });
    if (commandEmpty) commandEmpty.hidden = visibleCount !== 0;
  };
  const openCommandSearch = () => {
    commandModal?.show();
    setTimeout(() => commandInput?.focus(), 160);
  };
  document.querySelectorAll('[data-command-search-open]').forEach((button) => {
    button.addEventListener('click', openCommandSearch);
  });
  document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      openCommandSearch();
    }
  });
  commandModalEl?.addEventListener('shown.bs.modal', () => {
    commandInput?.focus();
    commandInput?.select();
  });
  commandModalEl?.addEventListener('hidden.bs.modal', () => {
    if (commandInput) commandInput.value = '';
    filterCommandItems();
  });
  commandInput?.addEventListener('input', filterCommandItems);
  filterCommandItems();

  document.querySelectorAll('[data-local-search]').forEach((input) => {
    const container = document.querySelector(input.dataset.localSearch);
    if (!container) return;
    const items = Array.from(container.querySelectorAll('[data-local-item]'));
    const scope = input.closest('[data-local-scope]') || input.closest('.panel') || input.closest('.content');
    const empty = container.querySelector('[data-local-empty]') || scope?.querySelector('[data-local-empty]');
    const count = scope?.querySelector('[data-local-count]') || input.closest('.content')?.querySelector('[data-local-count]');
    const itemLabel = (count?.textContent || 'items').replace(/^\d+\s*/, '') || 'items';
    const applyLocalSearch = () => {
      const query = input.value.trim().toLowerCase();
      let visible = 0;
      items.forEach((item) => {
        const text = (item.dataset.searchText || item.textContent || '').toLowerCase();
        const match = !query || text.includes(query);
        item.hidden = !match;
        if (match) visible += 1;
      });
      if (empty) empty.hidden = visible !== 0;
      if (count) count.textContent = `${visible} ${itemLabel}`;
    };
    input.addEventListener('input', applyLocalSearch);
    applyLocalSearch();
  });

  const syncLayoutButtons = () => {
    document.querySelectorAll('[data-layout-option]').forEach((button) => {
      button.classList.toggle('active', button.dataset.layoutOption === document.body.dataset.layout);
    });
  };

  document.querySelectorAll('[data-layout-option]').forEach((button) => {
    button.addEventListener('click', () => {
      const layout = button.dataset.layoutOption || 'default';
      document.body.dataset.layout = layout;
      storage.set('dashora-layout', layout);
      syncLayoutButtons();
    });
  });
  syncLayoutButtons();

  document.querySelectorAll('[data-countdown]').forEach((timer) => {
    const target = new Date(timer.dataset.countdown).getTime();
    const nodes = {
      days: timer.querySelector('[data-days]'),
      hours: timer.querySelector('[data-hours]'),
      minutes: timer.querySelector('[data-minutes]'),
      seconds: timer.querySelector('[data-seconds]')
    };
    const pad = (value) => String(value).padStart(2, '0');
    const tick = () => {
      const distance = Math.max(0, target - Date.now());
      const days = Math.floor(distance / 86400000);
      const hours = Math.floor((distance % 86400000) / 3600000);
      const minutes = Math.floor((distance % 3600000) / 60000);
      const seconds = Math.floor((distance % 60000) / 1000);
      if (nodes.days) nodes.days.textContent = pad(days);
      if (nodes.hours) nodes.hours.textContent = pad(hours);
      if (nodes.minutes) nodes.minutes.textContent = pad(minutes);
      if (nodes.seconds) nodes.seconds.textContent = pad(seconds);
    };
    tick();
    setInterval(tick, 1000);
  });

  document.querySelectorAll('[data-nav-accordion]').forEach((button) => {
    button.addEventListener('click', () => {
      const section = button.closest('.nav-accordion');
      const shouldOpen = !section?.classList.contains('open');
      document.querySelectorAll('.nav-accordion').forEach((item) => {
        item.classList.remove('open');
        item.querySelector('[data-nav-accordion]')?.setAttribute('aria-expanded', 'false');
      });
      if (shouldOpen && section) {
        section.classList.add('open');
        button.setAttribute('aria-expanded', 'true');
      }
    });
  });

  const sidebar = document.getElementById('sidebar');
  const backdrop = document.querySelector('[data-sidebar-close]');
  const sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');
  const setSidebarOpen = (isOpen) => {
    sidebar?.classList.toggle('show', isOpen);
    backdrop?.classList.toggle('show', isOpen);
    sidebarToggles.forEach((button) => {
      button.setAttribute('aria-expanded', String(isOpen));
      button.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
      const icon = button.querySelector('i');
      icon?.classList.toggle('bi-list', !isOpen);
      icon?.classList.toggle('bi-x-lg', isOpen);
    });
  };
  sidebarToggles.forEach((button) => {
    button.setAttribute('aria-controls', 'sidebar');
    button.setAttribute('aria-expanded', 'false');
    button.addEventListener('click', () => {
      setSidebarOpen(!sidebar?.classList.contains('show'));
    });
  });
  backdrop?.addEventListener('click', () => setSidebarOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sidebar?.classList.contains('show')) {
      setSidebarOpen(false);
    }
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 992 && sidebar?.classList.contains('show')) {
      setSidebarOpen(false);
    }
  });

  const fieldSets = {
    projects: [
      ['Project name', 'text', 'Launch mobile redesign'],
      ['Owner', 'text', 'Sara Ahmed'],
      ['Due date', 'date', ''],
      ['Budget', 'number', '24000'],
      ['Status', 'select', 'Planning|In Progress|Review|Completed'],
      ['Description', 'textarea', 'Project goals and delivery notes']
    ],
    tasks: [
      ['Task title', 'text', 'Review onboarding flow'],
      ['Assignee', 'text', 'Jon Lee'],
      ['Priority', 'select', 'High|Medium|Low'],
      ['Due date', 'date', '']
    ],
    ecommerce: [
      ['Customer', 'text', 'Nexa Analytics'],
      ['Product', 'text', 'Dashora Pro License'],
      ['Quantity', 'number', '2'],
      ['Channel', 'select', 'Website|Marketplace|Retail|Partner']
    ],
    orders: [
      ['Order ID', 'text', 'DS-1025'],
      ['Customer', 'text', 'CloudPeak'],
      ['Amount', 'number', '1280'],
      ['Status', 'select', 'Paid|Pending|Processing|Refunded']
    ],
    crm: [
      ['Lead name', 'text', 'Apex Studio'],
      ['Contact email', 'email', 'hello@example.com'],
      ['Deal value', 'number', '18000'],
      ['Stage', 'select', 'Discovery|Proposal|Negotiation|Won']
    ],
    customers: [
      ['Customer name', 'text', 'Ariana Reed'],
      ['Company', 'text', 'Acme Studio'],
      ['Email', 'email', 'ariana@example.com'],
      ['Segment', 'select', 'Enterprise|Growth|Startup']
    ],
    products: [
      ['Product name', 'text', 'Analytics Add-on'],
      ['SKU', 'text', 'DS-AN-204'],
      ['Price', 'number', '49'],
      ['Stock status', 'select', 'In Stock|Low Stock|Out of Stock']
    ],
    reports: [
      ['Report name', 'text', 'Executive revenue summary'],
      ['Date range', 'select', 'Last 7 days|Last 30 days|Quarter to date|Year to date'],
      ['Format', 'select', 'PDF|CSV|XLSX'],
      ['Recipients', 'email', 'team@example.com']
    ],
    calendar: [
      ['Event title', 'text', 'Quarterly review'],
      ['Start date', 'date', ''],
      ['Time', 'time', ''],
      ['Attendees', 'text', 'Leadership team']
    ],
    team: [
      ['Member name', 'text', 'Maya Rahman'],
      ['Email', 'email', 'maya@example.com'],
      ['Role', 'select', 'Admin|Manager|Analyst|Viewer'],
      ['Team', 'select', 'Product|Sales|Support|Finance']
    ],
    default: [
      ['Title', 'text', 'New workspace item'],
      ['Owner', 'text', 'Sara Ahmed'],
      ['Status', 'select', 'Draft|Active|Paused|Complete'],
      ['Notes', 'textarea', 'Add useful context for this item']
    ]
  };

  const renderField = ([label, type, value]) => {
    const id = 'field-' + label.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    if (type === 'select') {
      return '<div class="col-md-6"><label class="form-label" for="' + id + '">' + label + '</label><select id="' + id + '" class="form-select">' + value.split('|').map((option) => '<option>' + option + '</option>').join('') + '</select></div>';
    }
    if (type === 'textarea') {
      return '<div class="col-12"><label class="form-label" for="' + id + '">' + label + '</label><textarea id="' + id + '" class="form-control" rows="4" placeholder="' + value + '"></textarea></div>';
    }
    return '<div class="col-md-6"><label class="form-label" for="' + id + '">' + label + '</label><input id="' + id + '" class="form-control" type="' + type + '" placeholder="' + value + '"></div>';
  };

  const modalEl = document.getElementById('createModal');
  const fieldsEl = document.getElementById('createFields');
  const titleEl = document.getElementById('createModalTitle');
  const formEl = document.getElementById('createForm');
  const toastEl = document.getElementById('actionToast');
  const modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;
  const toast = toastEl && window.bootstrap ? new bootstrap.Toast(toastEl) : null;

  document.querySelectorAll('[data-create-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const type = button.dataset.createType || 'default';
      const label = button.dataset.createLabel || button.textContent.trim() || 'Create Item';
      if (titleEl) titleEl.textContent = label;
      if (fieldsEl) fieldsEl.innerHTML = (fieldSets[type] || fieldSets.default).map(renderField).join('');
      formEl?.setAttribute('data-current-action', label);
      modal?.show();
    });
  });

  formEl?.addEventListener('submit', (event) => {
    event.preventDefault();
    const label = formEl.dataset.currentAction || 'Action';
    const body = toastEl?.querySelector('.toast-body');
    if (body) body.textContent = label + ' saved in this static demo.';
    modal?.hide();
    toast?.show();
  });

  document.querySelectorAll('[data-export-page]').forEach((button) => {
    button.addEventListener('click', () => {
      const body = toastEl?.querySelector('.toast-body');
      if (body) body.textContent = 'Export prepared in this static demo.';
      toast?.show();
    });
  });

  document.querySelectorAll('[data-print-page]').forEach((button) => {
    button.addEventListener('click', () => {
      window.print();
    });
  });

  const commentState = { filter: 'all', sort: 'newest' };
  const applyCommentFilters = () => {
    const items = Array.from(document.querySelectorAll('[data-comment-item]'));
    let visibleCount = 0;
    items.forEach((item) => {
      const filterMatch = commentState.filter === 'all' || item.dataset.filter === commentState.filter;
      const sortMatch = commentState.sort === 'newest' || item.dataset.sort === commentState.sort || item.dataset.filter === commentState.sort;
      const visible = filterMatch && sortMatch;
      item.hidden = !visible;
      if (visible) visibleCount += 1;
    });
    const empty = document.querySelector('.comment-empty');
    if (empty) empty.hidden = visibleCount !== 0;
  };

  document.querySelectorAll('[data-comment-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-comment-filter]').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      commentState.filter = button.dataset.commentFilter || 'all';
      applyCommentFilters();
    });
  });

  document.querySelectorAll('[data-comment-sort-option]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-comment-sort-option]').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      commentState.sort = button.dataset.commentSortOption || 'newest';
      applyCommentFilters();
    });
  });

  document.querySelectorAll('[data-comment-reply], [data-comment-approve]').forEach((button) => {
    button.addEventListener('click', () => {
      const body = toastEl?.querySelector('.toast-body');
      if (body) body.textContent = button.matches('[data-comment-reply]') ? 'Reply composer opened in this static demo.' : 'Comment approved in this static demo.';
      toast?.show();
    });
  });
  applyCommentFilters();

  const chatApp = document.querySelector('[data-chat-app]');
  if (chatApp) {
    const contacts = Array.from(chatApp.querySelectorAll('[data-chat-contact]'));
    const filterButtons = chatApp.querySelectorAll('[data-chat-filter]');
    const searchInput = chatApp.querySelector('[data-local-search="#chatContacts"]');
    const emptyState = chatApp.querySelector('[data-local-empty]');
    const nameNode = chatApp.querySelector('[data-chat-name]');
    const roleNode = chatApp.querySelector('[data-chat-role]');
    const statusNode = chatApp.querySelector('[data-chat-status]');
    const avatarNode = chatApp.querySelector('[data-chat-avatar]');
    const profileAvatar = chatApp.querySelector('[data-chat-profile-avatar]');
    const profileName = chatApp.querySelector('[data-chat-profile-name]');
    const profileRole = chatApp.querySelector('[data-chat-profile-role]');
    const messages = chatApp.querySelector('[data-chat-messages]');
    const form = chatApp.querySelector('[data-chat-form]');
    const input = chatApp.querySelector('[data-chat-input]');
    let activeFilter = 'all';

    const applyChatFilters = () => {
      const query = (searchInput?.value || '').trim().toLowerCase();
      let visibleCount = 0;
      contacts.forEach((contact) => {
        const categoryMatch = activeFilter === 'all' || contact.dataset.chatCategory === activeFilter;
        const searchText = `${contact.dataset.name || ''} ${contact.dataset.role || ''} ${contact.textContent || ''}`.toLowerCase();
        const searchMatch = !query || searchText.includes(query);
        const visible = categoryMatch && searchMatch;
        contact.hidden = !visible;
        if (visible) visibleCount += 1;
      });
      if (emptyState) emptyState.hidden = visibleCount !== 0;

      const activeContact = contacts.find((contact) => contact.classList.contains('active'));
      if (visibleCount && (!activeContact || activeContact.hidden)) {
        contacts.find((contact) => !contact.hidden)?.click();
      }
    };

    contacts.forEach((contact) => {
      contact.addEventListener('click', () => {
        contacts.forEach((item) => item.classList.remove('active'));
        contact.classList.add('active');
        if (nameNode) nameNode.textContent = contact.dataset.name || '';
        if (roleNode) roleNode.textContent = contact.dataset.role || '';
        if (statusNode) statusNode.innerHTML = `<i></i>${contact.dataset.status || 'Online'}`;
        const contactAvatar = contact.querySelector('.avatar');
        if (avatarNode && contactAvatar) {
          avatarNode.innerHTML = contactAvatar.innerHTML;
          avatarNode.className = contactAvatar.className;
          avatarNode.setAttribute('data-chat-avatar', '');
        }
        if (profileAvatar && contactAvatar) {
          profileAvatar.innerHTML = contactAvatar.innerHTML;
          profileAvatar.className = `${contactAvatar.className} xl`;
          profileAvatar.setAttribute('data-chat-profile-avatar', '');
        }
        if (profileName) profileName.textContent = contact.dataset.name || '';
        if (profileRole) profileRole.textContent = contact.dataset.role || '';
      });
    });

    filterButtons.forEach((button) => {
      button.addEventListener('click', () => {
        activeFilter = button.dataset.chatFilter || 'all';
        filterButtons.forEach((item) => {
          const active = item === button;
          item.classList.toggle('active', active);
          item.setAttribute('aria-pressed', String(active));
        });
        applyChatFilters();
      });
    });
    searchInput?.addEventListener('input', applyChatFilters);
    applyChatFilters();

    form?.addEventListener('submit', (event) => {
      event.preventDefault();
      const value = (input?.value || '').trim();
      if (!value || !messages) return;
      const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      messages.insertAdjacentHTML('beforeend', `<div class="message-row me"><div><p class="bubble me">${value.replace(/[&<>"']/g, (match) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[match]))}</p><small>${now}</small></div></div>`);
      input.value = '';
      messages.scrollTop = messages.scrollHeight;
    });
  }

  const mailState = { folder: 'inbox', status: 'all', search: '' };
  const updateMailPreview = (item) => {
    if (!item) return;
    document.querySelectorAll('[data-mail-item]').forEach((mail) => mail.classList.remove('active'));
    item.classList.add('active');
    const subject = document.querySelector('[data-mail-preview-subject]');
    const sender = document.querySelector('[data-mail-preview-sender]');
    const body = document.querySelector('[data-mail-preview-body]');
    if (subject) subject.textContent = item.dataset.previewSubject || '';
    if (sender) sender.textContent = item.dataset.previewSender || '';
    if (body) body.textContent = item.dataset.body || '';
  };
  const applyMailFilters = () => {
    const items = Array.from(document.querySelectorAll('[data-mail-item]'));
    let firstVisible = null;
    let visibleCount = 0;
    items.forEach((item) => {
      const folderMatch = item.dataset.folder === mailState.folder || (mailState.folder === 'starred' && item.dataset.status === 'starred');
      const statusMatch = mailState.status === 'all' || item.dataset.status === mailState.status;
      const searchText = ((item.dataset.subject || '') + ' ' + (item.dataset.sender || '') + ' ' + (item.dataset.body || '')).toLowerCase();
      const searchMatch = !mailState.search || searchText.includes(mailState.search);
      const visible = folderMatch && statusMatch && searchMatch;
      item.hidden = !visible;
      if (visible) {
        visibleCount += 1;
        firstVisible ||= item;
      }
    });
    const empty = document.querySelector('.mail-empty');
    if (empty) empty.hidden = visibleCount !== 0;
    updateMailPreview(firstVisible);
  };

  document.querySelectorAll('[data-mail-folder]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-mail-folder]').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      mailState.folder = button.dataset.mailFolder || 'inbox';
      applyMailFilters();
    });
  });
  document.querySelectorAll('[data-mail-status]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-mail-status]').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      mailState.status = button.dataset.mailStatus || 'all';
      applyMailFilters();
    });
  });
  document.querySelector('[data-mail-search]')?.addEventListener('input', (event) => {
    mailState.search = event.target.value.trim().toLowerCase();
    applyMailFilters();
  });
  document.querySelectorAll('[data-mail-item]').forEach((item) => {
    item.addEventListener('click', (event) => {
      if (event.target.closest('.mail-star') || event.target.closest('input')) return;
      updateMailPreview(item);
    });
  });
  document.querySelectorAll('.mail-star').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      button.classList.toggle('active');
      const item = button.closest('[data-mail-item]');
      if (item) item.dataset.status = button.classList.contains('active') ? 'starred' : 'unread';
      applyMailFilters();
    });
  });
  applyMailFilters();

  const calendarEl = document.getElementById('dashoraCalendar');
  const calendarToday = new Date();
  const calendarYear = calendarToday.getFullYear();
  const calendarMonth = calendarToday.getMonth();
  const calendarIsoAt = (day, time) => {
    const date = new Date(calendarYear, calendarMonth, day);
    const safeDay = Math.min(day, new Date(calendarYear, calendarMonth + 1, 0).getDate());
    date.setDate(safeDay);
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}T${time}`;
  };
  const calendarEvents = [
    { title: 'Cabana A - Reserved', start: calendarIsoAt(5, '10:00:00'), color: '#5278ff' },
    { title: 'Villa Suite Check-in', start: calendarIsoAt(8, '09:00:00'), color: '#2fa84f' },
    { title: 'Advance Payment Due', start: calendarIsoAt(12, '11:30:00'), color: '#f6a642' },
    { title: 'Full Venue Event', start: calendarIsoAt(16, '14:00:00'), color: '#8a1df2' },
    { title: 'Cabana B - Reserved', start: calendarIsoAt(20, '13:00:00'), color: '#5278ff' },
    { title: 'Deluxe Suite Check-out', start: calendarIsoAt(24, '09:30:00'), color: '#2fa84f' },
    { title: 'Final Settlement Due', start: calendarIsoAt(28, '16:30:00'), color: '#dc2626' },
    { title: 'Guest Arrival Today', start: calendarIsoAt(calendarToday.getDate(), '12:00:00'), color: '#2fb6d0' }
  ];
  const renderFallbackCalendar = (mount) => {
    if (!mount) return;
    let visibleDate = new Date(calendarYear, calendarMonth, calendarToday.getDate());
    const pad = (value) => String(value).padStart(2, '0');
    const sameDayEvents = (date) => calendarEvents.filter((event) => {
      const eventDate = new Date(event.start);
      return eventDate.getFullYear() === date.getFullYear() && eventDate.getMonth() === date.getMonth() && eventDate.getDate() === date.getDate();
    });
    const draw = () => {
      const year = visibleDate.getFullYear();
      const month = visibleDate.getMonth();
      const first = new Date(year, month, 1);
      const startOffset = (first.getDay() + 6) % 7;
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const monthName = first.toLocaleDateString('en', { month: 'long', year: 'numeric' });
      let cells = '';
      ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach((day) => {
        cells += `<div class="fallback-calendar-head">${day}</div>`;
      });
      for (let i = 0; i < startOffset; i += 1) {
        cells += '<div class="fallback-calendar-day muted"></div>';
      }
      for (let day = 1; day <= daysInMonth; day += 1) {
        const date = new Date(year, month, day);
        const dateKey = `${year}-${pad(month + 1)}-${pad(day)}`;
        const events = sameDayEvents(date).map((event) => `<span style="--event-color:${event.color}">${event.title}</span>`).join('');
        const todayKey = `${calendarYear}-${pad(calendarMonth + 1)}-${pad(calendarToday.getDate())}`;
        const today = dateKey === todayKey ? ' today' : '';
        cells += `<button class="fallback-calendar-day${today}" type="button"><strong>${day}</strong>${events}</button>`;
      }
      mount.innerHTML = `<div class="fallback-calendar">
        <div class="fallback-calendar-toolbar">
          <button class="btn btn-light btn-sm" data-calendar-prev type="button"><i class="bi bi-chevron-left"></i></button>
          <h3>${monthName}</h3>
          <div><button class="btn btn-primary btn-sm" data-calendar-today type="button">Today</button><button class="btn btn-light btn-sm" data-calendar-next type="button"><i class="bi bi-chevron-right"></i></button></div>
        </div>
        <div class="fallback-calendar-grid">${cells}</div>
      </div>`;
      mount.querySelector('[data-calendar-prev]')?.addEventListener('click', () => {
        visibleDate = new Date(year, month - 1, 1);
        draw();
      });
      mount.querySelector('[data-calendar-next]')?.addEventListener('click', () => {
        visibleDate = new Date(year, month + 1, 1);
        draw();
      });
      mount.querySelector('[data-calendar-today]')?.addEventListener('click', () => {
        visibleDate = new Date(calendarYear, calendarMonth, calendarToday.getDate());
        draw();
      });
    };
    draw();
  };
  if (calendarEl) {
    try {
      if (!window.FullCalendar) throw new Error('FullCalendar is not loaded.');
      calendarEl.innerHTML = '';
      const eventsUrl = calendarEl.dataset.eventsUrl;
      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: calendarToday,
        height: 'auto',
        dayMaxEvents: true,
        nowIndicator: true,
        navLinks: true,
        selectable: true,
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        buttonText: {
          today: 'Today',
          month: 'Month',
          week: 'Week',
          day: 'Day',
          list: 'List'
        },
        events: eventsUrl || calendarEvents,
        eventClick: (info) => {
          info.jsEvent.preventDefault();
          window.dispatchEvent(new CustomEvent('calendar-event-selected', {
            detail: {
              title: info.event.title,
              start: info.event.start,
              end: info.event.end,
              color: info.event.backgroundColor,
              ...info.event.extendedProps
            }
          }));
        }
      });
      calendar.render();
      setTimeout(() => {
        const hasFullCalendarView = calendarEl.classList.contains('fc') || calendarEl.querySelector('.fc-view-harness');
        if (!hasFullCalendarView) renderFallbackCalendar(calendarEl);
      }, 100);
    } catch (error) {
      renderFallbackCalendar(calendarEl);
    }
  }

  const chartRegistry = new Map();
  const variantNumber = (label, index, min, max) => {
    const seed = Array.from(label || 'default').reduce((sum, char) => sum + char.charCodeAt(0), 0) + (index * 37);
    return min + (seed % (max - min + 1));
  };
  const makeChartData = (kind, label) => {
    const normalized = (label || '').toLowerCase();
    const compact = normalized.includes('day') || normalized === 'today';
    const yearly = normalized.includes('year') || ['2026', 'dec'].includes(normalized);
    if (kind === 'traffic') {
      return {
        labels: ['Organic', 'Referral', 'Paid'],
        datasets: [{ data: [variantNumber(label, 1, 34, 56), variantNumber(label, 2, 22, 38), variantNumber(label, 3, 14, 28)] }]
      };
    }
    if (kind === 'sales-bars') {
      const labels = compact ? ['9a', '12p', '3p', '6p'] : yearly ? ['Q1', 'Q2', 'Q3', 'Q4'] : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
      return {
        labels,
        datasets: [
          { data: labels.map((_, index) => variantNumber(label, index, 96, 238)) },
          { data: labels.map((_, index) => variantNumber(label, index + 5, 72, 196)) }
        ]
      };
    }
    const labels = compact ? ['8a', '10a', '12p', '2p', '4p', '6p'] : yearly ? ['Q1', 'Q2', 'Q3', 'Q4'] : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'];
    return {
      labels,
      datasets: [
        { data: labels.map((_, index) => variantNumber(label, index, 28, 102)) },
        { data: labels.map((_, index) => variantNumber(label, index + 8, 18, 76)) }
      ]
    };
  };
  const statusBadge = (status) => {
    const normalized = status.toLowerCase();
    const badge = normalized.includes('won') || normalized.includes('completed') || normalized.includes('closed') ? 'won' : normalized.includes('pending') || normalized.includes('paused') || normalized.includes('stuck') ? 'stuck' : 'new';
    return `<span class="deal-badge ${badge}">${status}</span>`;
  };
  const teamPhotoMap = {
    JD: 'team-1.jpg', DR: 'team-1.jpg',
    SA: 'team-2.jpg', JB: 'team-2.jpg', LR: 'team-2.jpg',
    MR: 'team-3.jpg', CA: 'team-3.jpg', MC: 'team-3.jpg',
    JL: 'team-4.jpg', WP: 'team-4.jpg', PM: 'team-4.jpg',
    PS: 'team-5.jpg', NR: 'team-5.jpg', PW: 'team-5.jpg', SC: 'team-5.jpg',
    AR: 'team-6.jpg', CF: 'team-6.jpg', BH: 'team-6.jpg', AH: 'team-6.jpg',
    TN: 'team-1.jpg', SH: 'team-1.jpg',
    NB: 'team-2.jpg', HW: 'team-2.jpg',
    ZM: 'team-3.jpg', VR: 'team-3.jpg',
    CM: 'team-4.jpg', LC: 'team-4.jpg',
    EA: 'team-5.jpg', SG: 'team-5.jpg',
    CJ: 'team-6.jpg', OT: 'team-6.jpg'
  };
  const teamPhoto = (initials, name) => {
    const file = teamPhotoMap[initials];
    return file ? `<span class="rep-avatar has-photo"><img src="assets/img/team/${file}" alt="${name}"></span>` : `<span class="rep-avatar">${initials}</span>`;
  };
  const personLine = (initials, name) => `<span class="person-line">${teamPhoto(initials, name)}<span>${name}</span></span>`;
  const tableTemplates = {
    'deals status': {
      today: [
        ['Nova Systems', 'Today, 09:30', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-2.jpg" alt="Sara Ahmed"></span>Sara Ahmed', statusBadge('New Lead'), '<strong>$42.8K</strong>'],
        ['Orbit CRM', 'Today, 11:15', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-1.jpg" alt="Donald Risher"></span>Donald Risher', statusBadge('Intro Call'), '<strong>$63.4K</strong>'],
        ['CloudPeak', 'Today, 14:45', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-3.jpg" alt="Maya Rahman"></span>Maya Rahman', statusBadge('Deal Won'), '<strong>$88.9K</strong>']
      ],
      month: [
        ['Absternet LLC', 'Sep 20, 2026', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-1.jpg" alt="Donald Risher"></span>Donald Risher', statusBadge('Deal Won'), '<strong>$100.1K</strong>'],
        ['Raitech Soft', 'Sep 23, 2026', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-5.jpg" alt="Sofia Cunha"></span>Sofia Cunha', statusBadge('Intro Call'), '<strong>$150K</strong>'],
        ['William PVT', 'Sep 27, 2026', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-2.jpg" alt="Luis Rocha"></span>Luis Rocha', statusBadge('Stuck'), '<strong>$78.18K</strong>'],
        ['Apple Inc.', 'Oct 02, 2026', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-6.jpg" alt="Ayaan Hudda"></span>Ayaan Hudda', statusBadge('New Lead'), '<strong>$78.9K</strong>']
      ],
      year: [
        ['Acme Inc.', 'Q1 2026', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-2.jpg" alt="Jansh Brown"></span>Jansh Brown', statusBadge('Deal Won'), '<strong>$420K</strong>'],
        ['Northwind Labs', 'Q2 2026', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-5.jpg" alt="Priya Shah"></span>Priya Shah', statusBadge('Deal Won'), '<strong>$316K</strong>'],
        ['Vertex Cloud', 'Q3 2026', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-6.jpg" alt="Ariana Reed"></span>Ariana Reed', statusBadge('Pipeline'), '<strong>$284K</strong>']
      ]
    },
    'closing deals': {
      closed: [
        ['Acme Inc Install', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-1.jpg" alt="Donald Risher"></span>Donald Risher', '<strong>$96k</strong>', 'Today'],
        ['Save Lots Stores', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-2.jpg" alt="Jansh Brown"></span>Jansh Brown', '<strong>$55.7k</strong>', '30 Dec 2026'],
        ['William PVT', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-6.jpg" alt="Ayaan Hudda"></span>Ayaan Hudda', '<strong>$102k</strong>', '25 Nov 2026']
      ],
      active: [
        ['Northwind Renewal', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-3.jpg" alt="Maya Rahman"></span>Maya Rahman', '<strong>$118k</strong>', '12 Jan 2027'],
        ['Orbit Expansion', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-2.jpg" alt="Sara Ahmed"></span>Sara Ahmed', '<strong>$73k</strong>', '18 Jan 2027'],
        ['Nexa Analytics', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-5.jpg" alt="Priya Shah"></span>Priya Shah', '<strong>$84k</strong>', '21 Jan 2027']
      ],
      paused: [
        ['CloudPeak Migration', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-4.jpg" alt="Jon Lee"></span>Jon Lee', '<strong>$64k</strong>', 'Paused'],
        ['StudioFlow Suite', '<span class="rep-avatar has-photo"><img src="assets/img/team/team-6.jpg" alt="Ariana Reed"></span>Ariana Reed', '<strong>$41k</strong>', 'Paused']
      ]
    },
    'my tasks': {
      all: [
        ['Create new Admin Template', '03 Nov 2026', statusBadge('Completed'), personLine('MC', 'Marketing Coordinator')],
        ['Administrative Analyst', '17 Nov 2026', statusBadge('Progress'), personLine('DR', 'Donald Risher')],
        ['E-commerce Landing Page', '10 Dec 2026', statusBadge('Pending'), personLine('JB', 'Jansh Brown')]
      ],
      progress: [
        ['Administrative Analyst', '17 Nov 2026', statusBadge('Progress'), personLine('DR', 'Donald Risher')],
        ['UI/UX Design', '22 Dec 2026', statusBadge('Progress'), personLine('CA', 'Carroll Adams')]
      ],
      pending: [
        ['E-commerce Landing Page', '10 Dec 2026', statusBadge('Pending'), personLine('JB', 'Jansh Brown')],
        ['Projects Design', '31 Dec 2026', statusBadge('Pending'), personLine('WP', 'William Pinto')]
      ]
    },
    'top landing pages': {
      today: [
        ['<strong>/dashboard/analytics</strong>', '6,840', '4m 26s', '27.8%', '<span class="status paid">9.1%</span>'],
        ['<strong>/pricing</strong>', '5,120', '3m 08s', '34.5%', '<span class="status paid">7.2%</span>'],
        ['<strong>/docs/getting-started</strong>', '3,760', '5m 32s', '21.6%', '<span class="status paid">6.8%</span>']
      ],
      week: [
        ['<strong>/dashboard/analytics</strong>', '42,580', '4m 18s', '28.4%', '<span class="status paid">8.9%</span>'],
        ['<strong>/products/pro-plan</strong>', '31,940', '3m 42s', '31.6%', '<span class="status paid">7.4%</span>'],
        ['<strong>/pricing</strong>', '24,730', '2m 58s', '36.2%', '<span class="status pending">5.8%</span>'],
        ['<strong>/docs/getting-started</strong>', '18,420', '5m 06s', '22.9%', '<span class="status paid">6.6%</span>']
      ],
      month: [
        ['<strong>/dashboard/analytics</strong>', '183,620', '4m 12s', '29.1%', '<span class="status paid">8.5%</span>'],
        ['<strong>/products/pro-plan</strong>', '128,470', '3m 39s', '32.8%', '<span class="status paid">7.1%</span>'],
        ['<strong>/pricing</strong>', '96,350', '3m 01s', '35.4%', '<span class="status pending">5.9%</span>'],
        ['<strong>/integrations</strong>', '54,820', '2m 46s', '39.2%', '<span class="status pending">4.8%</span>']
      ]
    }
  };
  const replaceTableBody = (tbody, rows) => {
    tbody.innerHTML = rows.map((row) => `<tr>${row.map((cell, index) => `<td>${index === 0 && !String(cell).includes('<strong>') ? `<strong>${cell}</strong>` : cell}</td>`).join('')}</tr>`).join('');
  };
  const updateTablePanel = (panel, label) => {
    const heading = panel.querySelector('.panel-head h2')?.textContent.trim().toLowerCase();
    const tbody = panel.querySelector('tbody');
    if (!heading || !tbody) return false;
    const template = tableTemplates[heading];
    if (!template) return false;
    const key = label.toLowerCase();
    replaceTableBody(tbody, template[key] || template.all || Object.values(template)[0]);
    return true;
  };
  const updateListPanel = (panel, label) => {
    const heading = panel.querySelector('.panel-head h2')?.textContent.trim().toLowerCase();
    if (!heading) return false;
    if (heading.includes('top performers')) {
      panel.querySelectorAll('.performer-card').forEach((card, index) => {
        const amount = card.querySelector('b');
        const change = card.querySelector('em');
        if (amount) amount.textContent = `$${variantNumber(label, index, 6200, 24800).toLocaleString()}.${variantNumber(label, index + 2, 10, 98)}`;
        if (change) {
          const positive = variantNumber(label, index, 0, 10) > 3;
          change.className = positive ? 'won' : 'stuck';
          change.textContent = `${positive ? '+' : '-'}$${variantNumber(label, index + 4, 18, 96)}.${variantNumber(label, index + 5, 10, 90)}`;
        }
      });
      return true;
    }
    if (heading.includes('trading')) {
      const action = label.toLowerCase().includes('sell') ? 'Sell' : 'Buy';
      const cta = panel.querySelector('.btn-primary');
      const balance = panel.querySelector('.trade-balance strong');
      if (cta) cta.textContent = `${action} Coin`;
      if (balance) balance.textContent = action === 'Sell' ? '0.842 BTC' : '$12,426.07';
      return true;
    }
    if (heading.includes('projects overview')) {
      panel.querySelectorAll('.balance-stat strong').forEach((node, index) => {
        node.textContent = index === 2 ? `${variantNumber(label, index, 880, 3200)}h` : String(variantNumber(label, index, 42, 320));
      });
      return true;
    }
    return false;
  };

  document.querySelectorAll('.segmented').forEach((group) => {
    group.querySelectorAll('button').forEach((button) => {
      button.addEventListener('click', () => {
        group.querySelectorAll('button').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
        const panel = button.closest('.panel');
        const canvas = panel?.querySelector('[data-chart]');
        const chart = canvas ? chartRegistry.get(canvas) : null;
        if (chart) {
          const next = makeChartData(canvas.dataset.chart, button.textContent.trim());
          chart.data.labels = next.labels;
          next.datasets.forEach((dataset, index) => {
            if (chart.data.datasets[index]) chart.data.datasets[index].data = dataset.data;
          });
          chart.update();
        }
        const contentChanged = !chart && panel ? (updateTablePanel(panel, button.textContent.trim()) || updateListPanel(panel, button.textContent.trim())) : Boolean(chart);
        if (!contentChanged && !group.matches('[data-mail-sort]')) {
          const body = toastEl?.querySelector('.toast-body');
          if (body) body.textContent = `${button.textContent.trim()} view selected.`;
          toast?.show();
        }
      });
    });
  });

  document.querySelectorAll('.panel .btn:not([data-create-open]):not([data-export-page]):not([data-bs-toggle]), .team-actions .btn, .preview-actions .btn').forEach((button) => {
    button.addEventListener('click', () => {
      const body = toastEl?.querySelector('.toast-body');
      if (body) body.textContent = `${button.textContent.trim() || 'Action'} clicked in this static demo.`;
      toast?.show();
    });
  });

  document.querySelectorAll('form:not(#createForm):not([data-chat-form]):not([data-live-form])').forEach((form) => {
    const action = form.getAttribute('action');
    if (action && action !== '#') return;
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      let feedback = form.querySelector('[data-demo-form-feedback]');
      if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'alert alert-success mt-3 mb-0 py-2';
        feedback.setAttribute('role', 'status');
        feedback.setAttribute('data-demo-form-feedback', '');
        form.appendChild(feedback);
      }
      const actionLabel = form.querySelector('button[type="submit"], .btn-primary')?.textContent.trim() || 'Request';
      feedback.textContent = `${actionLabel} received in this static demo.`;
      const body = toastEl?.querySelector('.toast-body');
      if (body) body.textContent = feedback.textContent;
      toast?.show();
    });
  });

  if (!window.Chart) return;

  const legendGapPlugin = {
    id: 'dashoraLegendGap',
    beforeInit(chart) {
      const legend = chart.legend;
      if (!legend || legend._dashoraGapApplied) return;
      const originalFit = legend.fit;
      legend.fit = function fit() {
        originalFit.bind(legend)();
        const options = chart.options?.plugins?.legend;
        if (options?.display !== false && options?.position === 'top') {
          legend.height += 14;
        }
      };
      legend._dashoraGapApplied = true;
    }
  };

  Chart.register(legendGapPlugin);

  const palette = {
    blue: '#2563eb',
    teal: '#14b8a6',
    amber: '#f59e0b',
    grid: 'rgba(148, 163, 184, .22)'
  };

  document.querySelectorAll('[data-chart]').forEach((canvas) => {
    const type = canvas.dataset.chart;
    const isDoughnut = type === 'traffic';
    const isBar = type === 'sales-bars';
    const chart = new Chart(canvas, {
      type: isDoughnut ? 'doughnut' : isBar ? 'bar' : 'line',
      data: isDoughnut ? {
        labels: ['Organic', 'Referral', 'Paid'],
        datasets: [{ data: [48, 32, 20], backgroundColor: [palette.blue, palette.teal, palette.amber], borderWidth: 0 }]
      } : isBar ? {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [
          { label: 'Orders', data: [118, 164, 142, 188, 214, 176, 238], backgroundColor: palette.blue, borderRadius: 8 },
          { label: 'Revenue', data: [92, 128, 104, 156, 182, 141, 196], backgroundColor: palette.teal, borderRadius: 8 }
        ]
      } : {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
        datasets: [
          { label: 'Revenue', data: [32, 41, 39, 56, 62, 74, 81, 96], borderColor: palette.blue, backgroundColor: 'rgba(37,99,235,.12)', fill: true, tension: .42 },
          { label: 'Profit', data: [18, 24, 28, 31, 38, 44, 52, 61], borderColor: palette.teal, backgroundColor: 'rgba(20,184,166,.08)', fill: true, tension: .42 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 6 } },
        plugins: {
          legend: {
            display: !isDoughnut,
            position: 'top',
            labels: { boxWidth: 10, usePointStyle: true, padding: 22 }
          }
        },
        scales: isDoughnut ? {} : {
          x: { grid: { color: 'transparent' }, ticks: { color: '#94a3b8' } },
          y: { grid: { color: palette.grid }, ticks: { color: '#94a3b8' }, beginAtZero: true }
        },
        cutout: isDoughnut ? '68%' : undefined
      }
    });
    chartRegistry.set(canvas, chart);
  });
})();

// Shared Alpine component for the "Checkout & Pay Balance" -> "Download Final
// Invoice PDF" inline swap, used by both the mobile dashboard and the Manage
// Bookings mobile cards. Defined outside the IIFE above so it's global and
// reachable from x-data attributes.
function checkoutCard(checkoutUrl, finalInvoiceUrl) {
    return {
        processing: false,
        checkedOut: false,
        error: null,
        finalInvoiceUrl: finalInvoiceUrl,
        checkout() {
            if (this.processing || this.checkedOut) return;

            this.processing = true;
            this.error = null;

            fetch(checkoutUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
            })
                .then(async (response) => {
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to check out this booking.');
                    }

                    return data;
                })
                .then((data) => {
                    this.finalInvoiceUrl = data.final_invoice_url || this.finalInvoiceUrl;
                    this.checkedOut = true;
                })
                .catch((err) => {
                    this.error = err.message;
                })
                .finally(() => {
                    this.processing = false;
                });
        },
    };
}
