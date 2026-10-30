# Package and price editor

Action Plan SA.7 (Build 1). Owner role: Backend and Admin UX (HQ).

Packages used to be hard-coded in `config/school_packages.php`, with no prices.
They now live in the database, built from the ten product modules
([product catalogue](product-catalogue.md)), with effective-dated prices that
billing (SA.8, 1.17) will read. HQ edits them at `/saas/packages`. The config
file is kept only as the seed of the migration.

A school's **agreed contract price** stays on its contract
(`school_contracts.price_per_learner_cents`, [HQ school accounts](hq-school-accounts.md)).
The price list here is Navigate Ed's list price; it does not change a contract.

## User stories

- As Finance or an Owner, I create a draft package, choose its product modules and trial days, and activate it when it is ready to sell.
- As Finance, I set a package's price per learner per month from a date, add a discount for a period, and price product modules as add-ons, and I can see what the price was or will be on any date.
- As Finance, I change an active package's modules knowing that schools already on it keep what they have until I re-apply the package to them, with a reason.
- As Sales or Content, I see the packages, their modules and prices, and change nothing.
- As a school, what I am entitled to never changes because HQ edited a package, only when HQ moves me to a package or re-applies mine.

## Rules

- **Who.** `packages.view` (Owner, Operations, Finance, Content, Sales) reads `/saas/packages` and each package page. The new ability `packages.manage` (Owner, Finance) is needed for every change, and the pages show no form without it. Support has neither. School accounts never reach `/saas`. The FormRequest checks `packages.manage` and the service checks it again (403).
- **Package.** A key (lower case letters, digits and dashes, unique, never changed: schools name their package by it), a name, a description, trial days (0 to 365) and a status.
- **Status.** Draft, then active, then retired. Only an active package can be given to a school (when it is set up, or by changing its package). Retiring stops new schools being put on it; schools already on it keep it. A retired package is history: its details and modules cannot change, and it cannot be activated again.
- **Modules.** A package is a set of whole product modules; Core is always included. The technical modules a school's switches show (`config/modules.php`) follow from the product modules (`config/product_modules.php`). A technical module added to a product module in a code release joins every package that includes that product module, as `pilot` already did.
- **The entitlement rule.** A package's module set is versioned. While a package is a draft no school can be on it, so its set changes in place. Once active, saving a different set adds a new version. Each school records the version applied to it (`schools.package_version`; empty means version 1, so every existing school keeps exactly what it had). The module gate reads the school's version, never the package's latest. So:
  - changing an active package's modules changes nothing for schools already on it: their `school_modules` rows and what the gate allows stay the same;
  - a school given the package from then on (set up or moved to it) gets the latest version;
  - HQ brings the schools on an earlier version up to date with **Re-apply to these schools**, giving a reason. Each school is audited (`platform_package_reapplied`, with the old and new version and the reason), its apps refresh (`context.changed`), and a money module the new set leaves out stays readable but not writable while the school has its records, exactly as for a package change ([SaaS panel](saas-panel.md)).
  - The package page and the school's page say when a school is on an earlier version.
- **Prices.** Per learner per month, in integer cents, **excluding VAT**. Three kinds, each its own dated series per package:
  - the **base price** of the package;
  - an **add-on price** for one product module (not Core) added to the package;
  - a **discount** off the base price (more than R 0.00; the package price never goes below zero).
- **Effective dates.** A price has a start date (today or later, in South African time) and an optional end date (not before the start).
  - Adding a price while an open-ended price of the same kind started earlier ends that one the day before the new one starts; the new price must then be open-ended too.
  - Any other overlap with a price of the same kind is refused, and nothing is written.
  - A price in force or past is never edited: there is no form that changes its amount or dates, and it cannot be withdrawn.
  - A price that has not started can be withdrawn, the latest of its kind first. If it had ended an open-ended price, that price is open-ended again. Withdrawn prices stay in the history.
- **Money input.** Rands as typed, digits with up to two decimals (`89`, `89.5`, `0.07`), up to R 999 999.99. Converted to cents as text (`Money::fromDecimalString`), never through a float. Commas, signs, exponents and more decimals are refused.
- **Price on a date.** The package page shows the base, discount, package price and add-ons in force on any chosen date. Billing calls `PlatformPackages::priceOn($packageKey, $date, $productModule = null)`: without a module, the package price (base less discount) in cents; with one, that module's add-on price; null when no such price is in force. `quote($packageKey, $date)` gives the breakdown.
- **One reader.** Everything that read `config/school_packages.php` reads the tables through `PlatformPackages`: the module gate, the package choices and validation in `/saas`, the product catalogue page and the fee-reminder delivery scope (a SQL check on the school's applied version, so a prepared query rechecks it when it runs).
- **Audit.** Every change, with the values before and after: `platform_package_created`, `platform_package_updated` (only the fields changed), `platform_package_modules_changed` (old and new version and modules, and how many schools are on an earlier version), `platform_package_activated`, `platform_package_retired`, `platform_package_price_added`, `platform_package_price_ended` (an open-ended price ended or reopened), `platform_package_price_withdrawn`, and `platform_package_reapplied` (against each school). Moving a school's package also records the version it was given (`platform_school_package_changed`).

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `platform_packages` | global | key, name, description, status, trial days, current module set version, when activated or retired, who created it |
| `platform_package_modules` | global | one row per product module in each version of a package's set |
| `platform_prices` | global | package, kind (`base`, `module`, `discount`), product module (add-ons only), amount in cents, `vat_excluded` (always true), `effective_from`, `effective_to` (empty: no end), note, the price it ended, withdrawn when and by whom, who added it |
| `schools.package_version` | the school row | the module set version applied to the school; empty is version 1 |

Migration `2026_11_01_140000_create_platform_packages` creates them and seeds `legacy`, `core`, `standard` and `pilot` from `config/school_packages.php`, each active, version 1, no trial and no price (no price had been decided). It refuses to seed a package holding part of a product module. Its `down` refuses once HQ has added a package, a version or a price, applied a version to a school, or changed a seeded package in any way (name, description, status, trial days, version, or any later save).

## Acceptance tests (`Platform/PackageEditorTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_the_seeded_packages_match_the_old_config` | the migrated database | read the packages | the same keys, all active at version 1; each grants exactly the config's modules; no prices; no school has a version; the gate gives schools 1 (legacy) and 2 (core) what the config did |
| `test_rolling_back_refuses_once_hq_changed_a_seeded_package` | the seeded packages | rename one in the editor; retire, re-describe, re-time, re-version or re-save one; run the migration's `down` after each | `down` refuses every time and the tables stay |
| `test_finance_creates_edits_and_activates_a_draft_package` | Finance | create a draft with Learning; give it to a school; change its modules; edit it; activate it; give it to a school; retire it; then change, reactivate or give it | Core added; a draft cannot be given; modules change in place; each change audited with before and after; once active the school gets version 1; once retired every change is refused and the school keeps it |
| `test_prices_are_effective_dated_never_overlap_and_are_never_edited_once_in_force` | an Owner, the Core package | add base prices, a discount and an add-on; overlapping, past, malformed and zero-discount prices; withdraw prices; move time on | the right price on each date; the earlier open-ended price ends the day before; refusals write nothing; only the latest unstarted price can be withdrawn and reopens the one it ended; once in force a price cannot be withdrawn or changed, and a new price from today ends it yesterday |
| `test_money_is_parsed_from_rands_text_without_floats` | rands text | convert; post `0.29` and `12,50` | exact cents for each; malformed text refused; 29 cents stored; the comma refused |
| `test_sales_content_support_and_operations_cannot_change_packages_or_prices` | each role, a price | every write endpoint, and each service method directly | 403 everywhere and nothing written; Sales, Content and Operations read the pages without forms; Support cannot read them |
| `test_school_accounts_are_refused` | a school Super Admin and a teacher | read a package; create, price or change one | 403; nothing written |
| `test_an_unknown_package_or_another_packages_price_is_not_found` | a price on Standard | withdraw it through Core; any action on an unknown package | 404; nothing written |
| `test_changing_a_packages_modules_reaches_existing_schools_only_when_reapplied` | school 2 on Standard with fee history | Finance leaves Finance out of Standard; then re-applies without and with a reason | version 2; school 2's `school_modules` rows, gate state and apps unchanged, and the pages say it is on an earlier version; a new school would get version 2; without a reason refused; with one, school 2 is on version 2, audited with the reason, fees readable but not writable, `context.changed` queued; school 1 untouched; a second re-apply does nothing |

The existing package tests still pass unchanged: `Platform/ProductCatalogueTest`, `Platform/SaasPanelTest` (its /saas route count rises by the eight routes below), `Platform/PlatformRolesTest`, `Unit/ProductModulesTest`, and `Platform/LedgerSnapshotTest` (its synthetic packages are now rows instead of config). `Platform/ContextTest` allows one more fixed query for `/me/context`: the gate reads the module set of the school's package version once per request.

## Endpoints (HQ panel)

| Method | Path | Name | Ability |
| --- | --- | --- | --- |
| GET | `/saas/packages` | `saas.packages` | `packages.view` |
| GET | `/saas/packages/{package}` | `saas.packages.show` (`?on=YYYY-MM-DD` for the price on a date) | `packages.view` |
| POST | `/saas/packages` | `saas.packages.store` | `packages.manage` |
| PUT | `/saas/packages/{package}` | `saas.packages.update` | `packages.manage` |
| PUT | `/saas/packages/{package}/modules` | `saas.packages.modules` | `packages.manage` |
| PUT | `/saas/packages/{package}/status` | `saas.packages.status` | `packages.manage` |
| POST | `/saas/packages/{package}/prices` | `saas.packages.prices.store` | `packages.manage` |
| POST | `/saas/packages/{package}/prices/{price}/withdraw` | `saas.packages.prices.withdraw` | `packages.manage` |
| POST | `/saas/packages/{package}/reapply` | `saas.packages.reapply` | `packages.manage` |

Every write uses `PackageRequest`, and `PlatformPackages` validates the same rules and checks `packages.manage` again. No API route or response changes.

## Not in this step

- Invoices, payments and VAT amounts (SA.8, 1.17). Billing reads `priceOn()`; how a school's contract price and the list price combine is decided there.
- Giving one school an add-on module outside its package. Entitlements still come only from the package; add-on prices are recorded for billing and sales.
- Volume or percentage discounts: a discount is a fixed amount per learner per month.
- Re-applying a package to some of its schools only: re-apply brings every school on an earlier version up to date.
- Deleting a package or reactivating a retired one.
