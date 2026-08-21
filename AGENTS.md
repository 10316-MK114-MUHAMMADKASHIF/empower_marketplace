# Empower Marketplace — Agent Instructions

> General Laravel, PHP, Pint, PHPUnit, and Artisan conventions are in [CLAUDE.md](CLAUDE.md) (auto-loaded by Laravel Boost). This file covers **project-specific** context only.

## What This App Does

Empower Marketplace is a healthcare compliance services portal. Clients (healthcare practices) buy compliance packages, upload intake questionnaire files, an AI (Claude API) extracts the data from those files, and the system generates password-protected compliance PDFs and Word documents.

## Tech Stack

| Concern        | Package / Tools                                                           |
| -------------- | ------------------------------------------------------------------------- |
| Framework      | Laravel 13, PHP 8.3+                                                      |
| Reactive UI    | Livewire v4                                                               |
| Styling        | Tailwind CSS v4 (`@tailwindcss/vite`)                                     |
| Font           | Instrument Sans via Bunny Fonts (configured in `vite.config.js`)          |
| PDF generation | `tecnickcom/tcpdf` — AES-256, password-protected, read-only               |
| Word documents | `phpoffice/phpword` — fills `.docx` templates in `storage/app/templates/` |
| AI extraction  | Anthropic Claude API via Laravel `Http` facade (no SDK package)           |
| Queue          | Database queue driver (sync in tests — see `phpunit.xml`)                 |
| Testing        | PHPUnit with SQLite in-memory (see `phpunit.xml`)                         |

Before using any package API, confirm its installed version: `composer show <vendor/package>`.

## User Roles

- `client` — default role on registration; accesses `/portal`
- `admin` — accesses `/admin`; approves/rejects submissions and manages documents

Role is stored on the `users` table as a string enum column.

## Key Directories (once built)

```
app/
  Livewire/              # All Livewire components
    Portal/              # Multi-step portal wizard (StepPayment, StepPracticeProfile, etc.)
    Admin/               # Admin panel components (SubmissionList, SubmissionDetail, etc.)
  Jobs/                  # ProcessIntakeUpload, GenerateComplianceDocument
  Models/                # Eloquent models
  Enums/                 # PHP 8.1+ backed enums for status values
resources/views/
  layouts/               # app.blade.php, admin.blade.php, guest.blade.php
  livewire/              # Component views (mirrors app/Livewire/ structure)
  documents/             # Blade templates rendered to PDF (one per document type)
storage/app/
  private/compliance/    # Generated PDFs and .docx files — NEVER in public/
  templates/             # Source .docx template files for PhpWord to fill
```

## Portal Workflow (5 steps)

1. **Payment** — Single package selection only (no cart/multi-package checkout — a client picks one package via `?package=slug` from the pricing page's "Select Package" link, or a default is chosen for them); simulated only, marks `orders.payment_status = simulated_paid`
2. **Practice Profile** — Locks after submission (`practices.is_profile_locked = true`); includes OSHA locations CRUD
3. **Intake Upload** — PDF/image/docx uploads + handbook questionnaire; dispatches `ProcessIntakeUpload` jobs
4. **Review Status** — Polls `intake_submissions.status`; admin must approve before docs release
5. **Dashboard** — History, payments, documents (download/preview); stale badge when profile changes post-approval

## AI Pipeline

`ProcessIntakeUpload` job (queued):

- PDF/image: base64-encode → Claude API with vision prompt → structured JSON response
- `.docx`: extract text via PhpWord → send text to Claude
- Stores result in `intake_uploads.ai_extracted_data` (JSON)
- When all uploads for a submission complete → dispatches `GenerateComplianceDocument` per doc type

`GenerateComplianceDocument` job (queued):

- Merges practice profile + OSHA location + handbook answers + AI-extracted data
- Renders `resources/views/documents/{type}.blade.php`
- TCPDF renders to PDF: `SetProtection(['print'], '', $ownerPassword, 128)` — read-only, empty user password
- PhpWord fills `storage/app/templates/{type}.docx` → saves `.docx` copy
- Stored at `storage/app/private/compliance/{order_id}/{document_type}.{ext}`

Claude API call pattern (no SDK — use Http facade):

```php
Http::withToken(config('services.anthropic.key'))
    ->withHeaders(['anthropic-version' => '2023-06-01'])
    ->post('https://api.anthropic.com/v1/messages', [...]);
```

Config key: `services.anthropic.key` → env `ANTHROPIC_API_KEY`.

## Document Types by Package Tier

| Tier slug      | Documents included                                                                    |
| -------------- | ------------------------------------------------------------------------------------- |
| `essential`    | `employee_handbook_basic`, `osha_safety_plan`                                         |
| `professional` | `employee_handbook_full`, `osha_safety_plan`, `hr_policy_manual`                      |
| `advanced`     | all professional + `hipaa_privacy_policy`, `osha_location_report` (per OSHA location) |
| `complete`     | all advanced + `custom_compliance_document`                                           |

## Security Rules (Non-Negotiable)

- All uploaded files and generated documents go to `storage/app/private/` — **never** `storage/app/public/`
- Documents are served only via a signed, authenticated download route that verifies the requesting user owns the order
- Admin routes are protected by `AdminMiddleware` that checks `auth()->user()->role === 'admin'`
- The `ANTHROPIC_API_KEY` must only come from environment config — never hard-coded

## Build & Dev Commands

```bash
composer run dev          # Start Laravel dev server + Vite together
npm run build             # Build assets (run after frontend changes if not on dev server)
php artisan test --compact                          # All tests
php artisan test --compact --filter=testName        # Single test
vendor/bin/pint --dirty --format agent              # Format modified PHP files (always run before finishing)
```

## Livewire V4 Conventions

- Use `#[Validate]` attribute on properties instead of `protected $rules`
- Use `#[On('event-name')]` attribute instead of `protected $listeners`
- Use `#[Computed]` attribute for computed properties (cached per request)
- Extend `Livewire\Form` for complex form objects (e.g., `PracticeProfileForm`)
- Use `wire:navigate` on `<a>` tags for SPA-like navigation between portal steps
- Modal pattern: use a nested Livewire component with `#[On]` to open/close — do not use Alpine alone for modal state that involves server data
- Polling: `wire:poll.5s` for the Review Status step (admin approval check)

## Status Enums

Use PHP 8.1 backed enums in `app/Enums/` for all `status` columns. Example pattern from this app:

```php
enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    // ...
}
```

Use TitleCase enum keys (e.g., `PendingPayment`, not `PENDING_PAYMENT`).

## Factory Conventions

Use `fake()` helper (not `$this->faker`). See `database/factories/UserFactory.php` for the established pattern.

## Testing Conventions

- Tests use SQLite in-memory (`DB_DATABASE=:memory:` in `phpunit.xml`) — no real DB needed
- Queue runs synchronously in tests (`QUEUE_CONNECTION=sync`) — jobs execute inline
- Mock Claude API calls with `Http::fake()` in tests — never make real API calls in tests
- Mock TCPDF/PhpWord in job tests using `$this->mock()` to avoid file I/O
- Feature tests cover: registration, simulated payment, intake upload, job dispatch, admin approve/reject, signed download auth check
