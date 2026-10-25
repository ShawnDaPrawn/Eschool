# Consultation panel

The real staff panel now supports scoped consultation lists, draft authoring,
named audience search/paging, dynamic single/multiple/text questions and election
candidate statements. Publication freezes configuration; reasoned closure and
cancellation retain it. Staff can vote only through their actual audience, review
aggregate denominators/counts and view configuration history. Only authorized
managers see paged individual closed-survey answers; anonymous rows have no name,
response UUID or timestamp.

Five HTTP cases cover the actual authoring→publish→staff vote→close→results→history
flow; invalid authoring preserves only bounded escaped text to the current author
without old-input/error flash; wrong account/role/source and a legitimate other
school manager cannot read or mutate the record. The historical-year early guard
returns a private refusal with no anonymous answer reflection or session storage.
Together with nine navigation cases and Bootstrap/design-token checks, all
16 tests / 681 assertions passed. Whole-app Larastan, affected Pint, TypeScript
and JavaScript syntax passed. Added labels have explicit English fallbacks in all
four panel catalogues; fluent language review remains outstanding.

Actual Chromium workflows passed at 390, 820 and 1280 pixels, 51.4 seconds total:
AJAX audience choices and unsaved-title retention; authoring/frozen publication;
separate admin and teacher cookie contexts; one anonymous response and a clear
already-recorded state; manager and voter private-result refusal before close;
manager-only text and aggregate-only voter results after close; configuration
snapshot history; escaped script-like titles; axe serious/critical violations,
duplicate IDs, horizontal overflow and own-origin errors all clean. The browser
harness uses one login per role per width rather than relaxing production login
limits. It runs via the normal flows CI project.

The actual picker caught a cookie-authentication nuance: a no-referrer HTML page
omits the header Sanctum uses to identify stateful GET requests. Its first-party
fetch now explicitly uses `referrerPolicy: same-origin`, account/school headers,
no-store and current authority, while retaining no-referrer page responses and
sending no referrer to external hosts. Late/cancelled picker responses are ignored;
a refused account/school/source erases the editor. Background/bfcache erases private
fields and reloads before reuse. Existing mastery/risk/discipline/progress panel
scripts and views contain no API fetches and remain ordinary server forms.

This is the panel slice; family/shared-app readers and scheduled delivery/live
adapters remain active follow-ons. No deployed latency, external email, physical
hardware, app-store or production acceptance evidence is claimed.
