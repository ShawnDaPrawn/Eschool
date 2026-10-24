eSchool is a school management system. One Laravel server runs three things: the admin panel that school staff use, the school's public website, and the REST API behind two Flutter apps. One app is for students and parents, the other for teachers. Together they cover academic setup, admissions, attendance, timetables, lessons, assignments, exams and results, online exams, fees and online payments, leave, announcements, push notifications and chat.

This wiki is for people who build, run or change the system. For click-by-click guides on using the admin panel and configuring the apps, see the [documentation site](https://shawndaprawn.github.io/Eschool/).

## The system at a glance

```mermaid
flowchart LR
    subgraph Clients
        A["Admin panel<br/>(browser)"]
        W["Public website<br/>(browser)"]
        S["e-school app<br/>students and parents"]
        T["e-school-teacher app<br/>teachers"]
    end
    subgraph Server["admin-panel (Laravel 12)"]
        WEB["Web routes<br/>routes/web.php"]
        API["REST API<br/>routes/api.php"]
        DB[("MySQL / MariaDB")]
        FS[("storage/app/public")]
    end
    A --> WEB
    W --> WEB
    S -- "Bearer token" --> API
    T -- "Bearer token" --> API
    WEB --> DB
    API --> DB
    WEB --> FS
    API --> FS
    Server -- "push" --> FCM["Firebase Cloud Messaging"]
    FCM --> S
    FCM --> T
    PG["Payment gateway<br/>Stripe"] -- "webhooks" --> WEB
    S -- "checkout" --> PG
```

| Component | Folder | Stack | Version |
|---|---|---|---|
| Admin panel, public website, API | [`admin-panel/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/admin-panel) | PHP 8.3, Laravel 12.70, Sanctum 4, spatie/laravel-permission 6, Blade + Bootstrap + jQuery | — |
| Student and parent app | [`e-school/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/e-school) | Flutter (Dart `>=3.8`), flutter_bloc, Dio, Hive, Firebase | 3.5.0+39 |
| Teacher app | [`e-school-teacher/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/e-school-teacher) | Flutter (Dart `>=3.8`), flutter_bloc, Dio, Hive, Firebase | 3.5.0+32 |
| End-user documentation | [`documentation/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/documentation) | Docusaurus 3.10 | — |

## Who uses what

| Role | Uses | Signs in at |
|---|---|---|
| Super Admin and custom staff roles | Admin panel | `/login` |
| Teacher | Teacher app, and a limited admin panel | `/api/teacher/login`, `/login` |
| Student | Student/parent app | `/api/student/login` |
| Parent (guardian) | Student/parent app, switching between their children | `/api/parent/login` |
| Public visitors | School website, online admission form | `/` |

Roles and what each one may do are covered in [Roles and Permissions](Roles-and-Permissions).

## Where to start

**New to the codebase?** Read [Architecture](Architecture), then [Repository Layout](Repository-Layout), then the [Glossary](Glossary). The words *session year*, *class section* and *medium* appear everywhere.

**Setting up a dev environment?** [Getting Started](Getting-Started).

**Running it in production?** [Deployment](Deployment), [Configuration](Configuration), [Scheduled Tasks and Commands](Scheduled-Tasks-and-Commands), [Security Model](Security-Model#security-checklist-for-deployers), and the [runbooks](https://github.com/ShawnDaPrawn/Eschool/tree/main/engineering/runbooks) for deploys, releases, rollback, restore and incidents.

**Building the apps for your school?** [Mobile App Configuration](Mobile-App-Configuration), then [Building and Releasing Apps](Building-and-Releasing-Apps).

**Adding a feature or endpoint?** [Contributing](Contributing) (including [adding an admin module](Contributing#adding-an-admin-module-end-to-end)), [API Overview](API-Overview), [Security Model](Security-Model#rules-for-contributors), and for NavigateEd work the spec template and domain recipe in the [engineering folder](https://github.com/ShawnDaPrawn/Eschool/tree/main/engineering).

**Something broken?** [Troubleshooting and FAQ](Troubleshooting-and-FAQ); in production, the [incident runbook](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/runbooks/incident.md).

## Feature map

| Area | Admin panel | Apps | Wiki page |
|---|---|---|---|
| Academic setup: session years, mediums, classes, sections, streams, shifts, terms, subjects | Yes | Read-only | [Academic Structure](Academic-Structure) |
| Students, parents, teachers, staff, ID cards, promotion | Yes | Profiles | [Admin Panel Overview](Admin-Panel-Overview) |
| Attendance: students and staff | Yes | Teachers mark; students and parents view | [API Teacher](API-Teacher) |
| Timetable, lessons, topics, study material | Yes | Yes | [Admin Panel Overview](Admin-Panel-Overview) |
| Assignments and submissions | Yes | Teachers create; students submit | [API Student](API-Student) |
| Exams, marks, grades, report cards | Yes | Results | [Exams and Grading](Exams-and-Grading) |
| Online exams | Yes | Students take them | [Exams and Grading](Exams-and-Grading#online-exams) |
| Fees, installments, online payment (Stripe), receipts | Yes | Parents and students pay | [Payments](Payments) |
| Leave: staff and students | Yes | Yes | [Admin Panel Overview](Admin-Panel-Overview) |
| Announcements, custom notifications, push | Yes | Yes | [Notifications](Notifications) |
| Chat between teachers and students or parents | Settings | Yes | [Chat](Chat) |
| Public website, online admission, gallery | Yes | — | [Public Website](Public-Website) |
| Languages and translations | Yes | Yes | [Localization](Localization) |

## About this fork

This repository builds on the eSchool 3.5.0 codebase. Since then it has added CI, upgraded the apps to the current Flutter and Gradle toolchain, completed the translations, and hardened security throughout: API scoping by role, child and class, permission checks, SQL-injection and XSS fixes, upload filtering, webhook signature checks and security headers. See [Release History](Release-History) and [Security Model](Security-Model).

## NavigateEd

This codebase is the seed of **NavigateEd**, which grows it phase by phase, without a rewrite, into one platform for many South African schools: a platform kernel is added underneath the existing panel and API, and new domains beside them (see [Architecture](Architecture#where-it-is-going)). The engineering record lives in the repository, next to the code:

| What | Where |
|---|---|
| Phase plans, open questions and the defaults taken | [`engineering/phases/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/engineering/phases) |
| Feature specs | [`engineering/specs/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/engineering/specs) |
| Decision records (architecture, API v2, app packages, e-mail, student numbers) | [`engineering/adr/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/engineering/adr) |
| Runbooks: environments, deploy, release, rollback, restore, incident | [`engineering/runbooks/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/engineering/runbooks) |
| Threat model and security records | [`engineering/security/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/engineering/security) |
| Self-test runs | [`engineering/self-tests/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/engineering/self-tests) |
| What changed, newest first | [`CHANGELOG.md`](https://github.com/ShawnDaPrawn/Eschool/blob/main/CHANGELOG.md) (earlier history: [Release History](Release-History)) |

## See also

- [Architecture](Architecture)
- [Getting Started](Getting-Started)
- [Contributing](Contributing)
- [Deployment](Deployment)
