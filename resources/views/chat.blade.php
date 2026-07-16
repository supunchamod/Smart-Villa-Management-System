@extends('layouts.app')

@section('title', 'Chat | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="index.html"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Chat</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><button class="btn btn-primary" data-create-open data-create-type="chat" data-create-label="Start Chat"><i class="bi bi-plus-lg"></i> Start Chat</button></div>
        </div>
        <div class="chat-shell premium-chat" data-chat-app>
          <aside class="chat-sidebar panel">
            <div class="chat-sidebar-head">
              <div><span class="eyebrow">Messages</span><h2>Inbox</h2></div>
              <button class="icon-btn" type="button" aria-label="New message"><i class="bi bi-pencil-square"></i></button>
            </div>
            <div class="chat-search"><i class="bi bi-search"></i><input type="search" placeholder="Search conversations" aria-label="Search conversations" data-local-search="#chatContacts"></div>
            <div class="chat-tabs" role="group" aria-label="Message filters"><button class="active" type="button" data-chat-filter="all" aria-pressed="true">All</button><button type="button" data-chat-filter="team" aria-pressed="false">Teams</button><button type="button" data-chat-filter="client" aria-pressed="false">Clients</button></div>
            <div class="chat-contact-list" id="chatContacts">
              <button class="chat-contact active" type="button" data-chat-contact data-local-item data-chat-category="client" data-name="Maya Rahman" data-role="Customer Success Lead" data-status="Typing..." data-avatar="MR">
                <span class="avatar online has-photo"><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Maya Rahman"></span><span><strong>Maya Rahman</strong><small>Typing a reply...</small></span><em>2m</em>
              </button>
              <button class="chat-contact" type="button" data-chat-contact data-local-item data-chat-category="team" data-name="Jon Lee" data-role="Product Manager" data-status="Online" data-avatar="JL">
                <span class="avatar online has-photo"><img src="{{ asset('assets/img/team/team-4.jpg') }}" alt="Jon Lee"></span><span><strong>Jon Lee</strong><small>Shared sprint notes</small></span><em>18m</em>
              </button>
              <button class="chat-contact" type="button" data-chat-contact data-local-item data-chat-category="team" data-name="Priya Shah" data-role="Finance Partner" data-status="Away" data-avatar="PS">
                <span class="avatar away has-photo"><img src="{{ asset('assets/img/team/team-5.jpg') }}" alt="Priya Shah"></span><span><strong>Priya Shah</strong><small>Invoice export is ready</small></span><em>1h</em>
              </button>
              <button class="chat-contact" type="button" data-chat-contact data-local-item data-chat-category="team" data-name="Ops Team" data-role="Internal Channel" data-status="6 members active" data-avatar="OT">
                <span class="avatar online has-photo"><img src="{{ asset('assets/img/team/team-6.jpg') }}" alt="Ops Team"></span><span><strong>Ops Team</strong><small>Deployment checklist</small></span><em>3h</em>
              </button>
              <button class="chat-contact" type="button" data-chat-contact data-local-item data-chat-category="client" data-name="Natalie Reed" data-role="Enterprise Client" data-status="Offline" data-avatar="NR">
                <span class="avatar has-photo"><img src="{{ asset('assets/img/team/team-5.jpg') }}" alt="Natalie Reed"></span><span><strong>Natalie Reed</strong><small>Can we move the review?</small></span><em>Tue</em>
              </button>
            </div>
            <div class="empty-state local-empty" data-local-empty hidden><i class="bi bi-search"></i><h2>No conversations</h2><p>Try a different name, role, or message.</p></div>
          </aside>
          <section class="chat-window premium-chat-window">
            <div class="chat-header">
              <div class="chat-peer">
                <span class="avatar online has-photo" data-chat-avatar><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Maya Rahman"></span>
                <div><strong data-chat-name>Maya Rahman</strong><small data-chat-role>Customer Success Lead</small></div>
              </div>
              <div class="chat-header-actions">
                <span class="chat-presence" data-chat-status><i></i>Typing...</span>
                <button class="icon-btn" type="button" aria-label="Start call"><i class="bi bi-telephone"></i></button>
                <button class="icon-btn" type="button" aria-label="Start video"><i class="bi bi-camera-video"></i></button>
                <button class="icon-btn" type="button" aria-label="Conversation options"><i class="bi bi-three-dots"></i></button>
              </div>
            </div>
            <div class="chat-context">
              <div><span>Open ticket</span><strong>#CS-2847</strong></div>
              <div><span>Priority</span><strong class="text-danger">High</strong></div>
              <div><span>SLA</span><strong>4h 12m</strong></div>
            </div>
            <div class="messages" data-chat-messages>
              <div class="message-row other"><span class="avatar sm has-photo"><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Maya Rahman"></span><div><p class="bubble other">Can you review the updated customer report before the standup?</p><small>09:32 AM</small></div></div>
              <div class="message-row me"><div><p class="bubble me">Yes. I checked the account health section and added comments to the retention table.</p><small>09:35 AM</small></div></div>
              <div class="message-row other"><span class="avatar sm has-photo"><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Maya Rahman"></span><div><p class="bubble other">Great. Finance also needs the invoice export and renewal forecast.</p><small>09:37 AM</small></div></div>
              <div class="message-row me"><div><p class="bubble me">I will attach both files and send the final summary before 11:00.</p><small>09:40 AM</small></div></div>
            </div>
            <form class="composer" data-chat-form>
              <button class="icon-btn" type="button" aria-label="Attach file"><i class="bi bi-paperclip"></i></button>
              <button class="icon-btn" type="button" aria-label="Add emoji"><i class="bi bi-emoji-smile"></i></button>
              <input class="form-control" data-chat-input placeholder="Write a message" autocomplete="off">
              <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i><span>Send</span></button>
            </form>
          </section>
          <aside class="chat-profile panel">
            <span class="avatar xl has-photo" data-chat-profile-avatar><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Maya Rahman"></span>
            <h2 data-chat-profile-name>Maya Rahman</h2>
            <p data-chat-profile-role>Customer Success Lead</p>
            <div class="chat-profile-actions">
              <button class="icon-btn" type="button" aria-label="Message"><i class="bi bi-chat-dots"></i></button>
              <button class="icon-btn" type="button" aria-label="Call"><i class="bi bi-telephone"></i></button>
              <button class="icon-btn" type="button" aria-label="Email"><i class="bi bi-envelope"></i></button>
            </div>
            <div class="chat-profile-meta">
              <div><span>Company</span><strong>Northwind Labs</strong></div>
              <div><span>Timezone</span><strong>GMT +06:00</strong></div>
              <div><span>Shared files</span><strong>24 files</strong></div>
            </div>
            <div class="shared-files">
              <h3>Shared Files</h3>
              <a href="reports.html" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i><span>Q2 report.pdf</span><em>2.4 MB</em></a>
              <a href="invoice-list.html" target="_blank" rel="noopener"><i class="bi bi-file-earmark-spreadsheet"></i><span>Invoice export.xlsx</span><em>980 KB</em></a>
              <a href="tasks.html" target="_blank" rel="noopener"><i class="bi bi-file-earmark-text"></i><span>Renewal notes.doc</span><em>440 KB</em></a>
            </div>
          </aside>
        </div>
@endsection
