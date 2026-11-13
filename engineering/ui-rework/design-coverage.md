# Revision 3 design coverage

Every one of the 1,312 unique design destination/state IDs has an explicit implementation mapping in `design-coverage.json`. The linked domain traces identify actual views, route states and source handlers. A mapping is not a claim that each state received a separate browser test.

| Module | Design contracts |
|---|---:|
| Academics | 239 |
| Settings | 195 |
| Attendance | 40 |
| People | 211 |
| Extracurriculars | 92 |
| Community | 128 |
| Discipline | 35 |
| Home | 40 |
| Leave | 53 |
| Finance | 181 |
| Vendors | 44 |
| Awards | 54 |

The catalogue includes creation, edit, detail, result, refusal, configuration and shared interaction states. Result/exception variants use the real workflow receipt, record or shared error state rather than fake standalone pages. It distinguishes revised source screens, new panel adapters, source domain additions and inherited functionality.

Readiness is based on specific checks rather than an enabled switch. Unknown checks say Review configuration. Event consent/ticket associations are explicit school-owned links; they do not submit guardian responses. Facilitator temporary cover does not grant permanent group authority or an additional attendance permission.

The design’s source references include historical filenames and incomplete dynamic route prefixes. The actual implementation paths in the owner traces take precedence; `EX307`/`EX310` retain the design’s historical chat-service source reference while their current chat views/services are used. Runtime and regression results belong in `verification.md` and the domain ledgers.
