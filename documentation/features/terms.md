---
sidebar_position: 19
---

# School terms

Open **Academics → School terms** and select a school year. Administrators with
term permissions can add a name, start and end dates, and the grades covered.
Grade R is represented as 0 in numeric inputs. A full-school term covers R–12.

Terms can share dates when their grade ranges do not intersect. For the same
grades, a term must start after the preceding term ends. Term dates must fit the
selected school year. Marks-lock and report dates are optional; leave them empty
while preparing the calendar. These dates prepare the future assessment workflow;
marks enforcement and report release are separate features.

## Reuse a school calendar

1. Set up a year's terms and choose **Save calendar template** with a unique name.
2. Select another year and the saved template, then choose **Preview calendar**.
3. Review the shifted dates and grade ranges. Preview changes no calendar entries.
4. Choose **Apply calendar template** to add missing terms. Existing dates are
   retained; exact matches are skipped. Conflicts and invalid leap-day dates need
   correction before that calendar can be applied.

The new-year wizard also offers a calendar template. Selecting it supplies the
new calendar instead of the manual term rows. Preparing a year keeps the current
year active until the separate year-switch action.

## Edit or remove

Expand **Edit term** to adjust dates and optional metadata. A term cannot be moved
to another year. A term referenced by academic records cannot be removed, and its
grade range cannot be changed until those records are reassigned. Calendar edits
are audited. Imported dates that need review are retained and identified rather
than silently changed.

Semesters have been retired under the owner's confirmation that no installed
clients depend on them. Existing IDs and academic links migrate in place; there
is one term calendar rather than a parallel compatibility system.

School terms offers an explicit class enable action for the selected year. It
requires term editing and class-subject allocation permission, keeps academic
records intact, and opens the class subject editor to review allocations. Whole-year
allocations are not automatically assigned to a term. There is no bulk wipe or
destructive disable operation. Exam/result filters remain usable without a
school-wide current term, including schools using only grade-band calendars.
