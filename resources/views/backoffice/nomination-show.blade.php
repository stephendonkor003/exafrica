@php
    $nominee = $nomination->nominee;
    $category = $nomination->category;
    $nominator = $nomination->nominatedBy;
    $evaluator = $nomination->evaluatedBy;
    $achievementDocuments = $nomination->achievement_documents ?? [];
    $achievementLinks = $nomination->achievement_links ?? [];

    $display = static fn ($value, $fallback = 'Not provided') => filled($value) ? $value : $fallback;
    $dateDisplay = static fn ($value) => $value ? $value->format('M d, Y H:i') : 'Not recorded';

    $rawFields = [
        'Nomination ID' => $nomination->id,
        'Reference Code' => $nomination->reference_code,
        'Nominee ID' => $nomination->nominee_id,
        'Nominee Country' => $nominee?->country,
        'Category ID' => $nomination->category_id,
        'Nominated By User ID' => $nomination->nominated_by,
        'Evaluation Status' => $nomination->evaluation_status,
        'Evaluated By User ID' => $nomination->evaluated_by,
        'Evaluated At' => $dateDisplay($nomination->evaluated_at),
        'Nominator IP' => $nomination->nominator_ip,
        'Device Hash' => $nomination->nominator_device_hash,
        'Achievement Document Count' => count($achievementDocuments),
        'Achievement Link Count' => count($achievementLinks),
        'Created At' => $dateDisplay($nomination->created_at),
        'Updated At' => $dateDisplay($nomination->updated_at),
    ];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nomination Record | Extraordinary Africans</title>
    <link rel="stylesheet" href="{{ asset('css/backoffice.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bo-app-body">
    <div class="bo-app">
        <aside class="bo-sidebar">
            <div class="bo-sidebar-brand">
                <span class="bo-brand-mark"><i class="fa fa-earth-africa" aria-hidden="true"></i></span>
                <div>
                    <strong>Extraordinary Africans</strong>
                    <span>Agenda 2063 Back Office</span>
                </div>
            </div>

            <div class="bo-sidebar-card">
                <span>Signed in as</span>
                <strong>{{ $adminUser->name }}</strong>
                <em>Super Admin</em>
            </div>

            <nav class="bo-nav" aria-label="Back Office sections">
                <a class="bo-nav-item" href="{{ route('backoffice.dashboard') }}"><i class="fa fa-chart-line"></i><span>Dashboard</span></a>
                <a class="bo-nav-item active" href="{{ route('backoffice.dashboard') }}#nominations"><i class="fa fa-clipboard-check"></i><span>Nominations</span></a>
                <a class="bo-nav-item" href="{{ route('backoffice.dashboard') }}#nominees"><i class="fa fa-users"></i><span>Nominees</span></a>
                <a class="bo-nav-item" href="{{ route('backoffice.dashboard') }}#users"><i class="fa fa-user-shield"></i><span>Users</span></a>
            </nav>

            <form method="POST" action="{{ route('backoffice.logout') }}" class="bo-logout-form">
                @csrf
                <button type="submit"><i class="fa fa-arrow-right-from-bracket"></i><span>Sign Out</span></button>
            </form>
        </aside>

        <main class="bo-main">
            <header class="bo-topbar">
                <div>
                    <span class="bo-kicker">Nomination Record</span>
                    <h1>{{ $display($nominee?->full_name, 'Nominee Record') }}</h1>
                </div>
                <div class="bo-topbar-actions">
                    <a href="{{ route('backoffice.dashboard') }}#nominations" class="bo-ghost-link"><i class="fa fa-arrow-left"></i><span>Back to Nominations</span></a>
                </div>
                <div class="bo-user-chip">
                    <span>{{ strtoupper(substr($adminUser->name, 0, 1)) }}</span>
                    <div>
                        <strong>{{ $adminUser->name }}</strong>
                        <em>{{ $adminUser->email }}</em>
                    </div>
                </div>
            </header>

            <section class="bo-record-hero">
                <div>
                    <span class="bo-kicker">Reference</span>
                    <strong>{{ $display($nomination->reference_code, 'No reference') }}</strong>
                </div>
                <div>
                    <span>Status</span>
                    <em class="bo-status {{ $nomination->evaluation_status }}">{{ $nomination->evaluation_status }}</em>
                </div>
                <div>
                    <span>Category</span>
                    <strong>{{ $display($category?->name) }}</strong>
                </div>
                <div>
                    <span>Submitted</span>
                    <strong>{{ $dateDisplay($nomination->created_at) }}</strong>
                </div>
            </section>

            <section class="bo-record-grid">
                <article class="bo-card bo-record-card">
                    <header>
                        <div>
                            <span class="bo-card-kicker">Nominee</span>
                            <h2>Nominee Details</h2>
                        </div>
                        <span><i class="fa fa-user"></i></span>
                    </header>
                    <dl class="bo-record-list">
                        <div><dt>Full Name</dt><dd>{{ $display($nominee?->full_name) }}</dd></div>
                        <div><dt>Country</dt><dd>{{ $display($nominee?->country) }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $display($nominee?->email) }}</dd></div>
                        <div><dt>Phone</dt><dd>{{ $display($nominee?->phone) }}</dd></div>
                        <div><dt>Nominee Status</dt><dd><span class="bo-status {{ $nominee?->status }}">{{ $display($nominee?->status) }}</span></dd></div>
                        <div><dt>Votes</dt><dd>{{ number_format((int) ($nominee?->vote_count ?? 0)) }}</dd></div>
                        <div><dt>Profile Image</dt><dd>{{ $display($nominee?->profile_image) }}</dd></div>
                        @if ($nominee?->profile_image)
                            <div class="bo-record-wide">
                                <dt>Profile Picture Preview</dt>
                                <dd>
                                    <a class="bo-record-media" href="{{ $nominee->profile_image }}" target="_blank" rel="noopener">
                                        <img src="{{ $nominee->profile_image }}" alt="{{ $nominee->full_name }}">
                                        <span>Open image</span>
                                    </a>
                                </dd>
                            </div>
                        @endif
                        <div class="bo-record-wide"><dt>Bio</dt><dd>{{ $display($nominee?->bio) }}</dd></div>
                        <div class="bo-record-wide"><dt>Rejection Reason</dt><dd>{{ $display($nominee?->rejection_reason) }}</dd></div>
                    </dl>
                </article>

                <article class="bo-card bo-record-card">
                    <header>
                        <div>
                            <span class="bo-card-kicker">Nominator</span>
                            <h2>Submitted By</h2>
                        </div>
                        <span><i class="fa fa-user-check"></i></span>
                    </header>
                    <dl class="bo-record-list">
                        <div><dt>Name</dt><dd>{{ $display($nominator?->name) }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $display($nominator?->email) }}</dd></div>
                        <div><dt>Role</dt><dd>{{ $display($nominator?->role?->name) }}</dd></div>
                        <div><dt>IP Address</dt><dd>{{ $display($nomination->nominator_ip) }}</dd></div>
                        <div class="bo-record-wide"><dt>Device Hash</dt><dd>{{ $display($nomination->nominator_device_hash) }}</dd></div>
                        <div class="bo-record-wide"><dt>User Agent</dt><dd>{{ $display($nomination->nominator_user_agent) }}</dd></div>
                    </dl>
                </article>

                <article class="bo-card bo-record-card">
                    <header>
                        <div>
                            <span class="bo-card-kicker">Evaluation</span>
                            <h2>Review Details</h2>
                        </div>
                        <span><i class="fa fa-clipboard-check"></i></span>
                    </header>
                    <dl class="bo-record-list">
                        <div><dt>Evaluation Status</dt><dd><span class="bo-status {{ $nomination->evaluation_status }}">{{ $nomination->evaluation_status }}</span></dd></div>
                        <div><dt>Evaluated At</dt><dd>{{ $dateDisplay($nomination->evaluated_at) }}</dd></div>
                        <div><dt>Evaluator</dt><dd>{{ $display($evaluator?->name, 'Not evaluated') }}</dd></div>
                        <div><dt>Evaluator Email</dt><dd>{{ $display($evaluator?->email, 'Not evaluated') }}</dd></div>
                        <div class="bo-record-wide"><dt>Evaluator Notes</dt><dd>{{ $display($nomination->evaluator_notes) }}</dd></div>
                    </dl>
                </article>

                <article class="bo-card bo-record-card">
                    <header>
                        <div>
                            <span class="bo-card-kicker">Category</span>
                            <h2>Category Details</h2>
                        </div>
                        <span><i class="fa fa-layer-group"></i></span>
                    </header>
                    <dl class="bo-record-list">
                        <div><dt>Name</dt><dd>{{ $display($category?->name) }}</dd></div>
                        <div><dt>Position</dt><dd>{{ $display($category?->position) }}</dd></div>
                        <div><dt>Max Nominees</dt><dd>{{ $display($category?->max_nominees) }}</dd></div>
                        <div><dt>Active</dt><dd><span class="bo-status {{ $category?->is_active ? 'active' : 'inactive' }}">{{ $category?->is_active ? 'active' : 'inactive' }}</span></dd></div>
                        <div class="bo-record-wide"><dt>Description</dt><dd>{{ $display($category?->description) }}</dd></div>
                    </dl>
                </article>
            </section>

            <section class="bo-card bo-record-card bo-record-full">
                <header>
                    <div>
                        <span class="bo-card-kicker">Submission</span>
                        <h2>Nomination Reason</h2>
                    </div>
                    <span><i class="fa fa-message"></i></span>
                </header>
                <p class="bo-record-text">{{ $display($nomination->nomination_reason) }}</p>
            </section>

            <section class="bo-card bo-record-card bo-record-full">
                <header>
                    <div>
                        <span class="bo-card-kicker">Evidence</span>
                        <h2>Achievement Documents and Links</h2>
                    </div>
                    <span><i class="fa fa-file-lines"></i></span>
                </header>
                <div class="bo-evidence-grid">
                    <div>
                        <h3>Documents</h3>
                        @if (count($achievementDocuments))
                            <ul class="bo-record-links">
                                @foreach ($achievementDocuments as $document)
                                    <li>
                                        <a href="{{ $document['url'] ?? '#' }}" target="_blank" rel="noopener">
                                            <i class="fa fa-paperclip"></i>
                                            <span>{{ $display($document['name'] ?? null, 'Achievement document') }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="bo-record-text">No documents uploaded.</p>
                        @endif
                    </div>
                    <div>
                        <h3>Links</h3>
                        @if (count($achievementLinks))
                            <ul class="bo-record-links">
                                @foreach ($achievementLinks as $link)
                                    <li>
                                        <a href="{{ $link }}" target="_blank" rel="noopener">
                                            <i class="fa fa-link"></i>
                                            <span>{{ $link }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="bo-record-text">No achievement links provided.</p>
                        @endif
                    </div>
                </div>
            </section>

            <section class="bo-card bo-record-card bo-record-full">
                <header>
                    <div>
                        <span class="bo-card-kicker">Raw Data</span>
                        <h2>Stored Record Fields</h2>
                    </div>
                    <span><i class="fa fa-database"></i></span>
                </header>
                <dl class="bo-record-list bo-record-raw">
                    @foreach ($rawFields as $label => $value)
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd>{{ $display($value) }}</dd>
                        </div>
                    @endforeach
                    <div class="bo-record-wide">
                        <dt>Nominator User Agent</dt>
                        <dd>{{ $display($nomination->nominator_user_agent) }}</dd>
                    </div>
                </dl>
            </section>
        </main>
    </div>
</body>
</html>
