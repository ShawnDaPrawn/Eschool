# Teaching review and generation recovery

An approved draft could retain an enabled final confirmation after the editor
changed its visible definition. Any edit now clears that confirmation and blocks
practice publication and official mark recording until the edited revision is
saved, approved and explicitly confirmed again. The action handler also checks
the unsaved state. Refreshing a saved draft creates a fresh editor instance.

A rejected original generation POST (422) permits a corrected explicit submit.
Unknown generation outcomes retain their operation identifier. A missing receipt
(404), including on resumed refresh, leaves the workspace available and keeps
that same identifier for a subsequent explicit lookup. It never starts another
generation. Authentication and source access failures still erase the workspace.
Approved photo references remain bound to their original generation identifier.

Local verification on 26 October 2026: all 23 teaching repository/widget tests
passed, including six new cases covering practice, follow-up quiz and script-mark
edits, rejected generation correction and manual/resumed receipt races. The same
run passed all 13 parent-meetings repository/widget cases. Focused Dart analysis
has no errors or warnings. These widget/protocol checks do not claim a new native
device or production verification.
