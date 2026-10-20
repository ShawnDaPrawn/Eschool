/**
 * Side-nav wizard navigation.
 * Scoped per `.wizard-container` so multiple wizards can coexist on one page.
 * Markup contract: see resources/views/components/wizard*.blade.php
 */
(function () {
    document.querySelectorAll('.wizard-container').forEach(function (wrapper) {
        var steps = wrapper.querySelectorAll('.wizard-step');
        var navItems = wrapper.querySelectorAll('.wizard-sidebar .step-item');
        var total = steps.length;

        function go(idx) {
            if (idx < 0 || idx >= total) return;
            steps.forEach(function (s, i) {
                s.classList.toggle('active', i === idx);
            });
            navItems.forEach(function (n, i) {
                n.classList.toggle('active', i === idx);
                n.classList.toggle('completed', i < idx);
            });
            var rect = wrapper.getBoundingClientRect();
            if (rect.top < 0) {
                window.scrollTo({
                    top: window.pageYOffset + rect.top - 80,
                    behavior: 'smooth'
                });
            }
        }

        navItems.forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                go(parseInt(this.getAttribute('data-step'), 10));
            });
        });

        wrapper.querySelectorAll('.wizard-next').forEach(function (btn) {
            btn.addEventListener('click', function () {
                go(parseInt(this.getAttribute('data-current'), 10) + 1);
            });
        });

        wrapper.querySelectorAll('.wizard-prev').forEach(function (btn) {
            btn.addEventListener('click', function () {
                go(parseInt(this.getAttribute('data-current'), 10) - 1);
            });
        });
    });
})();
