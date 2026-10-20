/* ============================================================
   Sidebar nav search — live-filters the admin sidebar menu.
   Markup lives in resources/views/layouts/sidebar.blade.php,
   styles in public/assets/css/sidebar.css.
   ============================================================ */
(function () {
    'use strict';

    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    var input = document.getElementById('sidebar-search-input');
    if (!input) return;
    var clearBtn = document.getElementById('sidebar-search-clear');
    var noResults = document.getElementById('sidebar-search-no-results');
    var navRoot = sidebar.querySelector('ul.nav');
    if (!navRoot) return;

    // Top-level items, excluding the mobile header and the search bar itself.
    var topItems = [];
    Array.prototype.forEach.call(navRoot.children, function (li) {
        if (li.tagName === 'LI' && li.classList.contains('nav-item') &&
            !li.classList.contains('sidebar-mobile-header') &&
            !li.classList.contains('sidebar-search-item')) {
            topItems.push(li);
        }
    });

    // Remember which collapsible menus were open on load (server-rendered active state).
    var originalOpen = topItems.map(function (li) {
        var collapse = li.querySelector(':scope > .collapse');
        return collapse ? collapse.classList.contains('show') : false;
    });

    function norm(s) {
        return (s || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function ownTitle(li) {
        var link = li.querySelector(':scope > a.nav-link');
        if (!link) return '';
        var title = link.querySelector('.menu-title');
        return norm(title ? title.textContent : link.textContent);
    }

    function setOpen(link, collapse, open) {
        if (!collapse) return;
        collapse.classList.toggle('show', open);
        if (link) link.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function reset() {
        topItems.forEach(function (li, i) {
            li.classList.remove('sidebar-search-hidden');
            Array.prototype.forEach.call(li.querySelectorAll('.sub-menu > li.nav-item'), function (sub) {
                sub.classList.remove('sidebar-search-hidden');
            });
            setOpen(li.querySelector(':scope > a.nav-link'), li.querySelector(':scope > .collapse'),
                originalOpen[i]);
        });
        if (noResults) noResults.style.display = 'none';
    }

    function filter(rawQuery) {
        var q = norm(rawQuery);
        if (!q) {
            reset();
            return;
        }
        var anyVisible = false;
        topItems.forEach(function (li) {
            var link = li.querySelector(':scope > a.nav-link');
            var collapse = li.querySelector(':scope > .collapse');
            var parentMatch = ownTitle(li).indexOf(q) !== -1;

            if (collapse) {
                var childMatch = false;
                Array.prototype.forEach.call(li.querySelectorAll('.sub-menu > li.nav-item'), function (sub) {
                    var subLink = sub.querySelector('a.nav-link');
                    var subText = norm(subLink ? subLink.textContent : sub.textContent);
                    // A matching parent reveals all of its children.
                    var match = parentMatch || subText.indexOf(q) !== -1;
                    sub.classList.toggle('sidebar-search-hidden', !match);
                    if (match) childMatch = true;
                });
                var show = parentMatch || childMatch;
                li.classList.toggle('sidebar-search-hidden', !show);
                setOpen(link, collapse, show);
                if (show) anyVisible = true;
            } else {
                li.classList.toggle('sidebar-search-hidden', !parentMatch);
                if (parentMatch) anyVisible = true;
            }
        });
        if (noResults) noResults.style.display = anyVisible ? 'none' : 'block';
    }

    input.addEventListener('input', function () {
        if (clearBtn) clearBtn.style.display = this.value ? 'flex' : 'none';
        filter(this.value);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            this.value = '';
            if (clearBtn) clearBtn.style.display = 'none';
            filter('');
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            input.value = '';
            this.style.display = 'none';
            filter('');
            input.focus();
        });
    }
})();

/* ============================================================
   Mobile off-canvas drawer — backdrop + close interactions.
   off-canvas.js toggles `.active` on #sidebar; we observe that
   and mirror it onto a dimmed backdrop, and add close-on-backdrop,
   close button, and Escape-to-close.
   ============================================================ */
(function () {
    'use strict';

    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    var backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);

    function isOpen() {
        return sidebar.classList.contains('active');
    }

    function close() {
        sidebar.classList.remove('active');
        // syncBackdrop runs via the observer below.
    }

    function syncBackdrop() {
        var open = isOpen();
        backdrop.classList.toggle('active', open);
        document.body.classList.toggle('sidebar-offcanvas-open', open);
    }

    // off-canvas.js mutates #sidebar's class list — keep the backdrop in sync.
    var observer = new MutationObserver(syncBackdrop);
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });

    backdrop.addEventListener('click', close);

    var closeBtn = document.getElementById('sidebar-close');
    if (closeBtn) closeBtn.addEventListener('click', close);

    document.addEventListener('keydown', function (e) {
        if ((e.key === 'Escape' || e.keyCode === 27) && isOpen()) close();
    });

    // If the viewport grows past the mobile breakpoint while open, reset state.
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992 && isOpen()) close();
    });
})();

/* ============================================================
   Sticky-search fade — reveal the white gradient under the search
   bar only when the nav is actually scrolled, so menu items vanish
   smoothly behind it (and the first item isn't washed out at rest).
   ============================================================ */
(function () {
    'use strict';

    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    var nav = sidebar.querySelector('.nav');
    var searchItem = sidebar.querySelector('.sidebar-search-item');
    if (!nav || !searchItem) return;

    function update() {
        searchItem.classList.toggle('is-stuck', nav.scrollTop > 2);
    }

    nav.addEventListener('scroll', update, { passive: true });
    update();
})();
