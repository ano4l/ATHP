# AceTech Internal Portal

Internal employee portal for AceTech — cash requisitions, reporting, and notifications.

## Tech Stack

### Backend
- **PHP 8.4** with **Laravel 10**
- **Filament 3** — admin panel UI (TALL stack: Tailwind, Alpine.js, Laravel, Livewire)
- **MySQL** — database
- **Laravel Sanctum** — API token authentication

### Frontend (React SPA)
- **Next.js 14** — React framework
- **TypeScript** — type safety
- **Tailwind CSS** — styling
- **Recharts** — reporting charts
- **Lucide React** — icons

## Features

- **Dual Interface** — Filament admin panel (`/admin`) + React SPA frontend
- **Authentication** — Filament login + Sanctum API token auth for the React frontend
- **Role-based access** — Admin sees all records, Employee sees own records only
- **Dashboard** — live metrics (total requisitions, pending approvals, avg turnaround, unread notifications), recent requisitions
- **E-Requisitions (Cash)** — full lifecycle from draft to closure:
  - Draft → Submitted
  - Stage 1 approval → Final approval (for high-value requests above configurable threshold)
  - Modification requests and denials with comments
  - Finance/admin processing (processing, outstanding, paid/disbursed)
  - Fulfilment tracking, requester confirmation, and closure
  - **Basic Requisition** flag for multiple-quote comparison
- **Multi-step Requisition Form** — 4-step wizard (Project Details → Amount & Details → Attachments → Review) with Save Draft and Submit
- **Comment Thread** — per-requisition threaded comments for approver/requester communication
- **Workflow governance controls** — duplicate guard on submit and configurable stage-2 threshold
- **Supporting Documents** — private file uploads (max 10MB) with secure authenticated download endpoint, admin notification on upload
- **Reports & Analytics** — spend by branch/category, requisition trends over time, approval rate, all fetched from the API
- **Notifications** — in-app notifications at key workflow stages (submission, approval, denial, modification, attachment upload), mark read/all-read
- **Audit Trail** — all significant requisition actions logged to `audit_events` with pagination
- **Help & FAQ** — in-app guidance covering workflow, attachments, approvals, and system usage

### Requisition Categories

Fuel, Airtime, Materials, Travel, Procurement, Operations, Office Supplies, IT & Software, Marketing, Training, Fleet & Transport, Emergency, Other

## Getting Started

### Prerequisites

- PHP 8.2+ (8.4 recommended)
- Composer
- MySQL server
- Node.js 18+ (for frontend)

### Backend Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy environment file
cp .env.example .env

# 3. Generate app key
php artisan key:generate

# 4. Configure database in .env
# DB_DATABASE=acetech_portal
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 5. Create MySQL database
mysql -u root -p -e "CREATE DATABASE acetech_portal;"

# 6. Run migrations
php artisan migrate

# 7. Seed demo data
php artisan db:seed

# 8. Start the API/admin server
php artisan serve --port=8000
```

Filament admin panel: **http://localhost:8000/admin**

### Frontend Setup

```bash
cd e-requisition-ui

# 1. Install dependencies
npm install

# 2. Create .env.local
echo "NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api" > .env.local

# 3. Start the dev server
npm run dev
```

React frontend: **http://localhost:3000**

### Requisition Workflow Configuration

These environment variables tune the E-Requisition flow:

```env
REQUISITION_STAGE2_THRESHOLD=10000
REQUISITION_DUPLICATE_LOOKBACK_DAYS=30
REQUISITION_ATTACHMENT_MAX_MB=10
```

### Demo Users

| Role     | Email                | Password     |
|----------|----------------------|--------------|
| Admin    | admin@acetech.com    | admin123     |
| Employee | employee@acetech.com | employee123  |

## Project Structure

```
ATHP/
├── app/
│   ├── Enums/
│   │   ├── UserRole.php              # admin, employee
│   │   ├── Branch.php                # south_africa, zambia, eswatini, zimbabwe
│   │   ├── RequisitionFor.php        # internal, client, project
│   │   ├── RequisitionStatus.php     # draft → submitted → stage1_approved → approved → processing → paid → fulfilled → closed
│   │   ├── RequisitionCategory.php   # fuel, airtime, materials, travel, procurement, operations, etc.
│   │   ├── PaymentMethod.php         # cash, bank_transfer, mobile_money, card, eft, other
│   │   ├── PurchaseStatus.php
│   │   └── DeliveryStatus.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── CashRequisition.php       # includes is_basic_requisition flag
│   │   ├── CashRequisitionAttachment.php
│   │   ├── RequisitionComment.php    # threaded comments per requisition
│   │   ├── AuditEvent.php
│   │   └── Notification.php
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php        # login, logout, me
│   │   ├── DashboardController.php   # dashboard stats, reports
│   │   └── RequisitionController.php # CRUD, approve, deny, modify, process, fulfil, close, comments, attachments
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── CashRequisitionResource.php   # Full workflow actions + infolists
│   │   │   ├── NotificationResource.php      # List + mark read
│   │   │   └── AuditEventResource.php        # Audit trail listing (admin only)
│   │   ├── Pages/
│   │   │   ├── Registration.php              # Custom registration with branch field
│   │   │   └── Reports.php                   # Admin workflow analytics + CSV export
│   │   └── Widgets/
│   │       ├── StatsOverview.php             # Dashboard stats (multi-currency aware)
│   │       └── LatestRequisitions.php        # Recent requisitions table
│   └── Providers/
│       └── Filament/AdminPanelProvider.php
├── config/
│   └── requisition.php               # workflow thresholds, attachment config, required categories
├── database/
│   ├── migrations/                    # All table schemas including basic_requisition + comments
│   └── seeders/DatabaseSeeder.php
├── routes/
│   ├── web.php                        # Secure attachment download route
│   └── api.php                        # Sanctum-protected API routes
│
└── e-requisition-ui/                  # React/Next.js frontend
    ├── app/page.tsx                   # Main dashboard with section routing
    ├── lib/api.ts                     # API client (auth, requisitions, notifications, audit, reports, comments)
    └── components/dashboard/
        ├── sidebar.tsx                # Navigation sidebar
        ├── header.tsx                 # Top header bar
        ├── modals/
        │   └── requisition-form.tsx   # Multi-step requisition form with draft support
        └── sections/
            ├── overview.tsx           # Dashboard overview (live API data)
            ├── requisitions.tsx       # Requisition list with search, filter, pagination
            ├── approvals.tsx          # Pending approvals with approve/deny/modify actions
            ├── reports.tsx            # Analytics charts (branch, category, trend)
            ├── audit.tsx              # Audit trail with pagination
            ├── notifications.tsx      # Notification center with mark-read
            ├── faq.tsx                # Help & FAQ guidance
            └── settings.tsx           # User settings
```

## API Endpoints

All API routes are prefixed with `/api` and protected by `auth:sanctum` (except login).

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | Authenticate and receive token |
| GET | `/me` | Get current user |
| POST | `/logout` | Invalidate token |
| GET | `/dashboard` | Dashboard metrics |
| GET | `/requisitions` | List requisitions (filterable by `status`, paginated) |
| POST | `/requisitions` | Create requisition (supports `save_as_draft` param) |
| GET | `/requisitions/{id}` | View single requisition |
| POST | `/requisitions/{id}/approve` | Approve requisition |
| POST | `/requisitions/{id}/deny` | Deny requisition (comment required) |
| POST | `/requisitions/{id}/modify` | Request modification |
| POST | `/requisitions/{id}/process` | Mark as processing with payment details |
| POST | `/requisitions/{id}/fulfil` | Mark as fulfilled |
| POST | `/requisitions/{id}/close` | Close requisition |
| POST | `/requisitions/{id}/attachments` | Upload attachment |
| POST | `/requisitions/{id}/submit` | Submit a draft |
| GET | `/requisitions/{id}/comments` | List comments |
| POST | `/requisitions/{id}/comments` | Add comment |
| GET | `/notifications` | List notifications (paginated) |
| POST | `/notifications/{id}/read` | Mark notification read |
| POST | `/notifications/read-all` | Mark all read |
| GET | `/audit` | Audit log (paginated) |
| GET | `/reports` | Reports data |

## Branches & Currencies

| Branch       | Currency |
|--------------|----------|
| South Africa | ZAR      |
| Zambia       | ZMW      |
| Eswatini     | SZL      |
| Zimbabwe     | USD      |

## Email (Optional)

Configure SMTP in `.env`:

```
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=your_api_key
MAIL_FROM_ADDRESS=notifications@acetech.co.za
```

The app works without email — notifications are in-app only by default.
