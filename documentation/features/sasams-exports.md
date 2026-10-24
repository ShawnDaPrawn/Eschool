---
title: SA-SAMS exports
---

An academic manager opens **Academics → SA-SAMS exports**. Choose the private
JSON layout prepared with your school's SA-SAMS operator. It identifies the
target version and import menu, the school and year, exact column headers,
learner references and any target code mappings. For Excel, also choose the
matching blank XLSX template. Download the ZIP and keep it private.

The export reads dated enrolments, daily homeroom entries or one assessment
task's recorded marks. It never changes the register, marks or account access.
Missing attendance, unresolved marks and unmapped codes produce a clear error;
they do not become present days or zero scores. Marks use exact decimal scores.
Attendance exports omit period registers. A task export uses dated group and
school enrolment at its due date.

CSV follows the layout's ordered headers and delimiter, with UTF-8 text and
CRLF rows. Excel preserves the supplied template's header metadata and writes
text values to retain leading-zero identifiers. The blank workbook must contain
one sheet, at most 40 columns and no data below the declared header. Formulas,
macros, embedded objects and external links are refused. If your official format
needs additional workbook structure or numeric cell types, qualify an adapter
against that exact template before using it; this bridge does not guess.

The manifest records version, import menu, row count and layout/template/output
hashes, signed with the server's HMAC key. It contains no learner rows or private
reference map. The `target_import_verified` value remains false: successfully
generating a file does not prove compatibility with your SA-SAMS installation.

Back up SA-SAMS, import into a test copy of the matching version, and compare
learner identities/counts, daily codes, task maxima and scores before using the
export for a live submission. Keep the operator's version-specific acceptance
record separately. Never publish a real layout containing learner reference maps.
