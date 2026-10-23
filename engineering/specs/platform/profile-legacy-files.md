# Private copies of legacy profile files

Extend the bounded legacy profile backfill with explicit `--files`. Without it,
populated legacy file fields remain reported as pending. A dry run with the
option validates paths, bytes, extensions, MIME and the existing 5 MB limit,
without writing metadata, ciphertext, audits or values. Apply creates encrypted
private copies using the current profile-file schema and protected routes.
Existing encrypted values and deliberate clears still win. Imported definitions
remain hidden for school review.

Only local public-disk objects in the selected school's exact
`schools/{id}/{students|parents|teachers}/` directory may be copied. The folder
must match the selected profile. School 1 may also import pre-tenancy objects in
its unprefixed profile folder. Refuse foreign school prefixes, URLs, absolute
paths, traversal, empty path segments, control/encoded characters, symlinks,
missing files, unsupported disks, oversize/empty files and MIME/extension
mismatches. Read a bounded stream; never fetch a URL. Reports use fixed issue
codes and numeric IDs, never names, paths or file contents.

No source file is removed and v1 file paths are unchanged. Installed image
viewers do not send auth headers, so silently redirecting those paths to the
protected service would break them. Private-copy success is not proof that the
old public object has been retired. Reference inventory, verified compatible
clients, backed-up retirement and the general retention job remain separate.
On a failed record transaction, remove newly created ciphertext while preserving
source files and existing encrypted references.

Verify two schools, all three profile folders, pre-tenancy School 1, dry-run
immutability, traversal/URL/symlink/size/MIME refusals, ciphertext/name encryption,
idempotency/clears, transaction cleanup and fresh protected-read permissions.
No API, route, table, app or cloud-service changes are required.
