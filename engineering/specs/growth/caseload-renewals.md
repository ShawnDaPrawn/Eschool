# Caseload renewals into a new year (Action Plan 1.20)

Risk caseload grants (`risk_caseloads`, see [early warning and owned interventions](risk-interventions.md)) belong to one school year and end with it. When a new year is set up, each open grant from last year is offered for renewal so that no open case loses its professional. The administrator keeps the same professional, chooses another one, or does not renew.

## User stories

- As a principal, after the year switch I want last year's open caseloads offered in one list, so that each counsellor keeps their learners without my re-entering every grant.
- As a principal, when a counsellor has left, I want to give their open cases to another counsellor, or let them end.
- As a grade head, I renew only the caseloads of learners in my grade.
- As a teacher or other staff member without caseload administration, I see nothing of this.

## Rules

- **Where.** Go to **Early warning and interventions** and choose **Renew caseloads for a new year** (`/risk-interventions/renewals`). The link appears next to Risk caseload assignments for people who administer caseloads. It is a panel action on the new year and can be used at any point once that year exists. The year defaults to the year being prepared or, once the switch has happened, to the current year. The year must not have ended. "Last year" means the school year that starts just before it, including a deleted one. If that year was deleted, nothing is renewed, and an older year never stands in for it.
- **Who.** Only someone who administers caseloads: a dated principal, or a dated grade head for the learner (`RiskAuthority::administer`), with the early-warning module switched on. Everyone else gets 403 and sees nothing. That includes teachers, staff without restricted pastoral access, and counsellors who hold the cases themselves. A grade head sees only the cases they administer in both years. In a retained year, the learner's grade comes from that year's dated early-warning history, as for every other retained-year read. For the new year, authority is checked for the renewal itself, by `RiskAuthority::administerOn`, which only renewals use. The principal or grade-head appointment must belong to the new year and cover the renewal start date. Without early-warning history in the new year, the learner's grade is the one on that date. So a grade head appointed for the new year can renew before the switch, and a head whose appointment ends with the old year renews nothing. Every other caller, including the panel and v2 assignment endpoints, keeps `administer()` as of today, unchanged.
- **Open cases only.** A grant counts as open when it was not ended early (`revoked_at` is empty) and it is still running today or ran to the last day of last year. Ended and expired grants are never offered.
- A case is offered only if the learner has a place (an enrolment) in the new year. The page states how many open cases are not offered for this reason, without naming the learners.
- **The choice.** For each case the choices are the same professional (the default, while they still pass `SensitiveRecordsAccess` and the grant has not already ended; a lapsed grant is shown with its end date and defaults to Do not renew), another professional who currently has restricted pastoral access (a Counsellor or Safeguarding member with the permission), or Do not renew. A professional who has lost access is shown as such and cannot be chosen.
- **Writing.** Each renewal writes one ordinary grant through `Interventions::assign`, from today or the new year's first day (whichever is later) until its last day. Like every grant, its dates must lie inside its school year: `Interventions::assign` now refuses dates outside the year with a validation error, for manual assignments too. It is audited `growth.risk_caseload_changed`, and an extra `growth.risk_caseloads_renewed` row records the counts.
- **No wider access.** A renewal never widens who can see a case. It is bounded to the new year and gives one professional the grant, exactly as the administrator could do by hand. It gives no access to the administrator, to other professionals, or to the professional who held the ended grant. It does not touch interventions or their bodies.
- **Stale pages and repeats.** The page carries a fingerprint of the grants it shows (last year's open grants and the learners' grants in the new year), plus who is placed and which professionals are eligible. Renewing recomputes it and refuses, writing nothing, if anything changed. A learner who already has any grant in the new year is shown as already done and is not offered again, so renewing the same page twice writes nothing the second time.
- Like the other private risk writes, a renewal is bound to the account and school that showed the page, carries nothing in the query string, and responses are `no-store`.
- A case or professional from another school, or one not open on this page, is refused with nothing written.
- The form stays within PHP's input limit (`max_input_vars`, 1000 by default): it has one field per case and offers at most 500 open cases per submission (`CaseloadRenewals::PER_SUBMISSION`), with the rest offered once these are renewed. The form's last field is a completeness marker. A submission without it, which is what happens when the server drops trailing fields, is refused with nothing renewed.

## Data

There is no new table. The feature reads and writes `risk_caseloads` and reads `enrolments`.

## Acceptance tests (`Growth/CaseloadRenewalsTest`)

| Test | What it shows |
| --- | --- |
| `test_open_cases_are_offered_and_renewed_into_the_new_year_and_closed_ones_never` | Asha's and Cara's open grants are offered, while an ended grant and one that expired in June are not. Renewing Asha to Tara writes one 2027 grant from today to 31 December. Tara can open the case, and another counsellor cannot. The same page again is refused and writes nothing. |
| `test_a_professional_who_lost_access_is_not_kept_and_the_case_can_be_reassigned` | Tara loses the Counsellor role: she is not kept, cannot be chosen, and the case goes to Theo. |
| `test_learners_with_no_place_in_the_new_year_are_not_offered` | Cara has no 2027 place: her case is not offered, it is counted, and it is refused if posted. |
| `test_the_panel_offers_and_renews_caseloads_for_the_principal` | The page lists the cases and renews them. A query string gives 422, and the wrong account binding gives 403. |
| `test_staff_who_do_not_administer_caseloads_see_nothing_and_cannot_renew` | A teacher without pastoral access, and a counsellor who holds the case, both get 403 from the service and both routes, with no learner name shown and nothing written. |
| `test_a_grade_head_renews_only_the_cases_of_their_grade` | The grade 7 head sees and renews Asha only. Cara (grade 8) is refused, and the head gains no grant. |
| `test_a_grade_head_renews_before_the_new_year_starts_from_its_future_enrolments` | On 10 December, the grade 7 head (also appointed for 2027 from 1 January) renews Asha, whose 2027 enrolment starts on 1 January. The grant starts that day. If Asha is placed in grade 8 instead, she is neither offered nor assignable. |
| `test_a_renewal_cut_short_in_transit_is_refused_and_nothing_is_renewed` | The marker is the form's last field. A submission without it is refused and writes nothing. |
| `test_a_grade_head_whose_appointment_ends_with_this_year_renews_nothing_into_the_next` | A head appointed only until 31 December sees nothing for the new year and is refused. |
| `test_the_existing_assignment_endpoints_still_refuse_a_grade_head_for_a_year_to_come` | For a year to come, a grade head still gets 404 from the panel assignment POST, from v2 `PUT` assignments, assignments and professionals, and from `Interventions::assign` without the renewal date, as before. |
| `test_a_grant_outside_its_school_year_is_refused` | A grant starting before its year or ending after it is refused. |
| `test_a_lapsed_grant_is_offered_but_not_kept_by_default` | On 11 January a grant that ended on 31 December defaults to Do not renew. On 10 December it defaults to the same professional. |
| `test_the_page_defaults_to_the_year_being_prepared` | Before the switch, the page opens on the upcoming year. |
| `test_a_deleted_year_just_before_is_not_replaced_by_an_older_one` | With last year deleted, an older year's grants are not offered. |
| `test_another_schools_cases_professionals_and_years_are_refused` | School B's case, professional or year from School A: validation error or 404. Neither school gains a grant. |
| `test_a_page_shown_before_a_grant_changed_is_refused` | A grant ended after the page was shown makes renewing fail, through the service and the page. The ended grant is no longer offered. |

## Not in this step

- The new-year switch does not renew anything by itself, and the wizard does not link here.
- Support-plan, wellbeing-case and learner-note grants are separate grants and are not renewed here.
- No app or v2 API change. The v2 `growth/risk` paths, schemas and authority are unchanged. The one behaviour change is that a grant with dates outside its school year is now refused with a validation error (422).
- Cases left as "Do not renew" are offered again on the next page. With more than 500 open cases, they stay at the front of the list.
