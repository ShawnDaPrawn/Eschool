(function () {
    "use strict";
    var form = document.getElementById("person-profile");
    if (!form) return;
    var dirty = new Set();
    var required = new WeakSet();
    var pendingUploads = 0;
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
            if (control.name.endsWith("[clear]") && control.type === "checkbox" && !control.checked) dirty.delete(match[1]);
            else dirty.add(match[1]);
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
        if (dirty.size === 0 || pendingUploads > 0) event.preventDefault();
    });
    form.addEventListener("page-form:saved", function () { dirty.clear(); });
    document.querySelectorAll("[data-profile-file-upload]").forEach(function (upload) {
        upload.addEventListener("submit", async function (event) {
            event.preventDefault();
            var button = upload.querySelector("button[type=submit]");
            if (button.disabled) return;
            var status = upload.querySelector("[data-file-status]");
            var key = upload.dataset.profileFileUpload;
            var current = document.querySelector('[data-profile-file="' + key + '"] [data-file-current]');
            var headers = {"Accept": "application/json", "X-CSRF-TOKEN": upload.querySelector("input[name=_token]").value, "X-School-Id": upload.dataset.school};
            button.disabled = true;
            pendingUploads++;
            status.textContent = "";
            var acknowledged = false;
            try {
                var result = await fetch(upload.action, {method: "POST", credentials: "same-origin", headers: headers, body: new FormData(upload)});
                var body = await result.json();
                if (!result.ok || body.error) {
                    status.textContent = body.message || upload.dataset.failed;
                    return;
                }
                acknowledged = true;
                current.replaceChildren();
                dirty.delete(key);
                var clear = document.querySelector('[data-profile-file="' + key + '"] input[type=checkbox]');
                if (clear) clear.checked = false;
                upload.reset();
                var schema = await fetch(upload.dataset.schema + '?profile_type=' + encodeURIComponent(upload.querySelector('[name=profile_type]').value), {credentials: "same-origin", headers: headers, cache: "no-store"});
                if (!schema.ok) throw new Error("Profile refresh failed");
                var response = await schema.json();
                var fields = response.data.categories.flatMap(function (category) { return category.fields; });
                var field = fields.find(function (field) { return field.key === key; });
                var file = field && field.value && field.value.file;
                if (!file || !/^[0-9a-f-]{36}\.(pdf|png|jpg)$/.test(file.id)) throw new Error("Profile refresh failed");
                var link = document.createElement("a");
                link.href = upload.dataset.assetPrefix + file.id;
                link.target = "_blank";
                link.rel = "noopener";
                link.textContent = file.name;
                current.append(link);
                status.textContent = upload.dataset.saved;
            } catch (_) {
                status.textContent = acknowledged ? upload.dataset.refresh : upload.dataset.failed;
            } finally {
                button.disabled = false;
                pendingUploads--;
            }
        });
    });
})();
