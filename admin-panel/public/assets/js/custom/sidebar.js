/* ============================================================
   Sidebar drawer: the dimmed backdrop and close interactions of
   the off-canvas navigation on small screens. off-canvas.js
   toggles `.active` on #sidebar; this mirrors it onto a backdrop
   and adds close-on-backdrop, the close button and Escape.
   Markup: resources/views/layouts/sidebar.blade.php; styles:
   public/assets/css/sidebar.css. (The old menu filter was
   replaced by the header's global search in panel.js.)
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
