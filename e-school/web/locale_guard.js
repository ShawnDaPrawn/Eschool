// Flutter reads the browser's languages when it starts and stops with
// "Incorrect locale information provided" (a blank page) when one of them is
// not a valid language tag, as in a browser started from a system without a
// language ("en-US@posix"). Keep the valid tags, and English when none is
// left. Loaded before flutter_bootstrap.js; the same file is in both apps.
(function () {
  try {
    var tags = Array.prototype.slice.call(navigator.languages || [navigator.language]);
    var valid = tags.filter(function (tag) {
      try {
        new Intl.Locale(tag);
        return true;
      } catch (e) {
        return false;
      }
    });
    if (valid.length > 0 && valid.length === tags.length) return;
    if (valid.length === 0) valid = ['en'];
    Object.defineProperty(Navigator.prototype, 'languages', {
      configurable: true,
      get: function () { return valid.slice(); },
    });
    Object.defineProperty(Navigator.prototype, 'language', {
      configurable: true,
      get: function () { return valid[0]; },
    });
  } catch (e) {
    // Leave the browser's own values.
  }
})();
