# Shared school message clients

The existing family and teacher shells open `/communication/messages` behind
both the communication module and `communication_messages` flag. Account,
school and token changes invalidate in-flight work and private views. Notification
links accept one canonical message identifier; reviewer workbench links map to
the same guarded screen. Wrong or withdrawn readers clear the private body.

Use shared Material 3 screens in `eschool_academics`. Phones open a message on
its own page; laptop widths show inbox/drafts beside the reader/editor. Explicitly
opt into a wider AdaptiveScaffold content limit while retaining its normal default.
Both apps read authorized school messages; staff compose within fresh scopes and
can review another author's message only when the server allows it. Custom-list
and role-rule management remains in the native panel.

Draft commands carry revisions and UUIDs. An uncertain write freezes its original
payload for an exact retry. Conflicts retain local text beside current saved text;
editing invalidates preview and requires saving again. Send uses the saved preview
fingerprint. School-local scheduling, approval reasons and withdrawal are explicit.
Published text is immutable. Backgrounding hides private content; resume requests
fresh authorization and retains unsaved drafts for comparison. No persistent message
cache is created. Reader acknowledgement failures distinguish lost authority from a
retryable connection failure.

Verify repository identity checks, stale responses, uncertain retries, comparison,
role loss, dirty preview, current read rights and phone/two-pane layouts. Run both
complete app suites, shared academic tests, backend context contracts, and actual
Chrome login-to-message flows with synthetic data. Android/device/staging/provider
and human translation evidence remains separately recorded.
