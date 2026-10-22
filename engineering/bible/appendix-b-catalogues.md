# Appendix B: module and notification catalogues

The living copy of the Build Bible's Appendix B. The registries in code are `admin-panel/config/modules.php` and `admin-panel/config/notification_types.php` (both arrive in Phase 1); this page lists what they hold and what is planned. The **Status** column says what exists in the code today.

## Modules

Always on: school set-up and branding, people and approval, years and terms, academics, attendance, marks and reports, communication centre, notification settings.

| Key | Module | Needs | Default | Phase | Status |
| --- | --- | --- | --- | --- | --- |
| `intake` | Digital intake and admissions | | off | 4 | Planned |
| `fees` | Tuition fees and billing | | off | 5 | Planned |
| `wallet` | Wallet | | off | 5 | Planned |
| `vendors` | Vendors and storefronts | `wallet` | off | 5 | Planned |
| `ticketing` | Ticketing | `vendors` | off | 5 | Planned |
| `cards` | ID cards | | off | 4 | Planned |
| `card_pay` | Card payments | `cards`, `wallet` | off | 5 | Planned |
| `content` | Content upload | | on | 2 | Planned |
| `tutor` | AI tutor | `content` | off | 3 | Planned |
| `mastery` | Mastery map | | off | 3 | Planned |
| `practice` | Daily practice | `mastery` | off | 3 | Planned |
| `exam_coach` | Exam coach | `tutor` | off | 3 | Planned |
| `teaching_assistant` | Teaching assistant | `content` | off | 3 | Planned |
| `test_insights` | Test insights | `mastery` | off | 3 | Planned |
| `digest` | Weekly parent digest | | off | 3 | Planned |
| `early_warning` | Early warning | | off | 3 | Planned |
| `discipline` | Discipline | | off | 3 | Planned |
| `online_exams` | Online exams (seed) | | on | kept | Planned |
| `chat` | One-to-one chat (seed) | | on | kept | Planned |
| `groups` | Sport, arts and culture groups | | off | 4 | Planned |
| `wellbeing` | Incident reporting and counsellor support | | off | 4 | Planned |
| `house_points` | House points | | off | 4 | Planned |
| `awards` | Awards | | off | 4 | Planned |
| `consultations` | Polls, surveys and elections | | off | 4 | Planned |
| `photo_tags` | Photo tagging | | off | 4 | Planned |
| `ask_school` | Ask-the-school assistant | | off | 4 | Planned |
| `peer_tutoring` | Peer tutoring | `mastery` | off | 4 | Planned |
| `pathway` | Pathway planner | | off | 4 | Planned |
| `web_<portal>` | Web access per portal | | on | 0 | Planned for Phase 1's registry (the web builds shipped in Phase 0; the switch arrives with `config/modules.php`) |
| `reading_coach` | Reading coach | | off | 6 | Planned |
| `portfolio` | Verifiable portfolio | | off | 6 | Planned |
| `ask_navigateed` | Ask NavigateEd (admin) | | off | 6 | Planned |

## Notification types

Each type has a key, a module, portals, a default per channel (email, in-app) and whether NavigateEd locks it.

Locked everywhere: `account.verify_email`, `account.password_reset`, `account.new_device`, `approval.requested`, `consent.requested`, `safeguarding.alert` (to safeguarding staff).

Families, growing per phase: `academics.*` (marks released, report published, register amended, content published), `attendance.*` (absence alert), `comms.*` (message, notice, consultation open, consultation closing), `fees.*` (invoice, reminder, receipt, mandate), `wallet.*` (top-up, low balance, withdrawal status, order, ticket), `groups.*`, `discipline.*`, `awards.*`, `house_points.*`, `growth.*` (digest, band change, practice reminder), `intake.*` (application received, offer, enrolled), `cards.*` (issued, frozen, scanned at gate).

## Phase 0

Phase 0 added no module and no notification type: `config/modules.php` and `config/notification_types.php` arrive in Phase 1. The web builds of both apps shipped without a switch; `web_<portal>` (one switch per portal, default on) is the first entry of Phase 1's module registry. The queued mail and push jobs of Phase 0 (`SendMail`, `SendPush`) carry the existing notifications unchanged and record each attempt in `notification_deliveries`; the notification types and their rules attach to them in Phase 1.
