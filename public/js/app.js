/* =============================================
   EXTRAORDINARY AFRICAN — MAIN JAVASCRIPT
   ============================================= */

(function () {
    'use strict';

    /* ---- State ---- */
    let currentSection = null;
    let carouselIndex = 0;
    let totalSlides = 0;
    const heroLanguageStorageKey = 'ea_hero_language';
    const languageCodeByKey = {
        english: 'en',
        french: 'fr',
        arabic: 'ar',
    };
    const translateReloadGuardKey = 'ea_translate_reload_guard';
    const maxBrowserUploadBytes = 1900 * 1024;
    const maxProfileImageDimension = 1800;

    window.googleTranslateElementInit = function () {
        if (!window.google?.translate?.TranslateElement) return;

        new window.google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,fr,ar',
            autoDisplay: false,
            layout: window.google.translate.TranslateElement.InlineLayout.SIMPLE,
        }, 'google_translate_element');

        applyPlatformLanguage(localStorage.getItem(heroLanguageStorageKey) || 'english');
    };

    function getSlidesVisible() {
        if (window.innerWidth <= 768) return 1;
        if (window.innerWidth <= 1024) return 2;
        return 3;
    }

    const apiState = {
        token: sessionStorage.getItem('ea_token') || localStorage.getItem('ea_token') || '',
        user: JSON.parse(localStorage.getItem('ea_user') || 'null'),
        categories: [],
    };
    let publicNominees = [];
    let activeVoteNominee = null;

    function initBackendApp() {
        bindAuthForms();
        bindNominationForm();
        bindVotingControls();
        bindPublicNomineeModal();
        bindBackOfficeControls();
        updateAuthUi();
        loadCategories();
        loadApprovedNominees();

        if (apiState.token) {
            loadCurrentUser();
        }
    }

    function apiHeaders(body) {
        const headers = { 'Accept': 'application/json' };
        if (!(body instanceof FormData)) headers['Content-Type'] = 'application/json';
        if (apiState.token) headers.Authorization = 'Bearer ' + apiState.token;
        return headers;
    }

    async function apiRequest(path, options) {
        const requestOptions = options || {};
        const response = await fetch('/api/v1' + path, {
            ...requestOptions,
            headers: {
                ...apiHeaders(requestOptions.body),
                ...(requestOptions.headers || {}),
            },
        });
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

    function getDeviceFingerprint() {
        const storageKey = 'ea_device_id';
        let deviceId = '';

        try {
            deviceId = localStorage.getItem(storageKey) || '';
            if (!deviceId) {
                deviceId = makeDeviceId();
                localStorage.setItem(storageKey, deviceId);
            }
        } catch (error) {}

        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
        const display = window.screen
            ? [screen.width, screen.height, screen.colorDepth].join('x')
            : '';

        return [
            deviceId,
            navigator.userAgent || '',
            navigator.language || '',
            navigator.platform || '',
            timezone,
            display,
            navigator.hardwareConcurrency || '',
            navigator.deviceMemory || '',
        ].join('|');
    }

    function makeDeviceId() {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID();

        if (window.crypto?.getRandomValues) {
            const values = new Uint32Array(4);
            window.crypto.getRandomValues(values);
            return Array.from(values).map(function (value) {
                return value.toString(16).padStart(8, '0');
            }).join('');
        }

        return String(Date.now()) + '-' + String(Math.random()).slice(2);
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
        sessionStorage.setItem('ea_token', apiState.token);
        localStorage.removeItem('ea_token');
        localStorage.setItem('ea_user', JSON.stringify(apiState.user));
        updateAuthUi();
    }

    function clearSession() {
        apiState.token = '';
        apiState.user = null;
        sessionStorage.removeItem('ea_token');
        localStorage.removeItem('ea_token');
        localStorage.removeItem('ea_user');
        updateAuthUi();
    }

    function isSuperAdmin() {
        return apiState.user?.role === 'Super Admin' || apiState.user?.role === 'super_admin';
    }

    function updateAuthUi() {
        const signedIn = Boolean(apiState.token && apiState.user);
        const label = signedIn
            ? 'Signed in as ' + apiState.user.name + ' (' + apiState.user.email + ')'
            : 'You are not signed in. Create an account before nominating.';
        const votingLabel = signedIn
            ? label
            : 'Voting is open without an account. One vote per category is allowed per device and network.';

        ['authStatus', 'backofficeAuthStatus'].forEach(function (id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = label;
            el.classList.toggle('is-ok', signedIn);
        });

        const votingStatus = document.getElementById('votingAuthStatus');
        if (votingStatus) {
            votingStatus.textContent = votingLabel;
            votingStatus.classList.toggle('is-ok', true);
        }

        const nominationStatus = document.getElementById('nominationAuthStatus');
        if (nominationStatus) {
            nominationStatus.classList.toggle('is-ok', signedIn);
            nominationStatus.classList.toggle('with-action', !signedIn);
            if (signedIn) {
                nominationStatus.textContent = label;
            } else {
                nominationStatus.innerHTML = '<span>You are not signed in. Create an account before nominating.</span><button type="button" class="auth-status-action" id="nominationCreateAccountBtn"><i class="fa fa-user-plus" aria-hidden="true"></i><span>Create Account</span></button>';
                document.getElementById('nominationCreateAccountBtn')?.addEventListener('click', function () {
                    showSection('account');
                });
            }
        }

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.style.display = signedIn ? '' : 'none';

        const nominationForm = document.getElementById('nominationForm');
        if (nominationForm) nominationForm.classList.toggle('is-disabled', !signedIn);

        document.querySelectorAll('.admin-only').forEach(function (el) {
            el.style.display = isSuperAdmin() ? '' : 'none';
        });
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
                    if (typeof window.showSection === 'function' && document.getElementById('nominationForm')) {
                        window.showSection('nominations');
                        setMessage('nominationMessage', 'Account created. You can submit your nomination now.', 'success');
                    }
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
                if (currentSection === 'backoffice' && typeof window.showSection === 'function') {
                    window.showSection('account');
                }
            });
        }
    }

    async function loadCategories() {
        try {
            const payload = await apiRequest('/public/categories?per_page=100', { method: 'GET' });
            apiState.categories = payload.data || [];
            populateCategorySelects(apiState.categories);
        } catch (error) {
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
            placeholder.textContent = categories.length ? 'Select a category' : 'No categories available';
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
                setMessage('nominationMessage', 'Please create an account first.', 'error');
                showSection('account');
                return;
            }
            setMessage('nominationMessage', 'Saving nomination...', 'info');
            try {
                const payload = await nominationFormData(form);
                const response = await apiRequest('/nominations', {
                    method: 'POST',
                    body: payload,
                });
                form.reset();
                setMessage('nominationMessage', 'Nomination saved successfully.', 'success');
                showNominationSuccessPopup(response.data.reference_code || response.data.id);
            } catch (error) {
                setMessage('nominationMessage', formatError(error), 'error');
            }
        });
    }

    async function nominationFormData(form) {
        const data = new FormData(form);
        const links = extractAchievementLinks(String(data.get('achievement_links') || ''));

        data.delete('achievement_links');
        links.forEach(function (link) {
            data.append('achievement_links[]', link);
        });

        const profileImage = data.get('profile_image_file');
        if (profileImage instanceof File && profileImage.size) {
            const preparedImage = await prepareProfileImage(profileImage);
            data.set('profile_image_file', preparedImage, preparedImage.name);
        }

        const largeEvidence = data.getAll('achievement_documents[]').find(function (file) {
            return file instanceof File && file.size > maxBrowserUploadBytes;
        });

        if (largeEvidence) {
            throw new Error('The achievement document "' + largeEvidence.name + '" is too large. Upload files under 2 MB or paste the evidence as a link.');
        }

        data.append('device_fingerprint', getDeviceFingerprint());

        return data;
    }

    function normalizeExternalUrl(value) {
        const text = String(value || '').trim();
        if (!text) return '';
        if (/^[a-z][a-z0-9+.-]*:\/\//i.test(text)) return text;
        if (/^(www\.|[^\s@]+\.[^\s@]+)/i.test(text)) return 'https://' + text;
        return text;
    }

    function extractAchievementLinks(value) {
        const seen = new Set();
        const links = [];
        const urlPattern = /(?:https?:\/\/|www\.)[^\s,;<>]+|(?<!@)\b[a-z0-9][a-z0-9.-]+\.[a-z]{2,}(?:\/[^\s,;<>]*)?/gi;

        String(value || '').split(/\r\n|\r|\n/).forEach(function (line) {
            const matches = line.match(urlPattern) || [];
            matches.forEach(function (match) {
                const normalized = normalizeExternalUrl(match.replace(/[.,;:!?)"\]}]+$/g, ''));
                if (!normalized || seen.has(normalized)) return;
                seen.add(normalized);
                links.push(normalized);
            });
        });

        return links.slice(0, 5);
    }

    async function prepareProfileImage(file) {
        if (file.size <= maxBrowserUploadBytes) return file;

        if (!/^image\/(jpeg|jpg|png|webp)$/i.test(file.type)) {
            throw new Error('The profile image is too large. Use a JPG, PNG, or WEBP image under 2 MB.');
        }

        try {
            const image = await loadImageForCompression(file);
            const largestSide = Math.max(image.width, image.height);
            const scale = Math.min(1, maxProfileImageDimension / largestSide);
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(image.width * scale));
            canvas.height = Math.max(1, Math.round(image.height * scale));

            const context = canvas.getContext('2d');
            context.drawImage(image, 0, 0, canvas.width, canvas.height);

            let blob = null;
            let quality = 0.84;
            do {
                blob = await canvasToBlob(canvas, 'image/jpeg', quality);
                quality -= 0.08;
            } while (blob && blob.size > maxBrowserUploadBytes && quality >= 0.58);

            if (!blob || blob.size > maxBrowserUploadBytes) {
                throw new Error('The profile image is too large. Please choose a smaller JPG, PNG, or WEBP file.');
            }

            const safeName = file.name.replace(/\.[^.]+$/, '') || 'profile-image';
            return new File([blob], safeName + '.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });
        } catch (error) {
            if (error?.message) throw error;
            throw new Error('The profile image could not be prepared. Please choose a JPG, PNG, or WEBP image under 2 MB.');
        }
    }

    function loadImageForCompression(file) {
        return new Promise(function (resolve, reject) {
            const image = new Image();
            const url = URL.createObjectURL(file);

            image.onload = function () {
                URL.revokeObjectURL(url);
                resolve(image);
            };
            image.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('The profile image could not be read. Please choose a JPG, PNG, or WEBP image.'));
            };
            image.src = url;
        });
    }

    function canvasToBlob(canvas, type, quality) {
        return new Promise(function (resolve) {
            canvas.toBlob(resolve, type, quality);
        });
    }

    function showNominationSuccessPopup(referenceCode) {
        const existing = document.getElementById('nominationSuccessOverlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.className = 'nomination-success-overlay';
        overlay.id = 'nominationSuccessOverlay';
        overlay.innerHTML = '<div class="nomination-success-popup" role="dialog" aria-modal="true" aria-labelledby="nominationSuccessTitle">' +
            '<button type="button" class="nomination-success-close" aria-label="Close">&times;</button>' +
            '<div class="nomination-success-icon"><i class="fa fa-check" aria-hidden="true"></i></div>' +
            '<h3 id="nominationSuccessTitle">Nomination Submitted</h3>' +
            '<p>The nomination was saved successfully.</p>' +
            '<div class="nomination-reference"><span>Reference Code</span><strong>' + escapeHtml(referenceCode) + '</strong></div>' +
            '<button type="button" class="btn-submit nomination-success-done">Done</button>' +
            '</div>';

        document.body.appendChild(overlay);

        function closePopup() {
            overlay.remove();
            document.removeEventListener('keydown', handleKeydown);
        }

        function handleKeydown(event) {
            if (event.key === 'Escape') closePopup();
        }

        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) closePopup();
        });
        overlay.querySelector('.nomination-success-close').addEventListener('click', closePopup);
        overlay.querySelector('.nomination-success-done').addEventListener('click', closePopup);
        document.addEventListener('keydown', handleKeydown);
    }

    function bindVotingControls() {
        const categorySelect = document.getElementById('votingCategory');
        const refreshBtn = document.getElementById('refreshNomineesBtn');
        if (categorySelect) categorySelect.addEventListener('change', loadNominees);
        if (refreshBtn) refreshBtn.addEventListener('click', loadNominees);
    }

    function bindPublicNomineeModal() {
        const modal = document.getElementById('nomineeVoteModal');
        if (!modal) return;

        document.getElementById('nomineeVoteClose')?.addEventListener('click', closePublicNomineeModal);
        document.getElementById('nomineeVoteSubmit')?.addEventListener('click', async function () {
            if (!activeVoteNominee) return;
            await submitVote(activeVoteNominee.id, 'nomineeVoteMessage');
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) closePublicNomineeModal();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('active')) {
                closePublicNomineeModal();
            }
        });
    }

    async function loadApprovedNominees() {
        const strip = document.getElementById('approvedNominationStrip');
        if (!strip) return;

        strip.innerHTML = '<div class="empty-state">Loading approved nominations...</div>';

        try {
            const payload = await apiRequest('/public/nominees?per_page=60', { method: 'GET' });
            publicNominees = payload.data || [];
            renderApprovedNominationStrip(publicNominees);
        } catch (error) {
            strip.innerHTML = '<div class="empty-state">' + escapeHtml(formatError(error)) + '</div>';
        }
    }

    function renderApprovedNominationStrip(nominees) {
        const strip = document.getElementById('approvedNominationStrip');
        if (!strip) return;

        if (!nominees.length) {
            strip.innerHTML = '<div class="empty-state">No approved nominations yet.</div>';
            return;
        }

        const baseItems = nominees.slice();
        while (baseItems.length < 4) {
            baseItems.push(...nominees);
        }

        const loopItems = baseItems.concat(baseItems);
        const duration = Math.max(28, baseItems.length * 7);

        strip.innerHTML = '<div class="nomination-marquee-track" style="--marquee-duration:' + duration + 's;">' +
            loopItems.map(renderApprovedNomineeCard).join('') +
            '</div>';

        strip.querySelectorAll('[data-public-nominee-id]').forEach(function (card) {
            card.addEventListener('click', function () {
                const nominee = findPublicNominee(card.dataset.publicNomineeId);
                if (nominee) openPublicNomineeModal(nominee);
            });
        });
    }

    function renderApprovedNomineeCard(nominee) {
        const image = nomineeImage(nominee);
        const category = nomineeCategoryName(nominee);

        return '<button type="button" class="approved-nominee-card" data-public-nominee-id="' + nominee.id + '">' +
            '<span class="approved-nominee-image"><img src="' + escapeHtml(image) + '" alt="' + escapeHtml(nominee.full_name) + '"><span class="approved-nominee-hover">Vote Now</span></span>' +
            '<span class="approved-nominee-details"><strong>' + escapeHtml(nominee.full_name) + '</strong><span>' + escapeHtml(category) + '</span></span>' +
            '</button>';
    }

    async function loadNominees() {
        const grid = document.getElementById('nomineeGrid');
        const categorySelect = document.getElementById('votingCategory');
        if (!grid || !categorySelect) return;

        if (!categorySelect.value) {
            grid.innerHTML = '<div class="empty-state">Choose a category to view approved nominees.</div>';
            return;
        }

        grid.innerHTML = '<div class="empty-state">Loading nominees...</div>';

        try {
            const query = '?per_page=100&category_id=' + encodeURIComponent(categorySelect.value);
            const payload = await apiRequest('/public/nominees' + query, { method: 'GET' });
            renderNominees(payload.data || []);
        } catch (error) {
            grid.innerHTML = '<div class="empty-state">' + escapeHtml(formatError(error)) + '</div>';
        }
    }

    function renderNominees(nominees) {
        const grid = document.getElementById('nomineeGrid');
        if (!grid) return;
        if (!nominees.length) {
            grid.innerHTML = '<div class="empty-state">No approved nominees yet for this category.</div>';
            return;
        }
        rememberPublicNominees(nominees);
        grid.innerHTML = nominees.map(function (nominee) {
            const image = nomineeImage(nominee);
            const country = nominee.country ? escapeHtml(nominee.country) + ' - ' : '';
            return '<article class="nominee-card voting-nominee-card" data-public-nominee-id="' + nominee.id + '" tabindex="0">' +
                '<div class="nominee-card-image"><img src="' + escapeHtml(image) + '" alt="' + escapeHtml(nominee.full_name) + '"><span class="approved-nominee-hover">Vote Now</span></div>' +
                '<div class="nominee-card-body"><h3>' + escapeHtml(nominee.full_name) + '</h3><p>' + escapeHtml(truncateText(nominee.bio || 'No biography provided yet.', 150)) + '</p><div class="nominee-meta"><span>' + country + Number(nominee.vote_count || 0) + ' votes</span></div><button type="button" class="btn-submit vote-btn" data-nominee-id="' + nominee.id + '">View &amp; Vote</button></div>' +
                '</article>';
        }).join('');

        grid.querySelectorAll('[data-public-nominee-id]').forEach(function (card) {
            card.addEventListener('click', function (event) {
                if (event.target.closest('.vote-btn')) return;
                const nominee = findPublicNominee(card.dataset.publicNomineeId);
                if (nominee) openPublicNomineeModal(nominee);
            });
            card.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                const nominee = findPublicNominee(card.dataset.publicNomineeId);
                if (nominee) openPublicNomineeModal(nominee);
            });
        });

        grid.querySelectorAll('.vote-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const nominee = findPublicNominee(button.dataset.nomineeId);
                if (nominee) openPublicNomineeModal(nominee);
            });
        });
    }

    async function submitVote(nomineeId, messageId) {
        const targetMessageId = messageId || 'voteMessage';
        setMessage(targetMessageId, 'Saving vote...', 'info');
        try {
            const payload = await apiRequest('/public/votes', {
                method: 'POST',
                body: JSON.stringify({
                    nominee_id: nomineeId,
                    device_id: getDeviceFingerprint(),
                }),
            });
            setMessage(targetMessageId, 'Vote saved successfully. Vote #' + payload.data.vote_id + '.', 'success');
            setMessage('nominationVoteMessage', 'Vote saved successfully.', 'success');
            setMessage('voteMessage', 'Vote saved successfully.', 'success');
            await loadApprovedNominees();
            if (currentSection === 'voting') await loadNominees();
        } catch (error) {
            setMessage(targetMessageId, formatError(error), 'error');
        }
    }

    function openPublicNomineeModal(nominee) {
        const modal = document.getElementById('nomineeVoteModal');
        if (!modal) return;

        activeVoteNominee = nominee;

        const image = nomineeImage(nominee);
        const imageEl = document.getElementById('nomineeVoteImage');
        const categoryEl = document.getElementById('nomineeVoteCategory');
        const titleEl = document.getElementById('nomineeVoteTitle');
        const metaEl = document.getElementById('nomineeVoteMeta');
        const bioEl = document.getElementById('nomineeVoteBio');
        const impactWrap = document.getElementById('nomineeVoteImpactWrap');
        const impactEl = document.getElementById('nomineeVoteImpact');

        if (imageEl) {
            imageEl.src = image;
            imageEl.alt = nominee.full_name || 'Nominee';
        }
        if (categoryEl) categoryEl.textContent = nomineeCategoryName(nominee);
        if (titleEl) titleEl.textContent = nominee.full_name || 'Nominee';
        if (metaEl) {
            const meta = [
                nominee.country || '',
                Number(nominee.vote_count || 0) + ' votes',
            ].filter(Boolean);
            metaEl.innerHTML = meta.map(function (item) {
                return '<span>' + escapeHtml(item) + '</span>';
            }).join('');
        }
        if (bioEl) bioEl.textContent = nominee.bio || 'No biography provided yet.';

        if (impactWrap && impactEl) {
            const impact = nominee.nomination_reason || '';
            impactEl.textContent = impact;
            impactWrap.style.display = impact ? '' : 'none';
        }

        setMessage('nomineeVoteMessage', '', '');
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closePublicNomineeModal() {
        const modal = document.getElementById('nomineeVoteModal');
        if (!modal) return;

        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        activeVoteNominee = null;
        document.body.style.overflow = '';
    }

    function rememberPublicNominees(nominees) {
        const byId = new Map(publicNominees.map(function (nominee) {
            return [String(nominee.id), nominee];
        }));

        nominees.forEach(function (nominee) {
            byId.set(String(nominee.id), nominee);
        });

        publicNominees = Array.from(byId.values());
    }

    function findPublicNominee(id) {
        return publicNominees.find(function (nominee) {
            return String(nominee.id) === String(id);
        });
    }

    function nomineeImage(nominee) {
        return nominee?.profile_image || 'https://placehold.co/420x520/4A1628/F5A623?text=Nominee';
    }

    function nomineeCategoryName(nominee) {
        return nominee?.category?.name || 'Extraordinary Africans';
    }

    function truncateText(value, maxLength) {
        const text = String(value || '').trim();
        if (text.length <= maxLength) return text;
        return text.slice(0, maxLength - 3).trim() + '...';
    }

    function formatError(error) {
        if (error.payload && error.payload.errors) return Object.values(error.payload.errors).flat().join(' ');
        return error.message || 'Something went wrong.';
    }

    function escapeHtml(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    const boState = {
        dashboard: null,
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
        if (!isSuperAdmin()) {
            setMessage('loginMessage', 'Back Office access requires a Super Admin account.', 'error');
            if (typeof window.showSection === 'function') showSection('account');
            return;
        }

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
            boState.dashboard = payload.data;
            renderAdminDashboard();
        } catch (error) {
            grid.innerHTML = '<div class="empty-state">Back-office access requires a super admin account.</div>';
            ['boPhaseStatusList', 'boTopNomineesList', 'boCategoryVotesList'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.innerHTML = '';
            });
        }
    }

    function metricCard(label, value) {
        return '<div class="metric-card"><span>' + escapeHtml(label) + '</span><strong>' + escapeHtml(value) + '</strong></div>';
    }

    function renderAdminDashboard() {
        const dashboard = boState.dashboard;
        const grid = document.getElementById('adminMetricGrid');
        if (!dashboard || !grid) return;

        const s = dashboard.summary || {};
        grid.innerHTML = [
            metricCard('Nominees', s.total_nominees || 0),
            metricCard('Votes', s.total_votes || 0),
            metricCard('Categories', s.total_categories || 0),
            metricCard('Active Phase', s.active_phase || 'None'),
        ].join('');

        renderBackOfficeList(
            'boPhaseStatusList',
            dashboard.phase_status || [],
            function (phase) {
                return listItem(
                    phase.name,
                    formatDate(phase.start_date) + ' - ' + formatDate(phase.end_date),
                    phase.status
                );
            },
            'No phases configured.'
        );

        renderBackOfficeList(
            'boTopNomineesList',
            dashboard.top_nominees || [],
            function (nominee) {
                return listItem(
                    nominee.full_name,
                    Number(nominee.vote_count || 0) + ' votes',
                    'Ranked'
                );
            },
            'No nominees yet.'
        );

        renderBackOfficeList(
            'boCategoryVotesList',
            dashboard.votes_by_category || [],
            function (category) {
                return listItem(
                    category.category,
                    Number(category.vote_count || 0) + ' votes',
                    'Category'
                );
            },
            'No category votes yet.'
        );

        setCount('boPhaseCount', dashboard.phase_status?.length || 0);
        setCount('boTopNomineeCount', dashboard.top_nominees?.length || 0);
        setCount('boVoteCategoryCount', dashboard.votes_by_category?.length || 0);
    }

    function renderBackOfficeList(id, items, renderer, emptyMessage) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = items.length
            ? items.map(renderer).join('')
            : '<div class="empty-state">' + escapeHtml(emptyMessage) + '</div>';
    }

    function listItem(title, meta, tag) {
        return '<div class="bo-list-item"><div><strong>' + escapeHtml(title) + '</strong><span>' + escapeHtml(meta) + '</span></div><em>' + escapeHtml(tag) + '</em></div>';
    }

    function setCount(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = Number(value || 0);
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
        initHeroLanguageVideo();
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
        if (sectionId === 'backoffice') {
            window.location.href = '/back-office/login';
            return;
        }

        /* Show inner layout, hide hero */
        heroSection.style.display  = 'none';
        innerLayout.style.display  = 'flex';
        socialFloat.style.display  = 'flex';
        document.body.classList.add('is-inner-page');
        pauseHeroVideo();

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

        if (sectionId === 'nominations') {
            loadApprovedNominees();
        }

        if (sectionId === 'voting') {
            loadNominees();
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
        document.body.classList.remove('is-inner-page');
        resumeHeroVideo();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    /* =========================================
       HERO VIDEO LANGUAGE
       ========================================= */
    function initHeroLanguageVideo() {
        const video = document.getElementById('heroVideo');
        const source = document.getElementById('heroVideoSource');
        const buttons = Array.from(document.querySelectorAll('[data-hero-language]'));
        const savedLanguage = localStorage.getItem(heroLanguageStorageKey);
        const savedButton = buttons.find(function (button) {
            return button.dataset.heroLanguage === savedLanguage;
        }) || buttons[0];

        if (!video || !source || !buttons.length || !savedButton) return;

        setHeroLanguage(savedButton, { withSound: false, restart: false });

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                setHeroLanguage(button, { withSound: true, restart: true });
            });
        });

        const soundToggle = document.getElementById('heroSoundToggle');
        if (soundToggle) {
            soundToggle.addEventListener('click', function () {
                if (video.muted || video.volume === 0) {
                    enableHeroSound(video);
                } else {
                    video.muted = true;
                    updateHeroSoundToggle(video);
                }
            });

            video.addEventListener('volumechange', function () {
                updateHeroSoundToggle(video);
            });
            video.addEventListener('play', function () {
                updateHeroSoundToggle(video);
            });
            video.addEventListener('pause', function () {
                updateHeroSoundToggle(video);
            });
        }

        function setHeroLanguage(button, options) {
            const opts = options || {};
            const nextSrc = button.dataset.videoSrc || '';
            const label = button.dataset.videoLabel || button.textContent.trim();
            const hasNewSource = nextSrc && source.getAttribute('src') !== nextSrc;

            buttons.forEach(function (candidate) {
                const isActive = candidate === button;
                candidate.classList.toggle('is-active', isActive);
                candidate.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            const languageKey = button.dataset.heroLanguage || 'english';

            localStorage.setItem(heroLanguageStorageKey, languageKey);
            applyPlatformLanguage(languageKey, { userRequested: opts.restart });
            video.setAttribute('aria-label', 'Extraordinary Africans nomination video in ' + label);

            if (hasNewSource) {
                source.setAttribute('src', nextSrc);
                video.load();
            }

            if (opts.restart) {
                try {
                    video.currentTime = 0;
                } catch (error) {}
            }

            if (!isHeroVisible()) {
                video.pause();
                updateHeroSoundToggle(video);
            } else if (opts.withSound) {
                enableHeroSound(video);
            } else {
                ensureHeroVideoPlaying(video);
            }
        }
    }

    function applyPlatformLanguage(languageKey, options) {
        const opts = options || {};
        const code = languageCodeByKey[languageKey] || 'en';
        document.documentElement.setAttribute('lang', code);
        document.documentElement.setAttribute('dir', code === 'ar' ? 'rtl' : 'ltr');
        document.body?.classList.toggle('is-rtl-language', code === 'ar');

        if (code === 'en') {
            const wasTranslated = isTranslatedPage();
            sessionStorage.removeItem(translateReloadGuardKey);
            clearGoogleTranslateCookie();

            if (opts.userRequested && wasTranslated) {
                reloadWithCurrentSection();
            }

            return;
        }

        setGoogleTranslateCookie(code);
        selectGoogleTranslateLanguage(code, 0, Boolean(opts.userRequested));
    }

    function setGoogleTranslateCookie(code) {
        const value = '/en/' + code;
        const expires = 'expires=Fri, 31 Dec 9999 23:59:59 GMT';
        document.cookie = 'googtrans=' + value + ';' + expires + ';path=/';

        if (window.location.hostname.indexOf('.') > -1) {
            document.cookie = 'googtrans=' + value + ';' + expires + ';domain=.' + window.location.hostname + ';path=/';
        }
    }

    function clearGoogleTranslateCookie() {
        const expires = 'expires=Thu, 01 Jan 1970 00:00:00 GMT';
        document.cookie = 'googtrans=;' + expires + ';path=/';
        document.cookie = 'googtrans=/en/en;' + expires + ';path=/';

        if (window.location.hostname.indexOf('.') > -1) {
            document.cookie = 'googtrans=;' + expires + ';domain=.' + window.location.hostname + ';path=/';
            document.cookie = 'googtrans=/en/en;' + expires + ';domain=.' + window.location.hostname + ';path=/';
        }
    }

    function isTranslatedPage() {
        return document.cookie.indexOf('googtrans=') !== -1 ||
            document.documentElement.className.indexOf('translated-') !== -1 ||
            Boolean(document.querySelector('html.translated-ltr, html.translated-rtl'));
    }

    function selectGoogleTranslateLanguage(code, attempt, reloadIfMissing) {
        const combo = document.querySelector('.goog-te-combo');
        const tries = attempt || 0;

        if (!combo) {
            if (tries < 24) {
                setTimeout(function () {
                    selectGoogleTranslateLanguage(code, tries + 1, reloadIfMissing);
                }, 250);
            } else if (reloadIfMissing) {
                reloadOnceForTranslation(code);
            }
            return;
        }

        sessionStorage.removeItem(translateReloadGuardKey);
        combo.value = Array.from(combo.options).some(function (option) {
            return option.value === code;
        }) ? code : '';

        combo.dispatchEvent(new Event('change'));
    }

    function reloadOnceForTranslation(code) {
        const guardValue = sessionStorage.getItem(translateReloadGuardKey);
        if (guardValue === code) return;

        sessionStorage.setItem(translateReloadGuardKey, code);
        reloadWithCurrentSection();
    }

    function reloadWithCurrentSection() {
        const url = new URL(window.location.href);
        const section = currentSection || url.searchParams.get('section') || '';

        if (section) {
            url.searchParams.set('section', section);
        } else {
            url.searchParams.delete('section');
        }

        window.location.replace(url.toString());
    }

    function enableHeroSound(video) {
        video.muted = false;
        video.volume = 1;

        const playAttempt = video.play();
        if (playAttempt && typeof playAttempt.catch === 'function') {
            playAttempt.catch(function () {
                video.muted = true;
                updateHeroSoundToggle(video);
            });
        }

        updateHeroSoundToggle(video);
    }

    function ensureHeroVideoPlaying(video) {
        const playAttempt = video.play();
        if (playAttempt && typeof playAttempt.catch === 'function') {
            playAttempt.catch(function () {});
        }

        updateHeroSoundToggle(video);
    }

    function pauseHeroVideo() {
        const video = document.getElementById('heroVideo');
        if (!video) return;
        video.pause();
        updateHeroSoundToggle(video);
    }

    function resumeHeroVideo() {
        const video = document.getElementById('heroVideo');
        if (!video) return;
        ensureHeroVideoPlaying(video);
    }

    function isHeroVisible() {
        return heroSection && heroSection.style.display !== 'none';
    }

    function updateHeroSoundToggle(video) {
        const soundToggle = document.getElementById('heroSoundToggle');
        if (!soundToggle || !video) return;

        const icon = soundToggle.querySelector('i');
        const soundOn = !video.muted && video.volume > 0 && !video.paused;
        const label = soundOn ? 'Mute video sound' : 'Play video sound';

        soundToggle.classList.toggle('is-on', soundOn);
        soundToggle.setAttribute('aria-label', label);
        soundToggle.setAttribute('title', label);

        if (icon) {
            icon.className = soundOn ? 'fa fa-volume-high' : 'fa fa-volume-xmark';
        }
    }

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
        const input = searchBar.querySelector('input');
        const submit = searchBar.querySelector('button');
        const resultsEl = document.getElementById('siteSearchResults');
        const searchIndex = buildSearchIndex();

        if (!input || !submit || !resultsEl) return;

        searchToggle.addEventListener('click', function () {
            const shouldOpen = !searchBar.classList.contains('active');
            searchBar.classList.add('active');
            input.focus();

            if (!shouldOpen && input.value.trim()) {
                performSearch(true);
            } else if (input.value.trim()) {
                renderSearchResults(searchIndex, input.value, resultsEl);
            }
        });

        submit.addEventListener('click', function () {
            performSearch(true);
        });

        input.addEventListener('input', function () {
            renderSearchResults(searchIndex, input.value, resultsEl);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                performSearch(true);
            }

            if (event.key === 'Escape') {
                closeSearch();
            }
        });

        document.addEventListener('click', function (e) {
            if (!searchBar.contains(e.target) && !resultsEl.contains(e.target) && !searchToggle.contains(e.target)) {
                closeSearch();
            }
        });

        function performSearch(openBestMatch) {
            const matches = renderSearchResults(searchIndex, input.value, resultsEl);

            if (openBestMatch && matches.length) {
                openSearchResult(matches[0]);
            }
        }

        function closeSearch() {
            searchBar.classList.remove('active');
            resultsEl.classList.remove('active');
            resultsEl.innerHTML = '';
            input.setAttribute('aria-expanded', 'false');
        }

        function openSearchResult(result) {
            closeSearch();
            input.value = '';

            if (result.sectionId === 'home') {
                showHome();
                return;
            }

            showSection(result.sectionId);
            highlightSearchTarget(result.sectionId);
        }
    }

    function buildSearchIndex() {
        const entries = [];
        const heroText = document.querySelector('.hero-text');

        if (heroText) {
            const text = normalizeDisplayText(heroText.textContent);
            entries.push({
                sectionId: 'home',
                title: 'Home',
                text: text,
                searchText: normalizeSearchText(text),
            });
        }

        document.querySelectorAll('.section-content').forEach(function (section) {
            const sectionId = String(section.id || '').replace(/^section-/, '');
            if (!sectionId) return;

            const title = section.querySelector('.section-title')?.textContent?.trim() || readableSectionName(sectionId);
            const modalText = Array.from(section.querySelectorAll('[data-modal-title], [data-modal-text], [data-modal-tag]'))
                .map(function (item) {
                    return [
                        item.dataset.modalTitle,
                        item.dataset.modalText,
                        item.dataset.modalTag,
                    ].filter(Boolean).join(' ');
                })
                .join(' ');

            const text = normalizeDisplayText([title, section.textContent, modalText].join(' '));

            entries.push({
                sectionId: sectionId,
                title: title,
                text: text,
                searchText: normalizeSearchText(text),
            });
        });

        return entries;
    }

    function renderSearchResults(searchIndex, query, resultsEl) {
        const input = document.getElementById('siteSearchInput');
        const matches = searchSite(searchIndex, query).slice(0, 7);
        const cleanQuery = query.trim();

        if (!cleanQuery) {
            resultsEl.classList.remove('active');
            resultsEl.innerHTML = '';
            if (input) input.setAttribute('aria-expanded', 'false');
            return [];
        }

        resultsEl.classList.add('active');
        if (input) input.setAttribute('aria-expanded', 'true');

        if (!matches.length) {
            resultsEl.innerHTML = '<div class="search-empty">No matching page content found.</div>';
            return [];
        }

        resultsEl.innerHTML = matches.map(function (match, index) {
            return '<button type="button" class="search-result" role="option" data-search-index="' + index + '">' +
                '<strong>' + escapeHtml(match.title) + '</strong>' +
                '<span>' + highlightQuery(makeSearchSnippet(match.text, cleanQuery), cleanQuery) + '</span>' +
                '</button>';
        }).join('');

        resultsEl.querySelectorAll('.search-result').forEach(function (button) {
            button.addEventListener('click', function () {
                const result = matches[Number(button.dataset.searchIndex)];
                if (!result) return;

                searchBar.classList.remove('active');
                resultsEl.classList.remove('active');
                resultsEl.innerHTML = '';
                if (input) {
                    input.value = '';
                    input.setAttribute('aria-expanded', 'false');
                }

                if (result.sectionId === 'home') {
                    showHome();
                } else {
                    showSection(result.sectionId);
                    highlightSearchTarget(result.sectionId);
                }
            });
        });

        return matches;
    }

    function searchSite(searchIndex, query) {
        const normalizedQuery = normalizeSearchText(query);
        const terms = normalizedQuery.split(' ').filter(Boolean);

        if (!terms.length) return [];

        return searchIndex
            .map(function (entry) {
                const title = normalizeSearchText(entry.title);
                let score = 0;

                terms.forEach(function (term) {
                    if (title.startsWith(term)) score += 16;
                    else if (title.includes(term)) score += 10;

                    if (entry.searchText.includes(term)) score += 3;
                });

                if (title === normalizedQuery) score += 25;
                if (entry.searchText.includes(normalizedQuery)) score += 8;

                return { ...entry, score: score };
            })
            .filter(function (entry) {
                return entry.score > 0;
            })
            .sort(function (a, b) {
                return b.score - a.score;
            });
    }

    function makeSearchSnippet(text, query) {
        const source = normalizeDisplayText(text);
        const searchableSource = normalizeSearchText(source);
        const term = normalizeSearchText(query).split(' ').find(Boolean) || '';
        const index = term ? searchableSource.indexOf(term) : -1;

        if (index === -1) return source.slice(0, 120) + (source.length > 120 ? '...' : '');

        const start = Math.max(0, index - 44);
        const end = Math.min(source.length, index + term.length + 86);
        return (start > 0 ? '...' : '') + source.slice(start, end) + (end < source.length ? '...' : '');
    }

    function highlightQuery(text, query) {
        const escaped = escapeHtml(text);
        const terms = normalizeSearchText(query)
            .split(' ')
            .filter(function (term) { return term.length > 1; })
            .map(escapeRegExp);

        if (!terms.length) return escaped;

        return escaped.replace(new RegExp('(' + terms.join('|') + ')', 'gi'), '<mark>$1</mark>');
    }

    function normalizeSearchText(value) {
        return normalizeDisplayText(value).toLowerCase();
    }

    function normalizeDisplayText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function readableSectionName(sectionId) {
        return sectionId.replace(/-/g, ' ').replace(/\b\w/g, function (letter) {
            return letter.toUpperCase();
        });
    }

    function escapeRegExp(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlightSearchTarget(sectionId) {
        const section = document.getElementById('section-' + sectionId);
        const title = section?.querySelector('.section-title');
        if (!title) return;

        title.classList.remove('search-hit-highlight');
        void title.offsetWidth;
        title.classList.add('search-hit-highlight');
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
