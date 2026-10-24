# 0006: Replace semesters with school terms

Status: accepted by the product-owner instruction of 23 October 2026.

The owner confirmed that no installed clients use semesters and authorized
retiring that implementation instead of spending effort on a compatibility
bridge. This exception supersedes the earlier semester-specific requirement;
it does not waive compatibility for other API areas.

Rename the existing calendar table, model, academic foreign-key columns, routes,
permissions and bundled app consumers together. Preserve calendar IDs, stored
dates, year/school ownership and every academic link. Permission IDs and role /
direct grants survive the rename. Restrictive foreign keys replace delete
cascades. Retain historical migration filenames/schema for upgrades; isolate the
one historical data migration from current runtime models.

One canonical calendar supports grade bands, optional lock/report metadata and
school templates. Existing start_date/end_date storage matches the rest of the
backend; new calendar APIs expose starts_on/ends_on. No duplicate semester table,
model alias, dual write or retired endpoint is retained. Historical migration
files and release history are not a runtime compatibility layer.

Every consumer moves with the release. Current-term selection uses the class's
mapped grade; year carry-forward matches within the same grade band. School-wide
breaks union grade intervals so one grade's holiday is not shown as a whole-school
break. Metadata for the future marks/report workflow does not activate it.

Production release and pilot acceptance remain separate. Staging access, devices
and earlier phase acceptance do not freeze this independently tested change.
