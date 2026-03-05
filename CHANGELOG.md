# Changelog

## [Unreleased] — March 2026

### Backend

#### Bug Fixes
- Fixed `strftime` SQL incompatibility in the reports query (MySQL does not support `strftime`; replaced with `DATE_FORMAT`)
- Fixed `RequisitionFor` enum mismatch between form values and model casting
- Fixed validation inconsistency where amount validation differed between create and update paths
- Fixed API store skipping `DRAFT` status when `save_as_draft` param was passed

#### New Features
- **Basic Requisition flag** — Added `is_basic_requisition` boolean column to `cash_requisitions` table; surfaced in Filament form and API
- **Comment thread system** — Added `requisition_comments` table and `RequisitionComment` model; full CRUD via API (`GET/POST /requisitions/{id}/comments`)
- **Attachment upload notification** — Admin now receives an in-app notification when a user uploads a supporting document
- **Multi-currency StatsOverview** — Fixed hardcoded `$` symbol in `StatsOverview` widget; now respects the branch currency (ZAR, ZMW, SZL, USD)

#### Enums Updated
- `RequisitionCategory` — Replaced old categories with: `Fuel`, `Airtime`, `Materials`, `Travel`, `Procurement`, `Operations`, `Office Supplies`, `IT & Software`, `Marketing`, `Training`, `Fleet & Transport`, `Emergency`, `Other`

#### Cleanup
- Removed dead `RequisitionType` enum file
- Removed orphaned leave-related migration

---

### Frontend (React / Next.js)

#### All Sections Wired to Real API (mock data removed)

- **`overview.tsx`** — Fetches live dashboard stats (`getDashboard`) and recent requisitions (`getRequisitions`); displays total requisitions, pending approvals, avg turnaround hours, unread notifications
- **`requisitions.tsx`** — Fetches real requisition list with search, status filter, and pagination
- **`requisition-form.tsx`** — Multi-step form (4 steps) now calls `createRequisition` API; supports **Save as Draft** and **Submit**; file attachments uploaded via `uploadAttachment`
- **`approvals.tsx`** — Fetches pending requisitions (`submitted` + `stage1_approved`); approve/deny/modify actions call real API with comment input and confirmation dialog
- **`reports.tsx`** — Fetches live analytics from `getReports`; renders spend by branch (bar chart), by category (donut chart), and requisitions over time (line chart); stat cards show real totals and approval rate
- **`audit.tsx`** — Fetches paginated audit events from `getAuditLog`; color-coded action labels; pagination controls

#### New Components

- **`notifications.tsx`** — Notification center with paginated list, unread/read badge, mark-individual-read, and mark-all-read via API
- **`faq.tsx`** — Help & FAQ section with 12 Q&A items across categories: Getting Started, Approvals, Attachments, Workflow, Reports, General; filterable by category

#### Navigation

- Added **Notifications** (`Bell` icon) to sidebar nav
- Added **Help & FAQ** (`HelpCircle` icon) to sidebar nav
- Added `"notifications"` and `"faq"` to the `Section` type and render switch in `page.tsx`

#### API Client (`lib/api.ts`) — Functions Added

| Function | Endpoint |
|---|---|
| `getComments` | `GET /requisitions/{id}/comments` |
| `addComment` | `POST /requisitions/{id}/comments` |
| `submitDraft` | `POST /requisitions/{id}/submit` |
| `getNotifications` | `GET /notifications` |
| `markNotificationRead` | `POST /notifications/{id}/read` |
| `markAllNotificationsRead` | `POST /notifications/read-all` |
| `getAuditLog` | `GET /audit` |
| `getReports` | `GET /reports` |

#### Cleanup
- Deleted unused `metric-card.tsx` (hardcoded mock data, no longer imported)
- Deleted unused `recent-requisitions.tsx` (hardcoded mock data, no longer imported)
- Deleted unused `charts/status-chart.tsx` (hardcoded mock data, no longer imported)
- Removed empty `charts/` directory

---

### Documentation

- **README** — Complete rewrite:
  - Dual tech stack (Backend + Frontend sections)
  - Separate backend and frontend setup instructions
  - Full API endpoint reference table (24 endpoints)
  - Updated project structure tree reflecting all new files and removed files
  - Requisition categories list
  - Branch/currency table retained
