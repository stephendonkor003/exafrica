(function () {
    'use strict';

    const config = window.BackOffice || {};
    const state = {
        dashboard: null,
        nominations: [],
        nominees: [],
        votes: [],
        categories: [],
        phases: [],
        roles: [],
        users: [],
        search: {
            nominations: '',
            nominees: '',
            votingNominees: '',
            voteAudit: '',
            categories: '',
            users: '',
        },
    };

    document.addEventListener('DOMContentLoaded', function () {
        bindNavigation();
        bindFilters();
        bindSearch();
        bindForms();
        showInitialPanel();
        loadBackOffice();
    });

    function apiHeaders() {
        return {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + config.apiToken,
        };
    }

    async function apiRequest(path, options) {
        const response = await fetch('/api/v1' + path, {
            cache: 'no-store',
            headers: apiHeaders(),
            ...options,
        });
        const payload = await response.json().catch(function () {
            return { success: false, message: 'The server returned an unreadable response.' };
        });

        if (response.status === 401 || response.status === 403) {
            window.location.href = config.loginUrl || '/back-office/login';
            return payload;
        }

        if (!response.ok) {
            const error = new Error(payload.message || 'Request failed.');
            error.payload = payload;
            error.status = response.status;
            throw error;
        }

        return payload;
    }

    function bindNavigation() {
        document.querySelectorAll('.bo-nav-item').forEach(function (button) {
            button.addEventListener('click', function () {
                showPanel(button.dataset.boTab);
            });
        });
    }

    function bindFilters() {
        const nominationStatus = document.getElementById('boNominationStatus');
        if (nominationStatus) nominationStatus.addEventListener('change', loadBackOfficeNominations);

        const nomineeStatus = document.getElementById('boNomineeStatus');
        if (nomineeStatus) nomineeStatus.addEventListener('change', loadBackOfficeNominees);

        const votingCategory = document.getElementById('boVotingCategory');
        if (votingCategory) votingCategory.addEventListener('change', renderVotingStatistics);
    }

    function bindSearch() {
        document.querySelectorAll('[data-bo-search]').forEach(function (input) {
            input.addEventListener('input', function () {
                state.search[input.dataset.boSearch] = input.value.trim().toLowerCase();
                if (input.dataset.boSearch === 'nominations') renderBackOfficeNominations();
                if (input.dataset.boSearch === 'nominees') renderBackOfficeNominees();
                if (input.dataset.boSearch === 'votingNominees') renderVotingStatistics();
                if (input.dataset.boSearch === 'voteAudit') renderVotingStatistics();
                if (input.dataset.boSearch === 'categories') renderBackOfficeCategories();
                if (input.dataset.boSearch === 'users') renderBackOfficeUsers();
            });
        });
    }

    function bindForms() {
        const categoryForm = document.getElementById('boCategoryForm');
        if (categoryForm) {
            categoryForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                try {
                    setMessage('Saving category...', 'info');
                    await saveBackOfficeCategory(categoryForm);
                } catch (error) {
                    setMessage(formatError(error), 'error');
                }
            });
        }

        const userForm = document.getElementById('boUserForm');
        if (userForm) {
            userForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                await createBackOfficeUser(userForm);
            });
        }
    }

    function showPanel(tab) {
        document.querySelectorAll('.bo-nav-item').forEach(function (button) {
            button.classList.toggle('active', button.dataset.boTab === tab);
        });
        document.querySelectorAll('.bo-panel').forEach(function (panel) {
            panel.classList.toggle('active', panel.id === 'bo-panel-' + tab);
        });
        const titles = {
            dashboard: 'Dashboard',
            nominations: 'Nominations',
            nominees: 'Nominees',
            voting: 'Voting',
            categories: 'Categories',
            phases: 'Phases',
            users: 'Users',
        };
        const title = document.getElementById('boPageTitle');
        if (title) title.textContent = titles[tab] || tab.charAt(0).toUpperCase() + tab.slice(1);
    }

    function showInitialPanel() {
        const tab = String(window.location.hash || '').replace('#', '').trim();
        const validTabs = ['dashboard', 'nominations', 'nominees', 'voting', 'categories', 'phases', 'users'];

        if (validTabs.includes(tab)) showPanel(tab);
    }

    async function loadBackOffice() {
        setMessage('Loading Back Office data...', 'info');
        try {
            await Promise.all([
                loadAdminDashboard(),
                loadBackOfficeVotes(),
                loadBackOfficeNominations(),
                loadBackOfficeNominees(),
                loadBackOfficeCategories(),
                loadBackOfficePhases(),
                loadBackOfficeRoles(),
                loadBackOfficeUsers(),
            ]);
            setMessage('Back Office data loaded.', 'success');
        } catch (error) {
            setMessage(formatError(error), 'error');
        }
    }

    async function loadAdminDashboard() {
        const payload = await apiRequest('/dashboard/admin', { method: 'GET' });
        state.dashboard = payload.data;
        renderAdminDashboard();
    }

    function renderAdminDashboard() {
        const dashboard = state.dashboard || {};
        const summary = dashboard.summary || {};
        const grid = document.getElementById('adminMetricGrid');
        if (grid) {
            grid.innerHTML = [
                metricCard('Nominees', summary.total_nominees || 0, 'fa-users', 'teal'),
                metricCard('Votes', summary.total_votes || 0, 'fa-check-to-slot', 'gold'),
                metricCard('Categories', summary.total_categories || 0, 'fa-layer-group', 'rose'),
                metricCard('Active Phase', summary.active_phase || 'None', 'fa-calendar-check', 'indigo'),
            ].join('');
        }

        renderList('boPhaseStatusList', dashboard.phase_status || [], function (phase) {
            return listItem(phase.name, formatDate(phase.start_date) + ' - ' + formatDate(phase.end_date), phase.status);
        }, 'No phases configured.');

        renderList('boTopNomineesList', dashboard.top_nominees || [], function (nominee) {
            return listItem(nominee.full_name, Number(nominee.vote_count || 0) + ' votes', 'Ranked');
        }, 'No nominees yet.');

        renderList('boCategoryVotesList', dashboard.votes_by_category || [], function (category) {
            return listItem(category.category, Number(category.vote_count || 0) + ' votes', 'Category');
        }, 'No category votes yet.');

        renderCategoryVoteChart(dashboard.votes_by_category || []);
        renderPhaseTimeline(dashboard.phase_status || []);
        populateVotingCategoryFilter(dashboard.category_vote_stats || []);
        renderVotingStatistics();

        setCount('boPhaseCount', dashboard.phase_status?.length || 0);
        setCount('boTopNomineeCount', dashboard.top_nominees?.length || 0);
        setCount('boVoteCategoryCount', dashboard.votes_by_category?.length || 0);
    }

    function populateVotingCategoryFilter(categories) {
        const select = document.getElementById('boVotingCategory');
        if (!select) return;

        const current = select.value;
        select.innerHTML = '<option value="">All categories</option>' + categories.map(function (category) {
            return '<option value="' + category.id + '">' + escapeHtml(category.category) + '</option>';
        }).join('');

        if (current && categories.some(function (category) { return String(category.id) === String(current); })) {
            select.value = current;
        }
    }

    function renderVotingStatistics() {
        const dashboard = state.dashboard || {};
        const categoryStats = dashboard.category_vote_stats || [];
        const nomineeStats = dashboard.nominee_vote_stats || [];
        const selectedCategory = document.getElementById('boVotingCategory')?.value || '';
        const search = state.search.votingNominees || '';

        const filteredNominees = nomineeStats.filter(function (nominee) {
            const inCategory = !selectedCategory || String(nominee.category_id) === String(selectedCategory);
            const searchText = [
                nominee.full_name,
                nominee.country,
                nominee.category,
                nominee.status,
                nominee.public_votes,
                nominee.judge_votes,
                nominee.total_votes,
            ].join(' ').toLowerCase();
            return inCategory && (!search || searchText.includes(search));
        });

        const filteredCategories = selectedCategory
            ? categoryStats.filter(function (category) { return String(category.id) === String(selectedCategory); })
            : categoryStats;

        renderVotingMetrics(filteredCategories, filteredNominees);
        renderVotingCategoryCards(filteredCategories);
        renderVotingCategoryGraph(filteredCategories);
        renderVotingCategoryTable(filteredCategories);
        renderVotingNomineeTable(filteredNominees);
        renderVotingRankings(filteredNominees);
        renderVoteAuditTable();

        setCount('boVotingCategoryCount', filteredCategories.length);
        setCount('boVotingNomineeCount', filteredNominees.length);
        setCount('boVotingCardCount', filteredCategories.length);
        setCount('boVotingRankingCount', filteredNominees.length);
    }

    async function loadBackOfficeVotes() {
        const payload = await apiRequest('/votes', { method: 'GET' });
        state.votes = payload.data || [];
        renderVoteAuditTable();
    }

    function renderVoteAuditTable() {
        const body = document.getElementById('boVoteAuditBody');
        if (!body) return;

        const selectedCategory = document.getElementById('boVotingCategory')?.value || '';
        const search = state.search.voteAudit || '';
        const votes = state.votes.filter(function (vote) {
            const inCategory = !selectedCategory || String(vote.category_id) === String(selectedCategory);
            const searchText = [
                vote.account_user?.name,
                vote.account_user?.email,
                vote.voter_id,
                vote.nominee?.full_name,
                vote.nominee?.country,
                vote.category?.name,
                vote.ip_address,
                vote.location,
                vote.mac_address,
                vote.vote_type,
                vote.created_at,
            ].join(' ').toLowerCase();
            return inCategory && (!search || searchText.includes(search));
        });

        setCount('boVoteAuditCount', votes.length);

        if (!votes.length) return setTable(body, 8, 'No vote audit records found.');

        body.innerHTML = votes.map(function (vote) {
            const account = vote.account_user
                ? '<strong>' + escapeHtml(vote.account_user.name) + '</strong><span class="bo-table-subtext">' + escapeHtml(vote.account_user.email) + '</span>'
                : '<strong>Anonymous device</strong><span class="bo-table-subtext">Voter record #' + escapeHtml(vote.voter_id || 'Unknown') + '</span>';

            return '<tr>' +
                '<td>' + escapeHtml(formatDate(vote.created_at)) + '</td>' +
                '<td>' + account + '</td>' +
                '<td><strong>' + escapeHtml(vote.nominee?.full_name || 'Unknown nominee') + '</strong><span class="bo-table-subtext">' + escapeHtml(vote.nominee?.country || '') + '</span></td>' +
                '<td>' + escapeHtml(vote.category?.name || 'Unknown category') + '</td>' +
                '<td><code>' + escapeHtml(vote.ip_address || 'Unknown') + '</code></td>' +
                '<td>' + escapeHtml(vote.location || 'Unknown') + '</td>' +
                '<td><code class="bo-device-key">' + escapeHtml(vote.mac_address || 'Unknown') + '</code></td>' +
                '<td>' + statusPill(vote.vote_type || 'public_vote') + '</td>' +
                '</tr>';
        }).join('');
    }

    function renderVotingMetrics(categories, nominees) {
        const grid = document.getElementById('boVotingMetricGrid');
        if (!grid) return;

        const totalVotes = categories.reduce(function (sum, category) {
            return sum + Number(category.total_votes || 0);
        }, 0);
        const publicVotes = categories.reduce(function (sum, category) {
            return sum + Number(category.public_votes || 0);
        }, 0);
        const judgeVotes = categories.reduce(function (sum, category) {
            return sum + Number(category.judge_votes || 0);
        }, 0);
        const leadingNominee = nominees.reduce(function (leader, nominee) {
            return Number(nominee.total_votes || 0) > Number(leader?.total_votes || 0) ? nominee : leader;
        }, null);

        grid.innerHTML = [
            metricCard('Total Votes', totalVotes, 'fa-check-to-slot', 'gold'),
            metricCard('Public Votes', publicVotes, 'fa-users', 'teal'),
            metricCard('Judge Votes', judgeVotes, 'fa-gavel', 'rose'),
            metricCard('Leading Nominee', leadingNominee ? leadingNominee.full_name : 'None', 'fa-ranking-star', 'indigo'),
        ].join('');
    }

    function renderVotingCategoryCards(categories) {
        const el = document.getElementById('boVotingCategoryCards');
        if (!el) return;

        if (!categories.length) {
            el.innerHTML = '<div class="bo-empty">No category voting data yet.</div>';
            return;
        }

        const maxVotes = Math.max(...categories.map(function (category) {
            return Number(category.total_votes || 0);
        }), 1);

        el.innerHTML = categories.map(function (category) {
            const total = Number(category.total_votes || 0);
            const percent = Math.round((total / maxVotes) * 100);
            return '<div class="bo-voting-category-card">' +
                '<div><span>Category</span><strong>' + escapeHtml(category.category) + '</strong></div>' +
                '<div class="bo-voting-card-stats">' +
                    '<span><b>' + Number(category.nominee_count || 0) + '</b> nominees</span>' +
                    '<span><b>' + Number(category.public_votes || 0) + '</b> public</span>' +
                    '<span><b>' + Number(category.judge_votes || 0) + '</b> judge</span>' +
                '</div>' +
                '<div class="bo-voting-card-total"><strong>' + total + '</strong><span>Total votes</span></div>' +
                '<div class="bo-voting-card-bar"><span style="width:' + Math.max(4, percent) + '%;"></span></div>' +
                '</div>';
        }).join('');
    }

    function renderVotingCategoryGraph(categories) {
        const el = document.getElementById('boVotingCategoryGraph');
        if (!el) return;

        if (!categories.length) {
            el.innerHTML = '<div class="bo-empty">No graph data yet.</div>';
            return;
        }

        const maxVotes = Math.max(...categories.map(function (category) {
            return Number(category.total_votes || 0);
        }), 1);

        el.innerHTML = categories.map(function (category) {
            const publicVotes = Number(category.public_votes || 0);
            const judgeVotes = Number(category.judge_votes || 0);
            const total = Number(category.total_votes || 0);
            const width = Math.max(4, Math.round((total / maxVotes) * 100));
            const publicWidth = total > 0 ? Math.round((publicVotes / total) * 100) : 0;
            const judgeWidth = total > 0 ? Math.max(0, 100 - publicWidth) : 0;

            return '<div class="bo-voting-graph-row">' +
                '<div class="bo-voting-graph-label"><strong>' + escapeHtml(category.category) + '</strong><span>' + total + ' votes</span></div>' +
                '<div class="bo-voting-graph-track"><div class="bo-voting-graph-stack" style="width:' + width + '%;">' +
                    '<span class="public" style="width:' + publicWidth + '%;"></span>' +
                    '<span class="judge" style="width:' + judgeWidth + '%;"></span>' +
                '</div></div>' +
                '<div class="bo-voting-graph-key"><span><i class="public"></i>' + publicVotes + '</span><span><i class="judge"></i>' + judgeVotes + '</span></div>' +
                '</div>';
        }).join('');
    }

    function renderVotingCategoryTable(categories) {
        const body = document.getElementById('boVotingCategoriesBody');
        if (!body) return;

        if (!categories.length) return setTable(body, 5, 'No category voting statistics found.');

        body.innerHTML = categories.map(function (category) {
            return '<tr><td><strong>' + escapeHtml(category.category) + '</strong></td><td>' + Number(category.nominee_count || 0) + '</td><td>' + Number(category.public_votes || 0) + '</td><td>' + Number(category.judge_votes || 0) + '</td><td><strong>' + Number(category.total_votes || 0) + '</strong></td></tr>';
        }).join('');
    }

    function renderVotingNomineeTable(nominees) {
        const body = document.getElementById('boVotingNomineesBody');
        if (!body) return;

        if (!nominees.length) return setTable(body, 6, 'No nominee voting statistics found.');

        body.innerHTML = nominees.map(function (nominee) {
            return '<tr><td><strong>' + escapeHtml(nominee.full_name) + '</strong><span class="bo-table-subtext">' + escapeHtml(nominee.country || 'Country not provided') + '</span></td><td>' + escapeHtml(nominee.category || 'Uncategorised') + '</td><td>' + statusPill(nominee.status || 'pending') + '</td><td>' + Number(nominee.public_votes || 0) + '</td><td>' + Number(nominee.judge_votes || 0) + '</td><td><strong>' + Number(nominee.total_votes || 0) + '</strong></td></tr>';
        }).join('');
    }

    function renderVotingRankings(nominees) {
        const el = document.getElementById('boVotingRankingGrid');
        if (!el) return;

        if (!nominees.length) {
            el.innerHTML = '<div class="bo-empty">No nominee rankings yet.</div>';
            return;
        }

        const byCategory = nominees.reduce(function (groups, nominee) {
            const category = nominee.category || 'Uncategorised';
            if (!groups[category]) groups[category] = [];
            groups[category].push(nominee);
            return groups;
        }, {});

        el.innerHTML = Object.keys(byCategory).sort().map(function (category) {
            const ranked = byCategory[category].slice().sort(function (a, b) {
                return Number(b.total_votes || 0) - Number(a.total_votes || 0) || String(a.full_name || '').localeCompare(String(b.full_name || ''));
            });

            return '<section class="bo-ranking-card">' +
                '<header><div><span>Category</span><strong>' + escapeHtml(category) + '</strong></div><em>' + ranked.length + ' nominees</em></header>' +
                '<div class="bo-ranking-list">' + ranked.map(function (nominee, index) {
                    return '<div class="bo-ranking-row">' +
                        '<span class="bo-rank-number">' + (index + 1) + '</span>' +
                        '<div><strong>' + escapeHtml(nominee.full_name) + '</strong><span>' + escapeHtml(nominee.country || 'Country not provided') + ' · ' + escapeHtml(nominee.status || 'pending') + '</span></div>' +
                        '<em>' + Number(nominee.total_votes || 0) + ' votes</em>' +
                    '</div>';
                }).join('') + '</div>' +
                '</section>';
        }).join('');
    }

    function metricCard(label, value, icon, tone) {
        return '<div class="bo-metric-card ' + escapeHtml(tone) + '"><div><span>' + escapeHtml(label) + '</span><strong>' + escapeHtml(value) + '</strong></div><div class="bo-metric-icon"><i class="fa ' + escapeHtml(icon) + '"></i></div></div>';
    }

    function listItem(title, meta, tag) {
        return '<div class="bo-list-item"><div><strong>' + escapeHtml(title) + '</strong><span>' + escapeHtml(meta) + '</span></div><em>' + escapeHtml(tag) + '</em></div>';
    }

    function renderList(id, items, renderer, emptyMessage) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = items.length
            ? items.map(renderer).join('')
            : '<div class="bo-empty">' + escapeHtml(emptyMessage) + '</div>';
    }

    function renderCategoryVoteChart(items) {
        const el = document.getElementById('boCategoryVoteChart');
        if (!el) return;
        if (!items.length) {
            el.innerHTML = '<div class="bo-empty">No voting data yet.</div>';
            return;
        }

        const maxVotes = Math.max(...items.map(function (item) {
            return Number(item.vote_count || 0);
        }), 1);

        el.innerHTML = items.map(function (item) {
            const votes = Number(item.vote_count || 0);
            const percent = Math.max(4, Math.round((votes / maxVotes) * 100));
            return '<div class="bo-bar-row"><div class="bo-bar-label">' + escapeHtml(item.category) + '</div><div class="bo-bar-track"><div class="bo-bar-fill" style="--value:' + percent + '%;"></div></div><div class="bo-bar-value">' + votes + '</div></div>';
        }).join('');
    }

    function renderPhaseTimeline(items) {
        const el = document.getElementById('boPhaseTimeline');
        if (!el) return;
        if (!items.length) {
            el.innerHTML = '<div class="bo-empty">No phase timeline configured.</div>';
            return;
        }

        el.innerHTML = items.map(function (phase) {
            const status = String(phase.status || '').toLowerCase();
            const icon = status === 'active' ? 'fa-play' : (status === 'completed' ? 'fa-check' : 'fa-clock');
            return '<div class="bo-phase-item ' + escapeHtml(status) + '"><span class="bo-phase-icon"><i class="fa ' + icon + '"></i></span><div><strong>' + escapeHtml(phase.name) + '</strong><span>' + escapeHtml(phase.type) + ' - ' + escapeHtml(status) + '</span></div></div>';
        }).join('');
    }

    async function loadBackOfficeNominations() {
        const status = document.getElementById('boNominationStatus')?.value || '';
        const query = status ? '?evaluation_status=' + encodeURIComponent(status) : '';
        const payload = await apiRequest('/nominations' + query, { method: 'GET' });
        state.nominations = payload.data || [];
        renderBackOfficeNominations();
    }

    function renderBackOfficeNominations() {
        const body = document.getElementById('boNominationsBody');
        if (!body) return;
        const items = filterItems('nominations', state.nominations, function (nomination) {
            return [nomination.nominee?.full_name, nomination.nominee?.country, nomination.category?.name, nomination.evaluation_status, nomination.nomination_reason].join(' ');
        });
        if (!items.length) return setTable(body, 6, 'No nominations found.');
        body.innerHTML = items.map(function (nomination) {
            return '<tr><td><strong>' + escapeHtml(nomination.nominee?.full_name) + '</strong></td><td>' + escapeHtml(nomination.nominee?.country || 'Not provided') + '</td><td>' + escapeHtml(nomination.category?.name) + '</td><td>' + statusPill(nomination.evaluation_status) + '</td><td>' + escapeHtml(nomination.nomination_reason) + '</td><td class="bo-actions">' +
                recordLink('Show Record', nomination.id) +
                actionButton('Approve', 'approve-nomination', nomination.id, nomination.evaluation_status !== 'pending') +
                actionButton('Reject', 'reject-nomination', nomination.id, nomination.evaluation_status !== 'pending') +
                '</td></tr>';
        }).join('');
        bindActionButtons(body);
    }

    async function loadBackOfficeNominees() {
        const status = document.getElementById('boNomineeStatus')?.value || '';
        const query = status ? '?status=' + encodeURIComponent(status) : '';
        const payload = await apiRequest('/nominees' + query, { method: 'GET' });
        state.nominees = payload.data || [];
        renderBackOfficeNominees();
    }

    function renderBackOfficeNominees() {
        const body = document.getElementById('boNomineesBody');
        if (!body) return;
        const items = filterItems('nominees', state.nominees, function (nominee) {
            return [nominee.full_name, nominee.country, nominee.category?.name, nominee.status, nominee.vote_count].join(' ');
        });
        if (!items.length) return setTable(body, 6, 'No nominees found.');
        body.innerHTML = items.map(function (nominee) {
            return '<tr><td><strong>' + escapeHtml(nominee.full_name) + '</strong></td><td>' + escapeHtml(nominee.country || 'Not provided') + '</td><td>' + escapeHtml(nominee.category?.name) + '</td><td>' + statusPill(nominee.status) + '</td><td><strong>' + Number(nominee.vote_count || 0) + '</strong></td><td class="bo-actions">' +
                actionButton('Approve', 'approve-nominee', nominee.id, nominee.status === 'approved' || nominee.status === 'published') +
                actionButton('Publish', 'publish-nominee', nominee.id, nominee.status !== 'approved') +
                actionButton('Reject', 'reject-nominee', nominee.id, nominee.status === 'rejected') +
                '</td></tr>';
        }).join('');
        bindActionButtons(body);
    }

    async function loadBackOfficeCategories() {
        const payload = await apiRequest('/categories?per_page=100', { method: 'GET' });
        state.categories = payload.data || [];
        renderBackOfficeCategories();
    }

    function renderBackOfficeCategories() {
        const body = document.getElementById('boCategoriesBody');
        if (!body) return;
        const items = filterItems('categories', state.categories, function (category) {
            return [category.name, category.description, category.icon, category.position].join(' ');
        });
        if (!items.length) return setTable(body, 4, 'No categories found.');
        body.innerHTML = items.map(function (category) {
            return '<tr><td><strong>' + escapeHtml(category.name) + '</strong><span class="bo-table-subtext">' + escapeHtml(category.description || '') + '</span></td><td>' + Number(category.position || 0) + '</td><td>' + statusPill(category.is_active ? 'active' : 'inactive') + '</td><td class="bo-actions">' +
                actionButton('Edit', 'edit-category', category.id, false) +
                actionButton(category.is_active ? 'Disable' : 'Enable', 'toggle-category', category.id, false) +
                '</td></tr>';
        }).join('');
        bindActionButtons(body);
    }

    async function loadBackOfficePhases() {
        const payload = await apiRequest('/voting-phases', { method: 'GET' });
        state.phases = payload.data || [];
        renderBackOfficePhases();
    }

    function renderBackOfficePhases() {
        const body = document.getElementById('boPhasesBody');
        if (!body) return;
        if (!state.phases.length) return setTable(body, 5, 'No phases found.');
        body.innerHTML = state.phases.map(function (phase) {
            return '<tr><td><strong>' + escapeHtml(phase.name) + '</strong></td><td>' + escapeHtml(phase.phase_type) + '</td><td>' + escapeHtml(formatDate(phase.start_date)) + '<br>' + escapeHtml(formatDate(phase.end_date)) + '</td><td>' + statusPill(phase.is_active ? 'active' : 'inactive') + '</td><td class="bo-actions">' +
                actionButton('Activate', 'activate-phase', phase.id, phase.is_active) +
                '</td></tr>';
        }).join('');
        bindActionButtons(body);
    }

    async function loadBackOfficeRoles() {
        const payload = await apiRequest('/roles', { method: 'GET' });
        state.roles = payload.data || [];
        const select = document.getElementById('boUserRole');
        if (!select) return;
        select.innerHTML = '<option value="">Select role</option>' + state.roles.map(function (role) {
            return '<option value="' + role.id + '">' + escapeHtml(role.name) + '</option>';
        }).join('');
    }

    async function loadBackOfficeUsers() {
        const payload = await apiRequest('/users', { method: 'GET' });
        state.users = payload.data || [];
        renderBackOfficeUsers();
    }

    function renderBackOfficeUsers() {
        const body = document.getElementById('boUsersBody');
        if (!body) return;
        const items = filterItems('users', state.users, function (user) {
            return [user.name, user.email, user.role?.name, user.is_active ? 'active' : 'inactive'].join(' ');
        });
        if (!items.length) return setTable(body, 5, 'No users found.');
        body.innerHTML = items.map(function (user) {
            return '<tr><td><strong>' + escapeHtml(user.name) + '</strong></td><td>' + escapeHtml(user.email) + '</td><td>' + escapeHtml(user.role?.name) + '</td><td>' + statusPill(user.is_active ? 'active' : 'inactive') + '</td><td class="bo-actions">' +
                actionButton(user.is_active ? 'Deactivate' : 'Activate', 'toggle-user', user.id, false) +
                '</td></tr>';
        }).join('');
        bindActionButtons(body);
    }

    function bindActionButtons(scope) {
        scope.querySelectorAll('.bo-action[data-action]').forEach(function (button) {
            button.addEventListener('click', async function () {
                await handleAction(button.dataset.action, button.dataset.id);
            });
        });
    }

    async function handleAction(action, id) {
        try {
            if (action === 'approve-nomination') await apiRequest('/nominations/' + id + '/approve', { method: 'POST' });
            if (action === 'reject-nomination') await apiRequest('/nominations/' + id + '/evaluate', { method: 'POST', body: JSON.stringify({ evaluation_status: 'rejected', evaluator_notes: 'Rejected from back office.' }) });
            if (action === 'approve-nominee') await apiRequest('/nominees/' + id + '/approve', { method: 'POST' });
            if (action === 'publish-nominee') await apiRequest('/nominees/' + id + '/publish', { method: 'POST' });
            if (action === 'reject-nominee') await apiRequest('/nominees/' + id + '/reject', { method: 'POST', body: JSON.stringify({ rejection_reason: 'Rejected from back office.' }) });
            if (action === 'activate-phase') await apiRequest('/voting-phases/' + id + '/activate', { method: 'POST' });
            if (action === 'edit-category') return fillCategoryForm(id);
            if (action === 'toggle-category') await toggleCategory(id);
            if (action === 'toggle-user') await toggleUser(id);
            await loadBackOffice();
        } catch (error) {
            setMessage(formatError(error), 'error');
        }
    }

    function fillCategoryForm(id) {
        const category = state.categories.find(function (item) { return String(item.id) === String(id); });
        const form = document.getElementById('boCategoryForm');
        if (!category || !form) return;
        form.elements.id.value = category.id;
        form.elements.name.value = category.name || '';
        form.elements.position.value = category.position || 0;
        form.elements.max_nominees.value = category.max_nominees || 10;
        form.elements.icon.value = category.icon || '';
        form.elements.description.value = category.description || '';
        showPanel('categories');
        setMessage('Editing ' + category.name + '.', 'info');
    }

    async function saveBackOfficeCategory(form) {
        const payload = categoryFormPayload(form);
        const id = payload.id;
        delete payload.id;

        if (id) {
            await apiRequest('/categories/' + id, { method: 'PUT', body: JSON.stringify(payload) });
            setMessage('Category updated.', 'success');
        } else {
            await apiRequest('/categories', { method: 'POST', body: JSON.stringify(payload) });
            setMessage('Category created.', 'success');
        }

        form.reset();
        await loadBackOfficeCategories();
        await loadAdminDashboard();
    }

    function categoryFormPayload(form) {
        const data = new FormData(form);

        return {
            id: String(data.get('id') || '').trim(),
            name: String(data.get('name') || '').trim(),
            position: Number(data.get('position') || 0),
            max_nominees: Number(data.get('max_nominees') || 10),
            icon: String(data.get('icon') || '').trim(),
            description: String(data.get('description') || '').trim(),
        };
    }

    async function toggleCategory(id) {
        const category = state.categories.find(function (item) { return String(item.id) === String(id); });
        if (!category) return;
        await apiRequest('/categories/' + id, { method: 'PUT', body: JSON.stringify({ is_active: !category.is_active }) });
    }

    async function createBackOfficeUser(form) {
        await apiRequest('/users', { method: 'POST', body: JSON.stringify(formPayload(form)) });
        form.reset();
        setMessage('User created.', 'success');
        await loadBackOfficeUsers();
    }

    async function toggleUser(id) {
        const user = state.users.find(function (item) { return String(item.id) === String(id); });
        if (!user) return;
        await apiRequest('/users/' + id, { method: 'PUT', body: JSON.stringify({ is_active: !user.is_active }) });
    }

    function formPayload(form) {
        const data = new FormData(form);
        const payload = {};
        data.forEach(function (value, key) {
            if (String(value).trim() !== '') payload[key] = value;
        });
        return payload;
    }

    function setTable(body, colspan, message) {
        body.innerHTML = '<tr><td colspan="' + colspan + '"><div class="bo-empty">' + escapeHtml(message) + '</div></td></tr>';
    }

    function filterItems(type, items, serializer) {
        const search = state.search[type] || '';
        if (!search) return items;
        return items.filter(function (item) {
            return serializer(item).toLowerCase().includes(search);
        });
    }

    function actionButton(label, action, id, disabled) {
        const icons = {
            'approve-nomination': 'fa-check',
            'reject-nomination': 'fa-xmark',
            'approve-nominee': 'fa-thumbs-up',
            'publish-nominee': 'fa-bullhorn',
            'reject-nominee': 'fa-ban',
            'edit-category': 'fa-pen',
            'toggle-category': label === 'Disable' ? 'fa-toggle-off' : 'fa-toggle-on',
            'activate-phase': 'fa-play',
            'edit-user': 'fa-user-pen',
            'toggle-user': label === 'Disable' ? 'fa-user-slash' : 'fa-user-check',
        };
        const icon = icons[action] || 'fa-arrow-right';
        return '<button type="button" class="bo-action" data-action="' + action + '" data-id="' + id + '"' + (disabled ? ' disabled' : '') + '><i class="fa ' + icon + '" aria-hidden="true"></i><span>' + escapeHtml(label) + '</span></button>';
    }

    function recordLink(label, id) {
        const baseUrl = String(config.nominationRecordBaseUrl || '/back-office/nominations').replace(/\/$/, '');

        return '<a class="bo-action bo-action-link" href="' + baseUrl + '/' + encodeURIComponent(id) + '"><i class="fa fa-folder-open" aria-hidden="true"></i><span>' + escapeHtml(label) + '</span></a>';
    }

    function statusPill(status) {
        return '<span class="bo-status ' + escapeHtml(status) + '">' + escapeHtml(status) + '</span>';
    }

    function setCount(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = Number(value || 0);
    }

    function setMessage(message, type) {
        const el = document.getElementById('boMessage');
        if (!el) return;
        el.textContent = message || '';
        el.className = 'bo-message' + (message ? ' is-active' : '') + (type ? ' ' + type : '');
        if (type === 'success') {
            window.setTimeout(function () {
                if (el.textContent === message) setMessage('', '');
            }, 2200);
        }
    }

    function formatDate(value) {
        if (!value) return '';
        return String(value).replace('T', ' ').slice(0, 16);
    }

    function formatError(error) {
        if (error.payload && error.payload.errors) return Object.values(error.payload.errors).flat().join(' ');
        return error.message || 'Something went wrong.';
    }

    function escapeHtml(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
})();
