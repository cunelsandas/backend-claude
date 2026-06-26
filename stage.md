# Bluemocha Backend — Migration Master Roadmap

> **Legacy:** `C:\xampp\htdocs\bluemocha_backend_git` (Laravel 8 + Blade + jQuery + Yajra DataTables)
> **Target:** `C:\xampp\htdocs\backend-claude` (Laravel 12 + Inertia.js + Vue 3 Composition API + Tailwind CSS 4)
> **Strategy:** Same MySQL DB. New tables only. Preserve 100% of business logic. Complete UI/UX redesign.

---

## Locked Architectural Decisions

| Area | Decision |
|---|---|
| **Auth & Permissions** | `spatie/laravel-permission`. Legacy positions → Spatie **roles**; messy per-user, per-menu overrides → direct **permissions** on individual users. |
| **Database** | Connect to legacy MySQL DB. Add new tables only via Laravel 12 migrations. Never alter existing legacy tables without explicit approval. |
| **Data Tables** | Inertia paginated tables. One reusable `<DataTable>` Vue 3 component driven by Laravel paginator + URL params (`?sort=&filter=&page=`). |
| **Realtime** | Keep Pusher (already in use). Evaluate Laravel Reverb later. |
| **Sprint priority** | Dashboard, Dashboard II, Dashboard Third, **Sale** first. Integrations (LINE, FB, Pusher, exports) layered later. |
| **File Storage** | Audit legacy `config/filesystems.php` in Sprint 1 before deciding. Default to `public/` disk until confirmed. |

---

## Legend
- [ ] Pending  ·  [x] Done  ·  [~] In Progress  ·  [!] Blocked

---

## SPRINT 0 — Discovery & Decisions ✅
- [x] Audit legacy stack (Laravel 8, ~50 controllers, Yajra DataTables, LINE/FB/Pusher)
- [x] Confirm auth strategy: Spatie roles + per-user permission overrides
- [x] Confirm DB strategy: shared MySQL, new tables only
- [x] Confirm table strategy: Inertia paginated tables w/ reusable Vue component
- [x] Confirm module priority: Dashboards → Sale → rest

---

## SPRINT 1 — Foundation Setup ✅
- [x] Initialize Laravel 12 in `backend-claude` (`composer create-project laravel/laravel`)
- [x] Install Inertia.js (server: `inertiajs/inertia-laravel` 3.1, client: `@inertiajs/vue3` 3.4)
- [x] Install Vue 3 (3.5.38) + Vite 7 + `@vitejs/plugin-vue`
- [x] Install Tailwind CSS 4 (4.3.1) + `@tailwindcss/vite`
- [x] Install `tightenco/ziggy` 2.6 for route helpers
- [x] Configure `.env` — placeholders for 3 MySQL connections (DB_*, DB_*_SECOND, DB_*_THIRD); SESSION/CACHE on file driver for now
- [ ] Audit `config/filesystems.php` from legacy → mirror disk config *(deferred — env.rar locked; revisit when legacy creds available)*
- [x] Set up base layout: `resources/js/Layouts/AppLayout.vue` (slate sidebar, topbar, content slot)
- [x] Set up base Vue page: `resources/js/Pages/Welcome.vue` + working Inertia route at `/`
- [x] Verify Inertia handshake — smoke-tested via curl, `data-page` JSON renders with shared props `auth.user`, `flash`, `app.name`
- [x] `config/database.php` — added `mysql_second` + `mysql_third` connections; `strict=>false` on all three
- [x] `bootstrap/app.php` — registered `HandleInertiaRequests` middleware (L11+ style)
- [x] `vite.config.js` — added Vue plugin + `@/` alias to `resources/js`
- [ ] Commit baseline scaffolding *(pending — git init not yet done; awaiting user decision on repo setup)*

---

## SPRINT 2 — Auth & Permissions Migration ✅
- [x] Install `spatie/laravel-permission` (6.25)
- [x] Publish & run Spatie migrations — 5 new tables added to legacy DB
- [x] `Role` seeder — 4 roles: `super-admin`, `production-operator`, `production-maintainer`, `accountant`
- [x] `Permission` seeder — 36 unique route-name permissions ported from legacy `config/permissions.php`
- [x] Role → permission matrix seeded (super-admin & accountant get all, production roles get scoped subsets)
- [x] Auto-assignment script — `RolesAndPermissionsSeeder::assignBySmaIds()` maps existing `backend_user` rows to roles via `hr_employee.sma_user` matches against legacy ID lists
- [ ] Replace legacy `IsLogin`, `IsLoginAccountant`, `SaleMiddleware`, `RootMiddleware` with Spatie middleware *(deferred — will retire incrementally as we migrate each module's routes in Sprints 5–11)*
- [x] Build `Pages/Auth/Login.vue` — Inertia form, CSRF, error handling
- [x] Build `AuthenticatedSessionController` — Inertia version with **rehash-on-first-login** for plain-text legacy passwords
- [x] Build `HandleInertiaRequests` sharing `auth.user` (incl. display_name, sma_id, employee_record), `auth.roles[]`, `auth.permissions[]`
- [x] Test login flow end-to-end — verified live with SMA 36 super-admin user (Phasin Thongkuea); dashboard renders with role + permission state
- [x] New migration: `add_auth_columns_to_backend_user_table` — added nullable `password_hash` + `remember_token` to legacy `backend_user`
- [x] `User` model maps to `backend_user`; `Employee` model maps to `hr_employee`

---

## SPRINT 3 — Shared UI Kit & Layout ✅
- [x] **Design system tokens** — "Bluemocha Console" aesthetic: Fraunces (display) + DM Sans (body) + JetBrains Mono (data); warm cream surfaces; slate brand + caramel accent; density variables; tabular numerals defaults
- [x] `<AppLayout>` — Fraunces logo wordmark + slate sidebar with accent active-bar indicators; permission-aware nav; click-outside-safe user dropdown; embedded `<ToastContainer>`
- [x] `<DataTable>` — feature-complete: sortable columns, sticky header, global search, per-column filter row, column show/hide menu, **density toggle** (3 levels) with **mono-mode** option, multi-row selection + bulk-action bar, CSV/Excel export hooks, Inertia-driven URL state, localStorage-persisted user prefs, custom cell slots, empty state, active-row left bar
- [x] `<DataTablePagination>` — smart page button generation with ellipses, per-page selector, tabular-numeral counts
- [x] `<FormInput>`, `<FormSelect>`, `<FormTextarea>`, `<FormCheckbox>`, `<FormRadio>`, `<FormSwitch>`, `<FormLabel>`, `<FormError>`, `<FormHint>` — all with inline Inertia `useForm` error display, mono variants, character counters, leading/trailing slots
- [x] `<Modal>`, `<ConfirmDialog>`, `<Drawer>` — ESC + click-outside dismiss, body-scroll lock, animated enter/leave, persistent mode
- [x] `<Button>`, `<Badge>`, `<Card>`, `<Tabs>` (underline + pill), `<Kbd>` — primitives w/ multiple tones/sizes/variants
- [x] `<DatePicker>`, `<DateRangePicker>` — flatpickr wrappers themed to match Console
- [x] `<ToastContainer>` + `useToast()` composable — auto-piped from Inertia flash; slide-in from bottom-right; supports actions, tones, manual dismiss
- [x] `Pages/UiKit.vue` — live demo page at `/ui-kit` exercising every component with a 6-tab navigation
- [x] Login + Dashboard pages refreshed with new tokens (editorial brand panel on Login, hero typography on Dashboard)
- [x] Vite build clean: 637 modules, ~76 kB CSS, ~252 kB JS (chunked, gzip 89 kB)

---

## SPRINT 4 — Master Data Modules ✅
Reference data used by Dashboards & Sale. Read from legacy tables; CRUD only for new master tables.
- [x] Branches / Warehouses — `Warehouse` model → `sma_warehouses`, read-only paginated DataTable at `/master-data/warehouses`
- [x] Product master listing — `Product` model → `sma_products`, eager-loads Brand + Category, `/master-data/products`
- [x] Customer master listing — `Client` model → `sma_companies` (scoped to non-suppliers), `/master-data/customers`
- [x] Employee master listing — uses existing `Employee` model → `hr_employee`, `/master-data/employees`
- [x] Category / Brand / Unit reference views — `ProductCategory`, `Brand`, `Unit` models; `/master-data/categories`, `/master-data/brands`, `/master-data/units`
- [x] Build Eloquent models for each legacy table (no migrations — map to existing schema)
- [x] `AppLayout` nav updated — collapsible "Master Data" group with 7 sub-links; auto-opens when child route is active

---

## SPRINT 5 — Dashboard (Main) Migration ✅
- [x] Catalog every widget/KPI/chart on legacy Dashboard — full audit of DashboardController, 25+ AJAX endpoints mapped
- [x] `DashboardService` — all query logic extracted: KPIs, Section 2 comparisons, sales rank, best sale, commissions (online/Thailand CF/TikTok Live/Teafac/Affiliate), brand sales, shipping, customer counts, new customers, daily + day-of-week charts
- [x] `DashboardController@index` — single Inertia response with all props; date range via `?start=&end=` URL params
- [x] `Pages/Dashboard/Index.vue` — full grid layout: 6 KPI cards, 4-period comparison row, Line + Bar charts (vue-chartjs), sales rank table, customer counts, top 20 best-sale products with progress bars, tabbed commission panel, brand sales bar chart, shipping method table
- [x] Chart.js v4 + vue-chartjs installed; Line (daily trend) + Bar (orders by day/channel) charts wired
- [x] Date-range filter — `<DateRangePicker>` triggers `router.reload()` with `start`/`end` params; all widgets refresh together
- [x] Business rules preserved: marketplace date switch (post-July 2024 → cod_pay_date), retail/wholesale/credit/marketplace segmentation, commission rates (1%/2%), TikTok shop_id/shop_brand filters

---

## SPRINT 6 — Dashboard II Migration ✅
- [x] Catalog widgets unique to Dashboard II — 27 legacy AJAX endpoints audited, 13 sections identified
- [x] `DashboardTwoService` — 15 query methods; `buildRevenueMap()` shared helper merges 6 revenue streams
- [x] `DashboardTwoController` — Inertia index (15 deferred props + 4 eager) + 2 JSON API endpoints
- [x] `Pages/DashboardTwo/Index.vue` — all 13 sections:
      - ยอดขายรายเดือนแยกตามปี (8-series line chart, year selector)
      - จำนวนออเดอร์แยกตามปี (3-series bar chart)
      - สรุปต้นทุนและรายได้ (3-line cost/revenue chart)
      - ยอดขายสินค้าแยกตามหมวดสินค้า (top-5 products line, category selector)
      - แสดงสัดส่วนรายได้จากยอดขาย (donut — retail/wholesale/marketplace)
      - ในและต่างประเทศ (donut — Thailand vs Export, country_id=226)
      - สัดส่วนตามประเภทปี + เดือน (2 category-pie donuts, month selector)
      - สัดส่วน/CS (horizontal stacked bar, per-CS-staff)
      - ข้อมูลยอดขายรายไตรมาส (5×4Q quarterly table)
      - ข้อมูลยอดขายรายเดือน (5×12 year-major matrix)
      - ข้อมูลกำไรขั้นต้นรายเดือน (Finance DB, graceful n/a badge)
      - ข้อมูลกำไรสุทธิรายเดือน (Finance DB, graceful n/a badge)
      - Retail/Wholesale customer tables (API-loaded via separate JSON endpoints)
- [x] 4 selectors: year chips, month `<select>`, category `<select>`, DateRangePicker
- [x] 6 progressive `<Deferred>` blocks — sections reveal independently as props arrive
- [x] Routes: `/dashboard-two/` (Inertia) + `/dashboard-two/retail-customers` + `/dashboard-two/wholesale-customers` (JSON)
- [x] AppLayout nav linked to `/dashboard-two`
- [x] Fixed Inertia `<Deferred>` skeleton bug on Dashboard Main (`:data="[...]"` array binding)
- [ ] Verify numbers vs legacy

---

## SPRINT 7 — Dashboard III Migration [~] In Progress
- [x] Catalog all 5 sub-dashboards from legacy (~40 endpoints audited)
- [x] `DashboardThreeService` — 30+ query methods across all 5 sections
- [x] `DashboardThreeController` — Inertia index + 35 JSON API endpoints
- [x] `Pages/DashboardThree/Index.vue` — 5-tab SPA with lazy loading per tab:
      - **Main**: 6 KPI cards + 2 Doughnut charts (Thai/Export top 10) + 2 tables + paginated full-list modals with search
      - **Behavior**: 4 KPIs + lead source/contact type/buying cycle charts + tier-filtered customer table
      - **Financial**: 4 KPIs + top-profit horizontal bar + margin distribution donut + payment analysis table + top 20 customers
      - **Operation**: 4 KPIs + stage funnel bar + pipeline bar + kanban board (6 stages, add/delete deals) + follow-up table with urgency coloring + add/done/delete + activity timeline
      - **Product**: 4 KPIs + top 15 products bar + category margin bar + slow movers table + health scores table + competitive refs CRUD + product feedback CRUD
- [x] All CRUD modals: follow-up, pipeline deal, competitive ref, product feedback
- [x] Routes: `/dashboard-three/` (Inertia) + 35 API endpoints across 5 sub-paths
- [x] AppLayout nav linked to `/dashboard-three`
- [ ] Verify numbers vs legacy

---

## SPRINT 8 — Sale Module Migration 🔥 Priority
The largest transactional module — break into sub-sprints if needed.
- [ ] Sale list view (paginated `<DataTable>`, filters: date range, branch, status)
- [ ] Sale detail / show
- [ ] Sale create / edit (line items, discounts, taxes, payment terms)
- [ ] Sale invoice PDF (DomPDF) — preserve legacy template visually
- [ ] Sale Excel export (PhpSpreadsheet)
- [ ] Sale barcode/QR (milon/barcode)
- [ ] Sale status transitions (draft → confirmed → paid → cancelled) — preserve legacy state machine
- [ ] Permissions wired (`sale.view`, `sale.create`, `sale.edit`, `sale.delete`, `sale.export`)

---

## SPRINT 9 — Customer & CS Modules
- [ ] Customer list + detail (`CustomerController`, `CustomerNewController`, `CustomerSaleController`)
- [ ] Customer export (`CustomerExportController`) — Excel
- [ ] CS module (`CSController`, `CsVipOverduePageController`)
- [ ] Birthday module (`BirthdayController`)
- [ ] Award / commission modules (`AwardController`, `CommissionController`)

---

## SPRINT 10 — Reporting & Analytics
- [ ] Best sale breakdown (`BestSaleBreakdownController`)
- [ ] Buy range / market analysis (`BuyRangeController`, `MarketAnalysisController`)
- [ ] Behavior / financial / operation / MIS dashboards
- [ ] Excel/PDF export pipeline standardized

---

## SPRINT 11 — Integrations Layer
- [ ] LINE Bot SDK migration (`LineController`, `LineRichMenuController`)
- [ ] Facebook Graph + Socialite (`FacebookController`)
- [ ] Pusher realtime channels (evaluate Laravel Reverb as drop-in)
- [ ] FFmpeg / video processing pipeline
- [ ] Kerry / Best Express shipping APIs (`KerryController`, `BestExpressController`)
- [ ] Webhook endpoints under `routes/api.php`

---

## SPRINT 12 — Notifications & Logging
- [ ] Notifications system (`NotificationController` — email/LINE/in-app)
- [ ] AllLogs / audit trail (`AllLogsController`)
- [ ] Activity log via `spatie/laravel-activitylog` (optional)

---

## SPRINT 13 — Hardening & Cutover
- [ ] Full regression test pass against legacy (parallel-run)
- [ ] Performance audit (N+1 queries, eager loading, caching)
- [ ] Security review (CSRF, XSS, mass assignment, permission gates)
- [ ] User training material / changelog
- [ ] Production deploy plan (zero-downtime; DB-shared so rollback = repoint URL)
- [ ] Cutover & legacy decommission plan

---

## Conventions
- **Controllers**: thin. Move business logic into `app/Services/*`.
- **Validation**: Form Request classes (`app/Http/Requests/*`).
- **Authorization**: Spatie `permission:*` middleware on routes + Policy classes for record-level.
- **Inertia props**: pass minimal, denormalized data. Use Eloquent API Resources (`app/Http/Resources/*`) for shaping.
- **Vue**: Composition API + `<script setup>`. No Options API.
- **Naming**: `PascalCase` for components/pages, `kebab-case` for routes & URL params.
- **Commits**: Conventional Commits (`feat:`, `fix:`, `refactor:`, `chore:`).

---

## How to use this file
Tick boxes as work completes. Each sprint must end with a working, demoable feature before the next begins.
