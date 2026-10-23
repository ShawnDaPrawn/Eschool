(function () {
    "use strict";
    var form = document.getElementById("person-profile");
    if (!form) return;
    var dirty = new Set();
    var required = new WeakSet();
    Array.from(form.elements).forEach(function (control) {
        if (control.name && control.name.indexOf("values[") === 0 && control.required) {
            required.add(control);
            control.required = false;
        }
    });
    function changed(event) {
        var control = event.target;
        var match = /^values\[([a-z][a-z0-9_]*)\]/.exec(control.name || "");
        if (match) {
            dirty.add(match[1]);
            if (required.has(control)) control.required = true;
        }
    }
    form.addEventListener("input", changed);
    form.addEventListener("change", changed);
    form.addEventListener("formdata", function (event) {
        Array.from(event.formData.keys()).forEach(function (name) {
            var match = /^values\[([a-z][a-z0-9_]*)\]/.exec(name);
            if (match && !dirty.has(match[1])) event.formData.delete(name);
        });
    });
    form.addEventListener("submit", function (event) {
        if (dirty.size === 0) event.preventDefault();
    });
    form.addEventListener("page-form:saved", function () { dirty.clear(); });
})();
