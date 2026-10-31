# Product catalogue and packages

Action Plan step 1.16. The 44 technical modules a school switches
(`config/modules.php`) are too many to sell or price. They are grouped into ten
product modules (`config/product_modules.php`):

| Product module | Technical modules |
| --- | --- |
| Core (always included) | school, people, academics, communication, the four web portals |
| Learning | online exams, mastery map, daily practice, exam coach, teaching assistant, test insights, learner diary |
| AI tutor | subject tutor |
| Pastoral care | wellbeing, support plans, early warning, discipline, My grade |
| Family engagement | weekly digest, family forms, meetings and early collection, gallery, consultations, school assistant, live updates, direct messages |
| Activities and culture | activity groups, house points, awards, peer tutoring |
| Futures | pathways, school transfers |
| Leadership and staff | leadership dashboard, relief cover |
| Finance | fees, wallets, vendors, counter payments, ticketing |
| Access and admissions | ID cards, online registration |

Each has a **readiness level** (1 designed, 2 partly built, 3 built but
unproven, 4 verified on staging with real services, 5 used by a real school)
and a **monthly price per learner before VAT** in integer cents, `null` until
decided. Only level 4 and 5 product modules are offered to paying schools;
today everything is level 3, and the AI tutor level 2.

## Packages

Packages are built from whole product modules and live in the database since
SA.7 ([package editor](package-editor.md)); `config/school_packages.php`, which
added **`pilot`** (every product module, for the design-partner pilot), is now
only the seed. The `price_cents` placeholders in `config/product_modules.php`
were never set; prices are effective-dated rows in `platform_prices`.

## Platform page

`/saas/packages` ("Product modules and packages", in the platform bar): the
packages with their status, schools and price today, each product module with
its readiness and whether each package includes it fully, partly or not at
all, and what each contains. Platform staff with `packages.view` only.

## Tests

- `Unit/ProductModulesTest`: ten product modules; every technical module in
  exactly one; readiness 1 to 5; price integer cents or unset; Core always
  included and in every package; `pilot` equals all product modules.
- `Platform/ProductCatalogueTest`: platform staff see the page; school staff
  without the platform grant are refused; a school can be put on `pilot`.

## Not yet

Invoices from the prices (SA.8, 1.17).
