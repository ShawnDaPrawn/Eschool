/* ============================================================
   The panel's navigation drawer (resources/views/layouts/
   sidebar.blade.php, styles in public/assets/css/sidebar.css):
   - sections open and close as disclosures (aria-expanded and
     the panel's hidden attribute);
   - on wide screens the header's menu button shrinks the drawer
     to its icons (body.sidebar-icon-only); choosing a section
     there opens the drawer again;
   - on small screens the header's menu button opens the drawer
     over a dimmed backdrop; the close button, the backdrop and
     Escape close it.
   No dependencies. (The old menu filter was replaced by the
   header's global search in panel.js.)
   ============================================================ */
(function () {
    'use strict';

    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    var body = document.body;
    var minimize = document.querySelector('[data-panel-nav-minimize]');
    var opener = document.querySelector('[data-panel-nav-open]');

    /* ---- Sections ------------------------------------------- */
    function setSection(toggle, open) {
        var panel = document.getElementById(toggle.getAttribute('aria-controls'));
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (panel) panel.hidden = !open;
    }

    sidebar.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-panel-nav-section]');
        if (!toggle || !sidebar.contains(toggle)) return;
        if (body.classList.contains('sidebar-icon-only')) {
            // Icon-only drawer: bring the words back and show this section.
            setIconOnly(false);
            setSection(toggle, true);
            return;
        }
        setSection(toggle, toggle.getAttribute('aria-expanded') !== 'true');
    });

    /* ---- Icon-only drawer (wide screens) -------------------- */
    function setIconOnly(on) {
        body.classList.toggle('sidebar-icon-only', on);
        if (minimize) minimize.setAttribute('aria-expanded', on ? 'false' : 'true');
    }

    if (minimize) {
        minimize.addEventListener('click', function () {
            setIconOnly(!body.classList.contains('sidebar-icon-only'));
        });
    }

    /* ---- Drawer over the page (small screens) --------------- */
    var backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);

    function isOpen() {
        return sidebar.classList.contains('active');
    }

    function setOpen(open) {
        sidebar.classList.toggle('active', open);
        backdrop.classList.toggle('active', open);
        body.classList.toggle('sidebar-offcanvas-open', open);
        if (opener) opener.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            var first = sidebar.querySelector('select, button, a[href]');
            if (first) first.focus();
        } else if (opener && sidebar.contains(document.activeElement)) {
            opener.focus();
        }
    }

    if (opener) {
        opener.addEventListener('click', function () {
            setOpen(!isOpen());
        });
    }

    backdrop.addEventListener('click', function () {
        setOpen(false);
    });

    var closeBtn = document.getElementById('sidebar-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            setOpen(false);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isOpen()) setOpen(false);
    });

    // If the viewport grows past the small-screen breakpoint while open, reset.
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992 && isOpen()) setOpen(false);
    });

    // Bring the current page's link into view in a long menu.
    var current = sidebar.querySelector('[aria-current="page"]');
    var list = sidebar.querySelector('.panel-nav__list');
    if (current && list && list.scrollHeight > list.clientHeight) {
        list.scrollTop = current.offsetTop - list.clientHeight / 2 + current.offsetHeight / 2;
    }
})();
