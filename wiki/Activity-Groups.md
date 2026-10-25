Activity groups extend the current Laravel academic/communication platform with
seasonal school-life groups and dated authority. The initial backend is available
through API v2 and the group-management panel. The teacher and family apps share group, session and material views; coaches can record explicit attendance.

## Enable and allocate

The groups module is entitled in the declared school packages but starts off.
School managers enable it through the existing module controls, then open Activities → Activity groups. The workbench manages definitions, eligibility, seasons, dated people, sessions, explicit attendance and authored materials. The explicit
activity-groups-manage permission controls definitions and staff/membership edits.
Facilitator memberships have no Teacher or Super Admin permissions. Assign a
teacher or facilitator to a group with dates; a group administrator's subtree
grant is explicit, while ordinary facilitators retain their direct groups only.

Use active=false to cancel an existing allocation, including a future one.
Ending a membership uses inclusive dates. Historical records and attendance are
retained; current authority is checked before every read/write and UUID replay.

## Private API

/api/v2/activities/groups provides scoped lists, definitions, staff rosters,
allocations, sessions, explicit attendance and sanitized authored Markdown.
Bodies use operation UUIDs and optimistic revisions. Parents/learners see only
current own groups and published materials; they never get peer rosters.
People with both parent and staff roles keep those rights distinct.

Existing message workbench supports activity-group audiences and descendant
cascades within the author's authority. API editors opt in with activity_groups=1;
legacy editors retain their existing scopes and receive an upgrade instruction
for activity drafts. Published message readers keep their existing shape.

## Qualification

The backend/contract tests cover role, school, owner and guardian boundaries.
The native facilitator identity remains unavailable until its dedicated scoped sign-in and restricted shell are delivered; teacher and family apps can already open the shared group views. No store registration, physical device, staging
performance or production release is inferred from API and unit tests.

## See also

- [Roles-and-Permissions](Roles-and-Permissions)
- [Notifications](Notifications)
- [API-Overview](API-Overview)

When the school enables live updates, an open group shows the current online
count. It counts people rather than devices and expires without a heartbeat;
it is not an attendance register. Group changes refresh permitted detail.
An unsaved capture keeps its draft and shows an update prompt for a safe refresh.
