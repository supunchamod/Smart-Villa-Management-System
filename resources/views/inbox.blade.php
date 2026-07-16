@extends('layouts.app')

@section('title', 'Inbox | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="index.html"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Inbox</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><button class="btn btn-primary" data-create-open data-create-type="inbox" data-create-label="Compose"><i class="bi bi-plus-lg"></i> Compose</button></div>
        </div>
        <div class="inbox-shell">
  <aside class="mail-sidebar panel">
    <button class="btn btn-primary w-100 mb-3"><i class="bi bi-pencil-square"></i> Compose</button>
    <nav class="mail-folders">
      <button class="active" data-mail-folder="inbox" type="button"><span><i class="bi bi-inbox"></i>Inbox</span><em>24</em></button><button class="" data-mail-folder="starred" type="button"><span><i class="bi bi-star"></i>Starred</span><em>8</em></button><button class="" data-mail-folder="sent" type="button"><span><i class="bi bi-send"></i>Sent</span><em>128</em></button><button class="" data-mail-folder="drafts" type="button"><span><i class="bi bi-file-earmark"></i>Drafts</span><em>3</em></button><button class="" data-mail-folder="archive" type="button"><span><i class="bi bi-archive"></i>Archive</span><em>42</em></button>
    </nav>
  </aside>
  <section class="panel mail-panel">
    <div class="mail-toolbar">
      <div class="search mail-search"><i class="bi bi-search"></i><input data-mail-search type="search" placeholder="Search mail..." aria-label="Search mail"></div>
      <div class="segmented" data-mail-sort><button class="active" data-mail-status="all" type="button">All</button><button data-mail-status="unread" type="button">Unread</button><button data-mail-status="starred" type="button">Starred</button></div>
    </div>
    <div class="mail-content">
      <div class="mail-list">
        <article class="mail-item active" data-mail-item data-folder="inbox" data-status="unread" data-subject="contract renewal approved" data-sender="maya rahman" data-body="The enterprise renewal is approved and ready for finance review." data-preview-subject="Contract renewal approved" data-preview-sender="Maya Rahman">
          <span class="form-check"><input class="form-check-input" type="checkbox"></span>
          <button class="mail-star " type="button" aria-label="Star message"><i class="bi bi-star-fill"></i></button>
          <div><strong>Contract renewal approved</strong><p>Maya Rahman - The enterprise renewal is approved and ready for finance review.</p></div>
          <small>2h</small>
        </article><article class="mail-item " data-mail-item data-folder="inbox" data-status="starred" data-subject="quarterly report feedback" data-sender="donald risher" data-body="Please review the attached notes and confirm the next step." data-preview-subject="Quarterly report feedback" data-preview-sender="Donald Risher">
          <span class="form-check"><input class="form-check-input" type="checkbox"></span>
          <button class="mail-star active" type="button" aria-label="Star message"><i class="bi bi-star-fill"></i></button>
          <div><strong>Quarterly report feedback</strong><p>Donald Risher - Please review the attached notes and confirm the next step.</p></div>
          <small>4h</small>
        </article><article class="mail-item " data-mail-item data-folder="starred" data-status="unread" data-subject="new support escalation" data-sender="ariana reed" data-body="Customer success needs a response before the SLA window closes." data-preview-subject="New support escalation" data-preview-sender="Ariana Reed">
          <span class="form-check"><input class="form-check-input" type="checkbox"></span>
          <button class="mail-star " type="button" aria-label="Star message"><i class="bi bi-star-fill"></i></button>
          <div><strong>New support escalation</strong><p>Ariana Reed - Customer success needs a response before the SLA window closes.</p></div>
          <small>6h</small>
        </article><article class="mail-item " data-mail-item data-folder="sent" data-status="sent" data-subject="invoice payment confirmed" data-sender="finance team" data-body="Payment confirmation was sent to the customer contact." data-preview-subject="Invoice payment confirmed" data-preview-sender="Finance Team">
          <span class="form-check"><input class="form-check-input" type="checkbox"></span>
          <button class="mail-star " type="button" aria-label="Star message"><i class="bi bi-star-fill"></i></button>
          <div><strong>Invoice payment confirmed</strong><p>Finance Team - Payment confirmation was sent to the customer contact.</p></div>
          <small>1d</small>
        </article><article class="mail-item " data-mail-item data-folder="drafts" data-status="draft" data-subject="design system review" data-sender="sara ahmed" data-body="Draft response for the component audit and dashboard spacing." data-preview-subject="Design system review" data-preview-sender="Sara Ahmed">
          <span class="form-check"><input class="form-check-input" type="checkbox"></span>
          <button class="mail-star " type="button" aria-label="Star message"><i class="bi bi-star-fill"></i></button>
          <div><strong>Design system review</strong><p>Sara Ahmed - Draft response for the component audit and dashboard spacing.</p></div>
          <small>2d</small>
        </article><article class="mail-item " data-mail-item data-folder="archive" data-status="archive" data-subject="partner portal update" data-sender="jon lee" data-body="Archived deployment summary for the partner portal release." data-preview-subject="Partner portal update" data-preview-sender="Jon Lee">
          <span class="form-check"><input class="form-check-input" type="checkbox"></span>
          <button class="mail-star " type="button" aria-label="Star message"><i class="bi bi-star-fill"></i></button>
          <div><strong>Partner portal update</strong><p>Jon Lee - Archived deployment summary for the partner portal release.</p></div>
          <small>5d</small>
        </article>
        <div class="empty-state mail-empty" hidden><i class="bi bi-envelope-open"></i><h2>No messages</h2><p>Try another folder or search term.</p></div>
      </div>
      <aside class="mail-preview">
        <div class="preview-head"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Maya Rahman"></span><div><h2 data-mail-preview-subject>Contract renewal approved</h2><p data-mail-preview-sender>Maya Rahman</p></div></div>
        <p data-mail-preview-body>The enterprise renewal is approved and ready for finance review.</p>
        <div class="preview-actions"><button class="btn btn-light"><i class="bi bi-reply"></i> Reply</button><button class="btn btn-primary"><i class="bi bi-check2"></i> Mark Done</button></div>
      </aside>
    </div>
  </section>
</div>
@endsection
