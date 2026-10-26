# Private activity material attachment API

Four additive operations list current attachments/capabilities, upload a scoped
scanned document, return private binary content and withdraw a file. Every route
uses the shared private response boundary before authentication, including POST,
HTML-Accept, validation, account mismatch and source refusals. Writes accept body
fields only and never flash drafts into the session. Binary responses use opaque
filenames, sandbox policy, no-store, no-referrer and nosniff.

The server returns separate can_upload/can_remove capabilities; these never grant
rights. Material ownership and the fresh group/module/year scope still govern
every operation and receipt. Read-only owners may remove an attachment while new
uploads remain unavailable. File IDs and URLs do not authorize a read.

Evidence:

- Typed HTTP and complete route-contract catalogue: 557 tests / 2,545 assertions
  pass; final file domain/API cases pass 18 / 150 including positive owner controls.
- Complete API role/tenant and private reachability cohorts pass 652 / 4,967 after
  consuming the coordinator's exact two gate-device route registrations.
- All 420 prior paths and 1,067 schemas are preserved; adds three paths, four
  operations and six schemas. Strict OpenAPI lint, whole-app PHPStan and Pint pass.
- Pinned 7.25.0 generation produces 2,212 Dart files. All 2,213 source/FILES hashes
  are identical across two generations. All 150 SDK tests pass, including binary
  transport, multipart identity/revisions, account headers and false capabilities.
  SDK analysis has zero errors/warnings (1,202 generated style information messages);
  the new protocol test analyzes cleanly.

Staff panel/native attachment controls and dedicated group chat remain follow-on
implementation. The domain's deployed scanner/private-disk and device evidence
remain unqualified; no production activation is claimed here.
