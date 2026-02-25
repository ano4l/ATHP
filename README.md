# AceTech Internal Portal

Internal employee portal for AceTech — cash requisitions, leave management, reporting, and notifications.

## Tech Stack

- **PHP 8.2+** with **Laravel 11**
- **Filament 3** — admin panel UI (TALL stack: Tailwind, Alpine.js, Laravel, Livewire)
- **MySQL** — database
- **Chart.js** — reporting charts
- **Laravel Mail** — email notifications (optional)

## Features

- **Authentication** — Filament login + registration with branch selection
- **Role-based access** — Admin sees all records, Employee sees own records only
- **Dashboard** — stats widgets, latest requisitions & leave tables
- **E-Requisitions (Cash + Purchase)** — full lifecycle from draft to closure:
  - Draft → Submitted
  - Stage 1 approval → Final approval (for high-value requests)
  - Modification requests and denials with comments
  - Finance/admin processing (processing, outstanding, paid/disbursed)
  - Fulfilment tracking, requester confirmation, and closure
- **Workflow governance controls** — duplicate guard on submit and configurable stage-2 threshold
- **Supporting Documents** — private file uploads with secure authenticated download endpoint
- **Leave Management** — submit with reason, date range, auto business-day calculation; admin approve/deny
- **Reports** (Admin only) — workflow KPIs, status breakdown, by-branch/by-type/by-category charts, trend analytics, CSV export
- **Notifications** — in-app notifications for submissions/approvals/denials, mark read/all-read
- **Audit Trail** — significant requisition and leave actions logged to `audit_events` and viewable in admin panel

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- MySQL server
- Node.js (for Filament assets)

### Setup

```bash
# 1. Install dependencies
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

# 8. Start the dev server
php artisan serve
```

Open **http://localhost:8000/admin**

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
app/
├── Enums/
│   ├── UserRole.php          # admin, employee
│   ├── Branch.php            # south_africa, zambia, eswatini, zimbabwe
│   ├── RequisitionFor.php    # client, order, self
│   ├── RequisitionStatus.php # draft → stage approvals → processing → paid/fulfilled/closed
│   ├── RequisitionType.php   # cash, purchase
│   ├── RequisitionCategory.php
│   ├── PaymentMethod.php
│   ├── PurchaseStatus.php
│   ├── DeliveryStatus.php
│   ├── LeaveReason.php       # annual, sick, family_responsibility, study, unpaid, other
│   └── LeaveStatus.php       # submitted, approved, denied
├── Models/
│   ├── User.php
│   ├── CashRequisition.php
│   ├── CashRequisitionAttachment.php
│   ├── LeaveRequest.php
│   ├── AuditEvent.php
│   └── Notification.php
├── Filament/
│   ├── Resources/
│   │   ├── CashRequisitionResource.php   # Full requisition workflow actions + infolists
│   │   ├── LeaveRequestResource.php      # CRUD + approve/deny actions
│   │   ├── NotificationResource.php      # List + mark read
│   │   └── AuditEventResource.php        # Audit trail listing (admin only)
│   ├── Pages/
│   │   ├── Registration.php              # Custom registration with branch field
│   │   └── Reports.php                   # Admin workflow analytics + CSV export
│   └── Widgets/
│       ├── StatsOverview.php             # Dashboard workflow stat cards
│       ├── LatestRequisitions.php        # Recent requisitions table
│       └── LatestLeaves.php              # Recent leaves t
└── Providers/
    ├── AppServiceProvider.php
    └── Filament/
        └── AdminPanelProvider.php        # Panel config, colors, middleware

database/
├── migrations/                           # All table schemas
└── seeders/
    └── DatabaseSeeder.php                # Admin + employee + sample data

resources/views/filament/pages/
    └── reports.blade.php                 # Reports dashboard with status + category charts

routes/
└── web.php                               # Includes secure attachment download route
```

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
