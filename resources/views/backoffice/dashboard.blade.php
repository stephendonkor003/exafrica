<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Back Office | Extraordinary Africans</title>
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
                <button class="bo-nav-item active" type="button" data-bo-tab="dashboard"><i class="fa fa-chart-line"></i><span>Dashboard</span></button>
                <button class="bo-nav-item" type="button" data-bo-tab="nominations"><i class="fa fa-clipboard-check"></i><span>Nominations</span></button>
                <button class="bo-nav-item" type="button" data-bo-tab="nominees"><i class="fa fa-users"></i><span>Nominees</span></button>
                <button class="bo-nav-item" type="button" data-bo-tab="voting"><i class="fa fa-square-poll-vertical"></i><span>Voting</span></button>
                <button class="bo-nav-item" type="button" data-bo-tab="categories"><i class="fa fa-layer-group"></i><span>Categories</span></button>
                <button class="bo-nav-item" type="button" data-bo-tab="phases"><i class="fa fa-calendar-days"></i><span>Phases</span></button>
                <button class="bo-nav-item" type="button" data-bo-tab="users"><i class="fa fa-user-shield"></i><span>Users</span></button>
            </nav>

            <form method="POST" action="{{ route('backoffice.logout') }}" class="bo-logout-form">
                @csrf
                <button type="submit"><i class="fa fa-arrow-right-from-bracket"></i><span>Sign Out</span></button>
            </form>
        </aside>

        <main class="bo-main">
            <header class="bo-topbar">
                <div>
                    <span class="bo-kicker">Operations Console</span>
                    <h1 id="boPageTitle">Dashboard</h1>
                </div>
                <div class="bo-topbar-actions">
                    <a href="{{ url('/') }}" class="bo-ghost-link"><i class="fa fa-arrow-up-right-from-square"></i><span>Public Site</span></a>
                </div>
                <div class="bo-user-chip">
                    <span>{{ strtoupper(substr($adminUser->name, 0, 1)) }}</span>
                    <div>
                        <strong>{{ $adminUser->name }}</strong>
                        <em>{{ $adminUser->email }}</em>
                    </div>
                </div>
            </header>

            <div class="bo-message" id="boMessage"></div>

            <section class="bo-panel active" id="bo-panel-dashboard">
                <div class="bo-command-strip">
                    <div>
                        <span class="bo-kicker">Program Control</span>
                        <h2>Manage the full awards operation from one workspace</h2>
                    </div>
                    <div class="bo-command-strip-icons" aria-hidden="true">
                        <span><i class="fa fa-clipboard-check"></i></span>
                        <span><i class="fa fa-users-gear"></i></span>
                        <span><i class="fa fa-chart-simple"></i></span>
                    </div>
                </div>

                <div class="bo-metric-grid" id="adminMetricGrid"></div>

                <div class="bo-visual-grid">
                    <article class="bo-card bo-visual-card bo-visual-wide">
                        <header>
                            <div>
                                <span class="bo-card-kicker">Voting Intelligence</span>
                                <h2>Votes by Category</h2>
                            </div>
                            <span id="boVoteCategoryCount">0</span>
                        </header>
                        <div class="bo-bar-chart" id="boCategoryVoteChart"></div>
                    </article>

                    <article class="bo-card bo-visual-card">
                        <header>
                            <div>
                                <span class="bo-card-kicker">Timeline</span>
                                <h2>Phase Flow</h2>
                            </div>
                            <span id="boPhaseCount">0</span>
                        </header>
                        <div class="bo-phase-track" id="boPhaseTimeline"></div>
                    </article>
                </div>

                <div class="bo-dashboard-grid">
                    <article class="bo-card">
                        <header>
                            <div><span class="bo-card-kicker">Schedule</span><h2>Phase Status</h2></div>
                        </header>
                        <div class="bo-list" id="boPhaseStatusList"></div>
                    </article>
                    <article class="bo-card">
                        <header>
                            <div><span class="bo-card-kicker">Performance</span><h2>Top Nominees</h2></div>
                            <span id="boTopNomineeCount">0</span>
                        </header>
                        <div class="bo-list" id="boTopNomineesList"></div>
                    </article>
                    <article class="bo-card">
                        <header>
                            <div><span class="bo-card-kicker">Category Health</span><h2>Category Votes</h2></div>
                        </header>
                        <div class="bo-list" id="boCategoryVotesList"></div>
                    </article>
                </div>
            </section>

            <section class="bo-panel" id="bo-panel-nominations">
                <div class="bo-panel-head">
                    <div>
                        <span class="bo-card-kicker">Review Queue</span>
                        <h2>Nomination Review</h2>
                    </div>
                    <div class="bo-table-controls">
                        <label class="bo-search"><i class="fa fa-magnifying-glass"></i><input data-bo-search="nominations" type="search" placeholder="Search nominations"></label>
                        <select id="boNominationStatus">
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="bo-table-wrap">
                    <table class="bo-table">
                        <thead><tr><th>Nominee</th><th>Country</th><th>Category</th><th>Status</th><th>Reason</th><th>Actions</th></tr></thead>
                        <tbody id="boNominationsBody"></tbody>
                    </table>
                </div>
            </section>

            <section class="bo-panel" id="bo-panel-nominees">
                <div class="bo-panel-head">
                    <div>
                        <span class="bo-card-kicker">Publishing Pipeline</span>
                        <h2>Nominee Publishing</h2>
                    </div>
                    <div class="bo-table-controls">
                        <label class="bo-search"><i class="fa fa-magnifying-glass"></i><input data-bo-search="nominees" type="search" placeholder="Search nominees"></label>
                        <select id="boNomineeStatus">
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>
                <div class="bo-table-wrap">
                    <table class="bo-table">
                        <thead><tr><th>Name</th><th>Country</th><th>Category</th><th>Status</th><th>Votes</th><th>Actions</th></tr></thead>
                        <tbody id="boNomineesBody"></tbody>
                    </table>
                </div>
            </section>

            <section class="bo-panel" id="bo-panel-voting">
                <div class="bo-panel-head">
                    <div>
                        <span class="bo-card-kicker">Voting Intelligence</span>
                        <h2>Voting Statistics</h2>
                    </div>
                    <div class="bo-table-controls">
                        <label class="bo-search"><i class="fa fa-magnifying-glass"></i><input data-bo-search="votingNominees" type="search" placeholder="Search nominees"></label>
                        <label class="bo-search"><i class="fa fa-shield-halved"></i><input data-bo-search="voteAudit" type="search" placeholder="Search vote audit"></label>
                        <select id="boVotingCategory">
                            <option value="">All categories</option>
                        </select>
                    </div>
                </div>

                <div class="bo-metric-grid bo-voting-metrics" id="boVotingMetricGrid"></div>

                <div class="bo-voting-dashboard-grid">
                    <article class="bo-card">
                        <header>
                            <div><span class="bo-card-kicker">Category Cards</span><h2>Category Vote Overview</h2></div>
                            <span id="boVotingCardCount">0</span>
                        </header>
                        <div class="bo-voting-category-cards" id="boVotingCategoryCards"></div>
                    </article>

                    <article class="bo-card">
                        <header>
                            <div><span class="bo-card-kicker">Graphs</span><h2>Category Vote Graphs</h2></div>
                        </header>
                        <div class="bo-voting-graph" id="boVotingCategoryGraph"></div>
                    </article>
                </div>

                <div class="bo-voting-grid">
                    <article class="bo-card">
                        <header>
                            <div><span class="bo-card-kicker">Category Totals</span><h2>Votes by Category</h2></div>
                            <span id="boVotingCategoryCount">0</span>
                        </header>
                        <div class="bo-table-wrap bo-table-wrap-compact">
                            <table class="bo-table bo-voting-table">
                                <thead><tr><th>Category</th><th>Nominees</th><th>Public</th><th>Judge</th><th>Total</th></tr></thead>
                                <tbody id="boVotingCategoriesBody"></tbody>
                            </table>
                        </div>
                    </article>

                    <article class="bo-card">
                        <header>
                            <div><span class="bo-card-kicker">Nominee Totals</span><h2>Votes by Nominee</h2></div>
                            <span id="boVotingNomineeCount">0</span>
                        </header>
                        <div class="bo-table-wrap bo-table-wrap-compact">
                            <table class="bo-table bo-voting-table">
                                <thead><tr><th>Nominee</th><th>Category</th><th>Status</th><th>Public</th><th>Judge</th><th>Total</th></tr></thead>
                                <tbody id="boVotingNomineesBody"></tbody>
                            </table>
                        </div>
                    </article>
                </div>

                <article class="bo-card bo-voting-rankings-card">
                    <header>
                        <div><span class="bo-card-kicker">Rankings</span><h2>Nominee Ranking by Category</h2></div>
                        <span id="boVotingRankingCount">0</span>
                    </header>
                    <div class="bo-ranking-grid" id="boVotingRankingGrid"></div>
                </article>

                <article class="bo-card bo-vote-audit-card">
                    <header>
                        <div><span class="bo-card-kicker">Audit Trail</span><h2>Voting Records</h2></div>
                        <span id="boVoteAuditCount">0</span>
                    </header>
                    <div class="bo-table-wrap bo-table-wrap-compact">
                        <table class="bo-table bo-audit-table">
                            <thead><tr><th>Date</th><th>Voter / Account</th><th>Nominee</th><th>Category</th><th>IP</th><th>Location</th><th>MAC / Device Key</th><th>Type</th></tr></thead>
                            <tbody id="boVoteAuditBody"></tbody>
                        </table>
                    </div>
                </article>
            </section>

            <section class="bo-panel" id="bo-panel-categories">
                <div class="bo-panel-head">
                    <div>
                        <span class="bo-card-kicker">Competition Structure</span>
                        <h2>Category Management</h2>
                    </div>
                    <label class="bo-search"><i class="fa fa-magnifying-glass"></i><input data-bo-search="categories" type="search" placeholder="Search categories"></label>
                </div>
                <form class="bo-form" id="boCategoryForm">
                    <input type="hidden" name="id">
                    <label><span>Name</span><input name="name" required type="text"></label>
                    <label><span>Position</span><input name="position" type="number" min="0"></label>
                    <label><span>Max Nominees</span><input name="max_nominees" type="number" min="1"></label>
                    <label><span>Icon</span><input name="icon" type="text" placeholder="fa-star"></label>
                    <label class="bo-form-wide"><span>Description</span><textarea name="description" rows="2"></textarea></label>
                    <button type="submit"><i class="fa fa-floppy-disk"></i><span>Save Category</span></button>
                </form>
                <div class="bo-table-wrap">
                    <table class="bo-table">
                        <thead><tr><th>Name</th><th>Position</th><th>Active</th><th>Actions</th></tr></thead>
                        <tbody id="boCategoriesBody"></tbody>
                    </table>
                </div>
            </section>

            <section class="bo-panel" id="bo-panel-phases">
                <div class="bo-panel-head">
                    <div>
                        <span class="bo-card-kicker">Program Timeline</span>
                        <h2>Voting Phases</h2>
                    </div>
                </div>
                <div class="bo-table-wrap">
                    <table class="bo-table">
                        <thead><tr><th>Name</th><th>Type</th><th>Dates</th><th>Active</th><th>Actions</th></tr></thead>
                        <tbody id="boPhasesBody"></tbody>
                    </table>
                </div>
            </section>

            <section class="bo-panel" id="bo-panel-users">
                <div class="bo-panel-head">
                    <div>
                        <span class="bo-card-kicker">Access Control</span>
                        <h2>User Management</h2>
                    </div>
                    <label class="bo-search"><i class="fa fa-magnifying-glass"></i><input data-bo-search="users" type="search" placeholder="Search users"></label>
                </div>
                <form class="bo-form" id="boUserForm">
                    <label><span>Name</span><input name="name" required type="text"></label>
                    <label><span>Email</span><input name="email" required type="email"></label>
                    <label><span>Password</span><input name="password" required type="password" minlength="12"></label>
                    <label><span>Role</span><select name="role_id" id="boUserRole" required></select></label>
                    <button type="submit"><i class="fa fa-user-plus"></i><span>Create User</span></button>
                </form>
                <div class="bo-table-wrap">
                    <table class="bo-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Actions</th></tr></thead>
                        <tbody id="boUsersBody"></tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        window.BackOffice = {
            apiToken: @json($apiToken),
            loginUrl: @json(route('backoffice.login')),
            nominationRecordBaseUrl: @json(url('/back-office/nominations')),
        };
    </script>
    <script src="{{ asset('js/backoffice.js') }}"></script>
</body>
</html>
