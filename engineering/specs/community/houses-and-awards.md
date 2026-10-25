# Houses, points and permanent awards

House points use dated physical enrolment and house membership, frozen house and
grade provenance, signed integer amounts, an atomic staff/day issue budget and
reasoned append-only reversals. Reversing an entry does not refill its original
staff issue budget. Public school counters contain aggregates; private reasons
never enter school-wide events. The declared initial daily cap is 50, editable by
school managers; public points start off. House history is not a permanent-profile
field. House names, emblems and colours are managed in the existing school theme.

Award categories have school, grade/subject or activity-group scopes and bounded
subcategories. Definitions are reusable across years; a year-specific open window
determines nomination availability. Dated grade heads, group category heads and
explicit category administrators use existing institutional authority. A separate
nomination records the author and reason, while permanent presentations snapshot
the published title, criteria, date and optional event. Outside awards are explicit
one-off records. Annual definitions prevent duplicate active presentations;
reasoned withdrawal retains their history. A school can require principal approval;
that additional check starts off rather than imposing an unconfigured gate.

The My grade service contract is AwardNominations::catalogue(actor,year,grade)
and nominate(actor,input), with stable operation UUIDs and fresh learner/category/
window/grade eligibility before replay. No consumer writes recognition tables.
Both modules start off. Domain, API, panel, affected app and live-counter evidence
are recorded separately; this specification is a plan until those checks run.

## Domain implementation evidence

The first domain increment passes 22 MySQL tests (97 assertions), Pint and the
configured whole-application static analysis. See
[the domain qualification](../../self-tests/houses-awards-domain.md). API, UI and
live delivery remain separate integration work; no phase completion is asserted.

## Annual groups and private delivery

A reusable group category can explicitly select a different dated activity group
for each school year through its award window. The original category scope stays
immutable; no name matching grants authority. Group subcategories keep their
parent's annual group, and a year with nominations or presentations cannot be
remapped. Current group heads and current learner membership govern new entries.

Family notices use current source/relationship checks before counting and delivery.
Private recognition invalidations contain source references and go only to fresh
family recipients. School counters retain aggregate-only events. App readers clear
private content across account, school, child, capability and lifecycle changes;
background resume requires fresh authorization.
