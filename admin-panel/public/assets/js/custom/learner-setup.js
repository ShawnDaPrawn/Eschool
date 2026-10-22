// The printable learner sign-in sheet (resources/views/learner-setup/sheet.blade.php).
(function () {
    var button = document.getElementById('print-sheet');
    if (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    }
})();
