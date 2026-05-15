/* =============================================
   EXTRAORDINARY AFRICAN — MAIN JAVASCRIPT
   ============================================= */

(function () {
    'use strict';

    /* ---- State ---- */
    let currentSection = null;
    let carouselIndex = 0;
    let totalSlides = 0;

    function getSlidesVisible() {
        if (window.innerWidth <= 768) return 1;
        if (window.innerWidth <= 1024) return 2;
        return 3;
    }

    const apiState = {
        token: localStorage.getItem('ea_token') || '',
        user: JSON.parse(localStorage.getItem('ea_user') || 'null'),
        categories: [],
    };

    function initBackendApp() {
        bindAuthForms();
        bindNominationForm();
        bindVotingControls();
        bindBackOfficeControls();
        updateAuthUi();

        if (apiState.token) {
            loadCurrentUser().finally(loadCategories);
        }
    }

    function apiHeaders() {
        const headers = { 'Accept': 'application/json', 'Content-Type': 'application/json' };
        if (apiState.token) headers.Authorization = 'Bearer ' + apiState.token;
        return headers;
    }

    async function apiRequest(path, options) {
        const response = await fetch('/api/v1' + path, { headers: apiHeaders(), ...options });
        const payload = await response.json().catch(function () {
            return { success: false, message: 'The server returned an unreadable response.' };
        });

        if (!response.ok) {
            const error = new Error(payload.message || 'Request failed.');
            error.payload = payload;
            error.status = response.status;
            throw error;
        }

        return payload;
    }

    function formPayload(form) {
        const data = new FormData(form);
        const payload = {};
        data.forEach(function (value, key) {
            if (String(value).trim() !== '') payload[key] = value;
        });
        return payload;
    }

    function setMessage(id, message, type) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = message || '';
        el.className = 'form-message' + (type ? ' ' + type : '');
    }

    function storeSession(data) {
        apiState.token = data.token;
        apiState.user = data.user;
        localStorage.setItem('ea_token', apiState.token);
        localStorage.setItem('ea_user', JSON.stringify(apiState.user));
        updateAuthUi();
    }

    function clearSession() {
        apiState.token = '';
        apiState.user = null;
        localStorage.removeItem('ea_token');
        localStorage.removeItem('ea_user');
        updateAuthUi();
    }

    function updateAuthUi() {
        const signedIn = Boolean(apiState.token && apiState.user);
        const label = signedIn
            ? 'Signed in as ' + apiState.user.name + ' (' + apiState.user.email + ')'
            : 'You are not signed in. Create an account or sign in before nominating or voting.';

        ['authStatus', 'nominationAuthStatus', 'votingAuthStatus', 'backofficeAuthStatus'].forEach(function (id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = label;
            el.classList.toggle('is-ok', signedIn);
        });

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.style.display = signedIn ? '' : 'none';

        const nominationForm = document.getElementById('nominationForm');
        if (nominationForm) nominationForm.classList.toggle('is-disabled', !signedIn);
    }

    async function loadCurrentUser() {
        try {
            const payload = await apiRequest('/auth/me', { method: 'GET' });
            apiState.user = payload.data;
            localStorage.setItem('ea_user', JSON.stringify(apiState.user));
            updateAuthUi();
        } catch (error) {
            clearSession();
        }
    }

    function bindAuthForms() {
        const registerForm = document.getElementById('registerForm');
        const loginForm = document.getElementById('loginForm');
        const logoutBtn = document.getElementById('logoutBtn');

        if (registerForm) {
            registerForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                setMessage('registerMessage', 'Creating account...', 'info');
                try {
                    const payload = await apiRequest('/auth/register', {
                        method: 'POST',
                        body: JSON.stringify(formPayload(registerForm)),
                    });
                    storeSession(payload.data);
                    registerForm.reset();
                    setMessage('registerMessage', 'Account created. You can now nominate and vote.', 'success');
                    await loadCategories();
                } catch (error) {
                    setMessage('registerMessage', formatError(error), 'error');
                }
            });
        }

        if (loginForm) {
            loginForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                setMessage('loginMessage', 'Signing in...', 'info');
                try {
                    const payload = await apiRequest('/auth/login', {
                        method: 'POST',
                        body: JSON.stringify(formPayload(loginForm)),
                    });
                    storeSession(payload.data);
                    loginForm.reset();
                    setMessage('loginMessage', 'Signed in successfully.', 'success');
                    await loadCategories();
                } catch (error) {
                    setMessage('loginMessage', formatError(error), 'error');
                }
            });
        }

        if (logoutBtn) {
            logoutBtn.addEventListener('click', async function () {
                try {
                    if (apiState.token) await apiRequest('/auth/logout', { method: 'POST' });
                } catch (error) {}
                clearSession();
            });
        }
    }

    async function loadCategories() {
        if (!apiState.token) {
            populateCategorySelects([]);
            return;
        }
        try {
            const payload = await apiRequest('/categories?per_page=100', { method: 'GET' });
            apiState.categories = payload.data || [];
            populateCategorySelects(apiState.categories);
        } catch (error) {
            if (error.status === 401) clearSession();
            populateCategorySelects([]);
        }
    }

    function populateCategorySelects(categories) {
        const selects = [document.getElementById('nominationCategory'), document.getElementById('votingCategory')].filter(Boolean);
        selects.forEach(function (select) {
            const current = select.value;
            select.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = categories.length ? 'Select a category' : 'Sign in to load categories';
            select.appendChild(placeholder);
            categories.forEach(function (category) {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                select.appendChild(option);
            });
            if (current) select.value = current;
        });
    }

    function bindNominationForm() {
        const form = document.getElementById('nominationForm');
        if (!form) return;
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!apiState.token) {
                setMessage('nominationMessage', 'Please create an account or sign in first.', 'error');
                showSection('account');
                return;
            }
            setMessage('nominationMessage', 'Saving nomination...', 'info');
            try {
                const payload = await apiRequest('/nominations', {
                    method: 'POST',
                    body: JSON.stringify(formPayload(form)),
                });
                form.reset();
                setMessage('nominationMessage', 'Nomination saved in the database. Reference #' + payload.data.id + '.', 'success');
            } catch (error) {
                setMessage('nominationMessage', formatError(error), 'error');
            }
        });
    }

    function bindVotingControls() {
        const categorySelect = document.getElementById('votingCategory');
        const refreshBtn = document.getElementById('refreshNomineesBtn');
        if (categorySelect) categorySelect.addEventListener('change', loadNominees);
        if (refreshBtn) refreshBtn.addEventListener('click', loadNominees);
    }

    async function loadNominees() {
        const grid = document.getElementById('nomineeGrid');
        const categorySelect = document.getElementById('votingCategory');
        if (!grid || !categorySelect) return;
        if (!apiState.token) {
            grid.innerHTML = '<div class="empty-state">Sign in to view nominees and vote.</div>';
            return;
        }
        if (!categorySelect.value) {
            grid.innerHTML = '<div class="empty-state">Choose a category to view published nominees.</div>';
            return;
        }
        grid.innerHTML = '<div class="empty-state">Loading nominees...</div>';
        try {
            const query = '?status=published&category_id=' + encodeURIComponent(categorySelect.value);
            const payload = await apiRequest('/nominees' + query, { method: 'GET' });
            renderNominees(payload.data || []);
        } catch (error) {
            grid.innerHTML = '<div class="empty-state">' + escapeHtml(formatError(error)) + '</div>';
        }
    }

    function renderNominees(nominees) {
        const grid = document.getElementById('nomineeGrid');
        if (!grid) return;
        if (!nominees.length) {
            grid.innerHTML = '<div class="empty-state">No published nominees yet for this category.</div>';
            return;
        }
        grid.innerHTML = nominees.map(function (nominee) {
            const image = nominee.profile_image || 'https://placehold.co/320x220/4A1628/F5A623?text=Nominee';
            return '<article class="nominee-card"><img src="' + escapeHtml(image) + '" alt="' + escapeHtml(nominee.full_name) + '"><div class="nominee-card-body"><h3>' + escapeHtml(nominee.full_name) + '</h3><p>' + escapeHtml(nominee.bio || 'No biography provided yet.') + '</p><div class="nominee-meta"><span>' + Number(nominee.vote_count || 0) + ' votes</span></div><button type="button" class="btn-submit vote-btn" data-nominee-id="' + nominee.id + '">Vote</button></div></article>';
        }).join('');
        grid.querySelectorAll('.vote-btn').forEach(function (button) {
            button.addEventListener('click', async function () {
                await submitVote(button.dataset.nomineeId);
            });
        });
    }

    async function submitVote(nomineeId) {
        setMessage('voteMessage', 'Saving vote...', 'info');
        try {
            const payload = await apiRequest('/votes', {
                method: 'POST',
                body: JSON.stringify({ nominee_id: nomineeId }),
            });
            setMessage('voteMessage', 'Vote saved successfully. Vote #' + payload.data.vote_id + '.', 'success');
            await loadNominees();
        } catch (error) {
            setMessage('voteMessage', formatError(error), 'error');
        }
    }

    function formatError(error) {
        if (error.payload && error.payload.errors) return Object.values(error.payload.errors).flat().join(' ');
        return error.message || 'Something went wrong.';
    }

    function escapeHtml(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    const boState = {
        nominations: [],
        nominees: [],
        categories: [],
        phases: [],
        roles: [],
        users: [],
    };

    function bindBackOfficeControls() {
        const refreshBtn = document.getElementById('backofficeRefreshBtn');
        if (refreshBtn) refreshBtn.addEventListener('click', loadBackOffice);

        document.querySelectorAll('.bo-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.bo-tab').forEach(function (t) { t.classList.remove('active'); });
                document.querySelectorAll('.bo-panel').forEach(function (p) { p.classList.remove('active'); });
                tab.classList.add('active');
                const panel = document.getElementById('bo-panel-' + tab.dataset.boTab);
                if (panel) panel.classList.add('active');
            });
        });

        const nominationStatus = document.getElementById('boNominationStatus');
        if (nominationStatus) nominationStatus.addEventListener('change', loadBackOfficeNominations);

        const nomineeStatus = document.getElementById('boNomineeStatus');
        if (nomineeStatus) nomineeStatus.addEventListener('change', loadBackOfficeNominees);

        const categoryForm = document.getElementById('boCategoryForm');
        if (categoryForm) {
            categoryForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                await saveBackOfficeCategory(categoryForm);
            });
        }

        const userForm = document.getElementById('boUserForm');
        if (userForm) {
            userForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                await createBackOfficeUser(userForm);
            });
        }
    }

    async function loadBackOffice() {
        if (!apiState.token) {
            setBackOfficeTable('boNominationsBody', 5, 'Sign in as a back-office user first.');
            showSection('account');
            return;
        }

        try {
            await Promise.all([
                loadAdminDashboard(),
                loadBackOfficeNominations(),
                loadBackOfficeNominees(),
                loadBackOfficeCategories(),
                loadBackOfficePhases(),
                loadBackOfficeRoles(),
                loadBackOfficeUsers(),
            ]);
        } catch (error) {
            setMessage('boCategoryMessage', formatError(error), 'error');
        }
    }

    async function loadAdminDashboard() {
        const grid = document.getElementById('adminMetricGrid');
        if (!grid) return;
        try {
            const payload = await apiRequest('/dashboard/admin', { method: 'GET' });
            const s = payload.data.summary;
            grid.innerHTML = [
                metricCard('Nominees', s.total_nominees),
                metricCard('Votes', s.total_votes),
                metricCard('Categories', s.total_categories),
                metricCard('Active Phase', s.active_phase || 'None'),
            ].join('');
        } catch (error) {
            grid.innerHTML = '<div class="empty-state">Back-office access requires a super admin account.</div>';
        }
    }

    function metricCard(label, value) {
        return '<div class="metric-card"><span>' + escapeHtml(label) + '</span><strong>' + escapeHtml(value) + '</strong></div>';
    }

    async function loadBackOfficeNominations() {
        const status = document.getElementById('boNominationStatus')?.value || '';
        const query = status ? '?evaluation_status=' + encodeURIComponent(status) : '';
        const payload = await apiRequest('/nominations' + query, { method: 'GET' });
        boState.nominations = payload.data || [];
        renderBackOfficeNominations();
    }

    function renderBackOfficeNominations() {
        const body = document.getElementById('boNominationsBody');
        if (!body) return;
        if (!boState.nominations.length) return setBackOfficeTable('boNominationsBody', 5, 'No nominations found.');
        body.innerHTML = boState.nominations.map(function (n) {
            return '<tr><td>' + escapeHtml(n.nominee?.full_name) + '</td><td>' + escapeHtml(n.category?.name) + '</td><td><span class="status-pill ' + escapeHtml(n.evaluation_status) + '">' + escapeHtml(n.evaluation_status) + '</span></td><td>' + escapeHtml(n.nomination_reason) + '</td><td class="bo-actions">' +
                actionButton('Approve', 'approve-nomination', n.id, n.evaluation_status !== 'pending') +
                actionButton('Reject', 'reject-nomination', n.id, n.evaluation_status !== 'pending') +
                '</td></tr>';
        }).join('');
        bindBackOfficeActionButtons(body);
    }

    async function loadBackOfficeNominees() {
        const status = document.getElementById('boNomineeStatus')?.value || '';
        const query = status ? '?status=' + encodeURIComponent(status) : '';
        const payload = await apiRequest('/nominees' + query, { method: 'GET' });
        boState.nominees = payload.data || [];
        renderBackOfficeNominees();
    }

    function renderBackOfficeNominees() {
        const body = document.getElementById('boNomineesBody');
        if (!body) return;
        if (!boState.nominees.length) return setBackOfficeTable('boNomineesBody', 5, 'No nominees found.');
        body.innerHTML = boState.nominees.map(function (n) {
            return '<tr><td>' + escapeHtml(n.full_name) + '</td><td>' + escapeHtml(n.category?.name) + '</td><td><span class="status-pill ' + escapeHtml(n.status) + '">' + escapeHtml(n.status) + '</span></td><td>' + Number(n.vote_count || 0) + '</td><td class="bo-actions">' +
                actionButton('Approve', 'approve-nominee', n.id, n.status === 'approved' || n.status === 'published') +
                actionButton('Publish', 'publish-nominee', n.id, n.status !== 'approved') +
                actionButton('Reject', 'reject-nominee', n.id, n.status === 'rejected') +
                '</td></tr>';
        }).join('');
        bindBackOfficeActionButtons(body);
    }

    async function loadBackOfficeCategories() {
        const payload = await apiRequest('/categories?per_page=100', { method: 'GET' });
        boState.categories = payload.data || [];
        renderBackOfficeCategories();
    }

    function renderBackOfficeCategories() {
        const body = document.getElementById('boCategoriesBody');
        if (!body) return;
        if (!boState.categories.length) return setBackOfficeTable('boCategoriesBody', 4, 'No categories found.');
        body.innerHTML = boState.categories.map(function (c) {
            return '<tr><td>' + escapeHtml(c.name) + '</td><td>' + Number(c.position || 0) + '</td><td>' + (c.is_active ? 'Yes' : 'No') + '</td><td class="bo-actions">' +
                actionButton('Edit', 'edit-category', c.id, false) +
                actionButton(c.is_active ? 'Disable' : 'Enable', 'toggle-category', c.id, false) +
                '</td></tr>';
        }).join('');
        bindBackOfficeActionButtons(body);
    }

    async function loadBackOfficePhases() {
        const payload = await apiRequest('/voting-phases', { method: 'GET' });
        boState.phases = payload.data || [];
        renderBackOfficePhases();
    }

    function renderBackOfficePhases() {
        const body = document.getElementById('boPhasesBody');
        if (!body) return;
        if (!boState.phases.length) return setBackOfficeTable('boPhasesBody', 5, 'No phases found.');
        body.innerHTML = boState.phases.map(function (p) {
            return '<tr><td>' + escapeHtml(p.name) + '</td><td>' + escapeHtml(p.phase_type) + '</td><td>' + escapeHtml(formatDate(p.start_date)) + '<br>' + escapeHtml(formatDate(p.end_date)) + '</td><td>' + (p.is_active ? 'Yes' : 'No') + '</td><td class="bo-actions">' +
                actionButton('Activate', 'activate-phase', p.id, p.is_active) +
                '</td></tr>';
        }).join('');
        bindBackOfficeActionButtons(body);
    }

    async function loadBackOfficeRoles() {
        const payload = await apiRequest('/roles', { method: 'GET' });
        boState.roles = payload.data || [];
        const select = document.getElementById('boUserRole');
        if (!select) return;
        select.innerHTML = '<option value="">Select role</option>' + boState.roles.map(function (r) {
            return '<option value="' + r.id + '">' + escapeHtml(r.name) + '</option>';
        }).join('');
    }

    async function loadBackOfficeUsers() {
        const payload = await apiRequest('/users', { method: 'GET' });
        boState.users = payload.data || [];
        renderBackOfficeUsers();
    }

    function renderBackOfficeUsers() {
        const body = document.getElementById('boUsersBody');
        if (!body) return;
        if (!boState.users.length) return setBackOfficeTable('boUsersBody', 5, 'No users found.');
        body.innerHTML = boState.users.map(function (u) {
            return '<tr><td>' + escapeHtml(u.name) + '</td><td>' + escapeHtml(u.email) + '</td><td>' + escapeHtml(u.role?.name) + '</td><td>' + (u.is_active ? 'Yes' : 'No') + '</td><td class="bo-actions">' +
                actionButton(u.is_active ? 'Deactivate' : 'Activate', 'toggle-user', u.id, false) +
                '</td></tr>';
        }).join('');
        bindBackOfficeActionButtons(body);
    }

    function actionButton(label, action, id, disabled) {
        return '<button type="button" class="bo-action" data-action="' + action + '" data-id="' + id + '"' + (disabled ? ' disabled' : '') + '>' + escapeHtml(label) + '</button>';
    }

    function bindBackOfficeActionButtons(scope) {
        scope.querySelectorAll('.bo-action').forEach(function (button) {
            button.addEventListener('click', async function () {
                await handleBackOfficeAction(button.dataset.action, button.dataset.id);
            });
        });
    }

    async function handleBackOfficeAction(action, id) {
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
            setMessage('boCategoryMessage', formatError(error), 'error');
        }
    }

    function fillCategoryForm(id) {
        const category = boState.categories.find(function (c) { return String(c.id) === String(id); });
        const form = document.getElementById('boCategoryForm');
        if (!category || !form) return;
        form.elements.id.value = category.id;
        form.elements.name.value = category.name || '';
        form.elements.position.value = category.position || 0;
        form.elements.max_nominees.value = category.max_nominees || 10;
        form.elements.icon.value = category.icon || '';
        form.elements.description.value = category.description || '';
        setMessage('boCategoryMessage', 'Editing ' + category.name + '.', 'info');
    }

    async function saveBackOfficeCategory(form) {
        const payload = formPayload(form);
        const id = payload.id;
        delete payload.id;
        payload.position = payload.position ? Number(payload.position) : 0;
        payload.max_nominees = payload.max_nominees ? Number(payload.max_nominees) : 10;

        try {
            if (id) {
                await apiRequest('/categories/' + id, { method: 'PUT', body: JSON.stringify(payload) });
                setMessage('boCategoryMessage', 'Category updated.', 'success');
            } else {
                await apiRequest('/categories', { method: 'POST', body: JSON.stringify(payload) });
                setMessage('boCategoryMessage', 'Category created.', 'success');
            }
            form.reset();
            await loadBackOfficeCategories();
            await loadCategories();
        } catch (error) {
            setMessage('boCategoryMessage', formatError(error), 'error');
        }
    }

    async function toggleCategory(id) {
        const category = boState.categories.find(function (c) { return String(c.id) === String(id); });
        if (!category) return;
        await apiRequest('/categories/' + id, { method: 'PUT', body: JSON.stringify({ is_active: !category.is_active }) });
    }

    async function createBackOfficeUser(form) {
        try {
            await apiRequest('/users', { method: 'POST', body: JSON.stringify(formPayload(form)) });
            form.reset();
            setMessage('boUserMessage', 'User created.', 'success');
            await loadBackOfficeUsers();
        } catch (error) {
            setMessage('boUserMessage', formatError(error), 'error');
        }
    }

    async function toggleUser(id) {
        const user = boState.users.find(function (u) { return String(u.id) === String(id); });
        if (!user) return;
        await apiRequest('/users/' + id, { method: 'PUT', body: JSON.stringify({ is_active: !user.is_active }) });
    }

    function setBackOfficeTable(id, colspan, message) {
        const body = document.getElementById(id);
        if (body) body.innerHTML = '<tr><td colspan="' + colspan + '">' + escapeHtml(message) + '</td></tr>';
    }

    function formatDate(value) {
        if (!value) return '';
        return String(value).replace('T', ' ').slice(0, 16);
    }

    /* ---- DOM refs (assigned after DOMContentLoaded) ---- */
    let heroSection, innerLayout, socialFloat;
    let navLinks, mainContent;
    let searchToggle, searchBar;
    let catTrack, catDots, catPrev, catNext;

    /* =========================================
       BOOT
       ========================================= */
    document.addEventListener('DOMContentLoaded', function () {
        heroSection  = document.getElementById('heroSection');
        innerLayout  = document.getElementById('innerLayout');
        socialFloat  = document.getElementById('socialFloat');
        mainContent  = document.getElementById('mainContent');
        navLinks     = document.querySelectorAll('.nav-link');
        searchToggle = document.getElementById('searchToggle');
        searchBar    = document.getElementById('searchBar');
        catTrack     = document.getElementById('catTrack');
        catDots      = document.getElementById('catDots');
        catPrev      = document.getElementById('catPrev');
        catNext      = document.getElementById('catNext');

        bindNav();
        bindHomeLink();
        bindSearch();
        bindHamburger();
        initCarousel();
        initModal();
        initBackendApp();

        /* Deep-link support: e.g. ?section=about */
        const params = new URLSearchParams(window.location.search);
        const deepSection = params.get('section');
        if (deepSection) {
            showSection(deepSection);
        }
    });

    /* =========================================
       SECTION SWITCHING
       ========================================= */
    window.showSection = function (sectionId) {
        /* Show inner layout, hide hero */
        heroSection.style.display  = 'none';
        innerLayout.style.display  = 'flex';
        socialFloat.style.display  = 'flex';

        /* Hide all section panels */
        document.querySelectorAll('.section-content').forEach(function (el) {
            el.style.display = 'none';
        });

        /* Show the requested panel */
        const target = document.getElementById('section-' + sectionId);
        if (target) {
            target.style.display = 'block';
            mainContent.scrollTop = 0;
        }

        /* Update active state in sidebar */
        navLinks.forEach(function (link) {
            link.classList.remove('active');
            if (link.dataset.section === sectionId) {
                link.classList.add('active');
            }
        });

        currentSection = sectionId;

        /* Re-init carousel when categories section is shown */
        if (sectionId === 'categories') {
            setTimeout(initCarousel, 50);
        }

        if (sectionId === 'nominations' || sectionId === 'voting') {
            loadCategories();
        }

        if (sectionId === 'voting') {
            loadNominees();
        }

        if (sectionId === 'backoffice') {
            loadBackOffice();
        }
    };

    /* =========================================
       HOME — return to landing hero
       ========================================= */
    window.showHome = function () {
        heroSection.style.display  = '';
        innerLayout.style.display  = 'none';
        socialFloat.style.display  = 'none';

        document.querySelectorAll('.section-content').forEach(function (el) {
            el.style.display = 'none';
        });

        navLinks.forEach(function (link) { link.classList.remove('active'); });

        currentSection = null;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    function bindHomeLink() {
        const homeLink = document.getElementById('homeLink');
        if (!homeLink) return;
        homeLink.addEventListener('click', function (e) {
            e.preventDefault();
            showHome();
            closeSidebar();
        });
    }

    /* =========================================
       NAV LINKS
       ========================================= */
    function bindNav() {
        document.querySelectorAll('.nav-link, [data-section]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                const sec = el.dataset.section;
                if (sec) showSection(sec);
                closeSidebar();
            });
        });
    }

    /* =========================================
       SEARCH TOGGLE
       ========================================= */
    function bindSearch() {
        if (!searchToggle || !searchBar) return;
        searchToggle.addEventListener('click', function () {
            searchBar.classList.toggle('active');
            if (searchBar.classList.contains('active')) {
                searchBar.querySelector('input').focus();
            }
        });

        document.addEventListener('click', function (e) {
            if (!searchBar.contains(e.target) && e.target !== searchToggle) {
                searchBar.classList.remove('active');
            }
        });
    }

    /* =========================================
       HAMBURGER (mobile sidebar toggle)
       ========================================= */
    function closeSidebar() {
        const sidebar  = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const btn      = document.getElementById('hamburgerBtn');
        if (sidebar)  sidebar.classList.remove('sidebar-open');
        if (backdrop) backdrop.classList.remove('active');
        if (btn)      btn.classList.remove('open');
    }

    function bindHamburger() {
        const btn      = document.getElementById('hamburgerBtn');
        const sidebar  = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (!btn || !sidebar) return;

        btn.addEventListener('click', function () {
            const isOpen = sidebar.classList.toggle('sidebar-open');
            btn.classList.toggle('open', isOpen);
            if (backdrop) backdrop.classList.toggle('active', isOpen);
        });

        if (backdrop) {
            backdrop.addEventListener('click', closeSidebar);
        }
    }

    /* =========================================
       CAROUSEL
       ========================================= */
    function initCarousel() {
        if (!catTrack) return;

        const slides = catTrack.querySelectorAll('.carousel-slide');
        totalSlides = slides.length;
        carouselIndex = 0;

        const visible = getSlidesVisible();

        /* Build dots */
        if (catDots) {
            catDots.innerHTML = '';
            const maxIndex = totalSlides - visible + 1;
            for (let i = 0; i < maxIndex; i++) {
                const dot = document.createElement('button');
                dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
                dot.textContent = i + 1;
                dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                (function (idx) {
                    dot.addEventListener('click', function () { goToSlide(idx); });
                }(i));
                catDots.appendChild(dot);
            }
        }

        if (catPrev) { catPrev.onclick = prevSlide; }
        if (catNext) { catNext.onclick = nextSlide; }

        updateCarousel();
    }

    function goToSlide(index) {
        const maxIndex = totalSlides - getSlidesVisible();
        carouselIndex = Math.max(0, Math.min(index, maxIndex));
        updateCarousel();
    }

    function prevSlide() { goToSlide(carouselIndex - 1); }
    function nextSlide() { goToSlide(carouselIndex + 1); }

    function updateCarousel() {
        if (!catTrack) return;

        const visible = getSlidesVisible();
        const slideWidthPct = 100 / visible;
        catTrack.style.transform = 'translateX(-' + (carouselIndex * slideWidthPct) + '%)';

        if (catDots) {
            catDots.querySelectorAll('.carousel-dot').forEach(function (dot, i) {
                dot.classList.toggle('active', i === carouselIndex);
            });
        }

        if (catPrev) catPrev.disabled = carouselIndex === 0;
        if (catNext) catNext.disabled = carouselIndex >= totalSlides - visible;
    }

    window.addEventListener('resize', function () {
        if (currentSection === 'categories') {
            initCarousel();
        }
    });

    /* =========================================
       SIDEBAR SCROLL BUTTONS
       ========================================= */
    document.addEventListener('DOMContentLoaded', function () {
        const scrollUp   = document.getElementById('scrollUp');
        const scrollDown = document.getElementById('scrollDown');
        const sidebarNav = document.querySelector('.sidebar-nav');

        if (scrollUp && sidebarNav) {
            scrollUp.addEventListener('click', function () {
                sidebarNav.scrollBy({ top: -80, behavior: 'smooth' });
            });
        }

        if (scrollDown && sidebarNav) {
            scrollDown.addEventListener('click', function () {
                sidebarNav.scrollBy({ top: 80, behavior: 'smooth' });
            });
        }
    });

    /* =========================================
       MODAL
       ========================================= */
    function initModal() {
        const overlay = document.getElementById('modalOverlay');
        if (!overlay) return;

        document.getElementById('modalClose').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });

        document.querySelectorAll('[data-modal]').forEach(function (card) {
            card.addEventListener('click', function (e) {
                /* Don't fire if user clicked a button/link inside the card */
                if (e.target.closest('button, a, input, select, textarea')) return;
                openModal(card);
            });
        });
    }

    function openModal(card) {
        var overlay  = document.getElementById('modalOverlay');
        var media    = document.getElementById('modalMedia');
        var tagEl    = document.getElementById('modalTag');
        var titleEl  = document.getElementById('modalTitle');
        var subEl    = document.getElementById('modalSubtitle');
        var textEl   = document.getElementById('modalText');
        var dateEl   = document.getElementById('modalDate');

        /* Reset media panel */
        media.innerHTML = '';
        media.className = 'modal-media';
        media.style.background = '';

        var imgSrc      = card.dataset.modalImage;
        var iconCls     = card.dataset.modalIcon;
        var number      = card.dataset.modalNumber;
        var colorClass  = card.dataset.modalColorClass;
        var bgColor     = card.dataset.modalBg || '#4A1628';

        if (imgSrc) {
            var img = document.createElement('img');
            img.src = imgSrc;
            img.alt = card.dataset.modalTitle || '';
            media.appendChild(img);
        } else if (colorClass) {
            media.classList.add(colorClass);
            var wrap = document.createElement('div');
            wrap.className = 'modal-icon-wrap';
            var lbl = document.createElement('span');
            lbl.className = 'modal-cat-label';
            lbl.textContent = card.dataset.modalTag || '';
            wrap.appendChild(lbl);
            media.appendChild(wrap);
        } else if (iconCls) {
            media.style.background = bgColor;
            var wrap = document.createElement('div');
            wrap.className = 'modal-icon-wrap';
            var icon = document.createElement('i');
            icon.className = iconCls;
            wrap.appendChild(icon);
            media.appendChild(wrap);
        } else if (number) {
            media.style.background = bgColor;
            var wrap = document.createElement('div');
            wrap.className = 'modal-icon-wrap';
            var num = document.createElement('div');
            num.className = 'modal-big-number';
            num.textContent = number;
            wrap.appendChild(num);
            media.appendChild(wrap);
        }

        var tagTxt = card.dataset.modalTag || '';
        tagEl.textContent   = tagTxt;
        tagEl.style.display = tagTxt ? '' : 'none';

        titleEl.textContent = card.dataset.modalTitle || '';

        var subTxt = card.dataset.modalSubtitle || '';
        subEl.textContent   = subTxt;
        subEl.style.display = subTxt ? '' : 'none';

        textEl.textContent = card.dataset.modalText || '';

        var dateTxt = card.dataset.modalDate || '';
        dateEl.textContent   = dateTxt;
        dateEl.style.display = dateTxt ? '' : 'none';

        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        var overlay = document.getElementById('modalOverlay');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    /* =========================================
       NOMINATION FORM — basic validation
       ========================================= */
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('.legacy-nomination-form');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const requiredFields = form.querySelectorAll('[placeholder]');
            let valid = true;

            requiredFields.forEach(function (field) {
                field.style.borderColor = '';
                if (field.hasAttribute('required') || field.closest('.form-group label') && field.closest('.form-group label').textContent.includes('*')) {
                    if (!field.value.trim()) {
                        field.style.borderColor = '#e05252';
                        valid = false;
                    }
                }
            });

            if (valid) {
                const btn = form.querySelector('.btn-submit');
                const orig = btn.textContent;
                btn.textContent = 'Submitted!';
                btn.style.background = '#4CAF50';
                btn.style.color = 'white';
                setTimeout(function () {
                    btn.textContent = orig;
                    btn.style.background = '';
                    btn.style.color = '';
                    form.reset();
                }, 2500);
            }
        });
    });

})();
