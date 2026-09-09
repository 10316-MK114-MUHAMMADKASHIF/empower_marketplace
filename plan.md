Plan: Empower Marketplace — Laravel + Livewire
TL;DR — Build a compliance services portal where healthcare practices buy packages, upload questionnaire files, an AI (Claude) extracts the data, and the system generates password-protected compliance PDFs. The plan covers a 7-phase build from auth through admin panel.

Decisions Locked In
Payment: Simulated only (no gateway)
Documents: Claude API parses uploads → Blade templates → TCPDF (password-protected read-only PDF) + PhpWord (.docx copy)
Auth: Email/password, no email verification
Admin: Approve/reject submissions, manage documents & leads
Phase 1 — Foundation
Steps (can run in parallel where noted):

Install livewire/livewire ^3 via Composer
Install tecnickcom/tcpdf, phpoffice/phpword, barryvdh/laravel-dompdf via Composer
Add role enum column (client|admin) to users table migration; update User model
Create AdminMiddleware — redirects non-admins; register in app.php
Create Blade layouts: layouts/app.blade.php (portal shell with nav/user dropdown) and layouts/admin.blade.php — parallel with step 6
Create Blade layouts: layouts/guest.blade.php (public pages, navbar matching prototype) — parallel with step 5
Relevant files: User.php, 0001_01_01_000000_create_users_table.php, app.php

Phase 2 — Database Schema
Steps: 7. Create migrations for all tables (each its own migration file):

practices
osha_locations
packages
orders
intake_submissions
intake_uploads
generated_documents
activity_logs
leads
Create Eloquent models for each table with relationships, factories, and casts
Seed packages table with the 4 tiers (Essential/Professional/Advanced/Complete) including pricing, features JSON, and included_document_types JSON
Key DB Fields:

Table Notable columns
practices user_id, npi_number, specialty, billable_providers_count, is_profile_locked
osha_locations practice_id, uses_hazardous_drugs, offers_hep_b_vaccination, offers_tb_screening, employees_per_year
packages slug, monthly_price, annual_price, billing_type (enum), features (JSON), included_document_types (JSON)
orders status (enum: pending_payment→paid→intake_submitted→under_review→approved→completed), payment_status, paid_at
intake_submissions order_id (unique), status (enum), handbook_answers (JSON), reviewed_by, reviewer_notes
intake_uploads upload_type (enum: practice_intake|osha_questionnaire), ai_extraction_status (enum), ai_extracted_data (JSON)
generated_documents document_type, status (enum), pdf_storage_path, docx_storage_path, is_stale
activity_logs user_id, order_id, event_type, description, metadata (JSON)
leads name, email, phone, message, package_interest
Phase 3 — Public Pages
Steps (parallel): 10. Convert index.html → welcome.blade.php using Tailwind v4, preserving all sections: hero, stats strip, services grid, pricing cards (linking to /register?package=X), process timeline, contact CTA, footer 11. Create ContactForm Livewire component → resources/views/contact.blade.php — validates name/email/phone/message, creates Lead record, shows confirmation panel

Phase 4 — Authentication
Steps: 12. Create LoginForm Livewire component — email/password, Auth::attempt, redirect to /portal 13. Create RegisterForm Livewire component — name/email/password/confirm, creates User with role=client, creates associated Practice record, redirects to /portal 14. Add logout route + action 15. Route group: Route::middleware('guest') for login/register, Route::middleware('auth') for portal

Phase 5 — Client Portal Wizard
The portal is a single-page Livewire component (Portal) that tracks $currentStep and $orderId in the session.

Steps: 16. Create Portal parent component with stepper UI (5 steps with progress bar), step-gating logic (can't advance without prerequisites met) 17. Step 1 — Payment: StepPayment — displays selected package summary, simulated card form fields (non-functional), "Pay Now" triggers spinner → marks order.payment_status = simulated_paid, order.status = paid 18. Step 2 — Practice Profile: StepPracticeProfile — practice name/logo/address/NPI/specialty/providers form (locks on submission); OshaLocationModal Livewire modal for add/edit/remove OSHA locations with the full 12-field location form 19. Step 3 — Intake Upload: StepIntakeUpload — drag-drop file upload for practice intake form + OSHA questionnaire (validate type/size); tier-gated handbook questionnaire accordion with all 42 fields across 7 sections; on submit creates IntakeSubmission, dispatches ProcessIntakeUpload jobs 20. Step 4 — Review Status: StepReviewStatus — polls intake_submission.status every 10 seconds via Livewire polling, shows timeline (Submitted → Under Review → Approved/Rejected), displays reviewer notes if rejected 21. Step 5 — Dashboard: StepDashboard — three tabs: History (activity log timeline), Payments (orders table + "Add Package" cards), Documents (list with per-doc download / preview actions, stale badges); DocumentPreviewModal for in-browser PDF preview

Phase 6 — AI Pipeline & Document Generation
Steps: 22. Create ProcessIntakeUpload queued job:

- Fetches file from private storage
- PDF/image files: base64-encode → Claude API (claude-3-5-sonnet) with vision prompt instructing structured JSON extraction keyed to template variables
- .docx files: extract text via PhpWord → send as text to Claude
- Stores parsed JSON in intake_uploads.ai_extracted_data
- When all uploads for a submission are completed, dispatches GenerateComplianceDocument per doc type

23. Create GenerateComplianceDocument queued job:

- Merges: practice profile + OSHA location data + handbook_answers + all ai_extracted_data from uploads
- Renders corresponding Blade template (resources/views/documents/{type}.blade.php) with merged data
- TCPDF renders HTML → PDF with AES-256 encryption: no copy/edit/print-high-res, empty user password (open to read), system owner password
- PhpWord fills corresponding .docx template (storage/app/templates/{type}.docx) → stores .docx copy
- Both files stored under storage/app/private/compliance/{order_id}/
- Updates generated_documents record, dispatches MarkDocumentStale when practice/location profile changes

24. Create storage/app/templates/ directory with placeholder .docx templates for each document type
25. Configure .env with ANTHROPIC_API_KEY and queue driver database; create jobs table migration (already exists in base Laravel)

Document types by tier:

Tier Documents
Essential Employee Handbook (basic), OSHA Safety Plan
Professional Employee Handbook (full), OSHA Safety Plan, HR Policy Manual
Advanced All Professional + HIPAA Privacy Policy + OSHA Location Report (one per OSHA location)
Complete All Advanced + Custom Compliance Document
Phase 7 — Admin Panel
Steps: 26. Create Admin/SubmissionList Livewire component — table of all IntakeSubmission records with status filter chips, search, pagination 27. Create Admin/SubmissionDetail Livewire component — view practice profile, uploaded files (download links), approve button (sets status, dispatches generation jobs) / reject button with notes textarea 28. Create Admin/DocumentList Livewire component — view all generated documents per order, "Regenerate" action re-dispatches job, stale badge indicator 29. Create Admin/LeadList Livewire component — paginated table of Lead records with name/email/phone/package/date

Phase 8 — Polish & Optimisations
Steps (parallel): 30. Activity logging — ActivityLog service class called at every key state change (payment, upload, approval, doc generation, download) 31. Signed download route — GET /documents/{document}/download uses Storage::temporaryUrl() or a signed route; validates the authenticated user owns the document 32. Stale document detection — Practice and OshaLocation updated model events dispatch MarkAllRelatedDocumentsStale job; dashboard shows stale badge + "Regenerate" CTA 33. Queue monitoring — add horizon or use php artisan queue:work with supervisor config note 34. Tests — feature tests for: registration flow, payment simulation, intake upload, AI job (mocked), document generation (mocked TCPDF), admin approve/reject, signed download auth

Relevant Files
File Role
User.php Extend with role field + relationships
web.php All routes to be defined here
welcome.blade.php Replace with Tailwind landing page
migrations All new migrations live here
app/Livewire/Portal.php Main wizard component (to create)
app/Jobs/ProcessIntakeUpload.php Claude API integration (to create)
app/Jobs/GenerateComplianceDocument.php TCPDF + PhpWord generation (to create)
resources/views/documents/ Blade templates per document type (to create)
storage/app/templates/ .docx template files (to create)
Verification
php artisan test --compact — all tests green after each phase
Register as client → select package → complete payment simulation → profile saved → upload files → job dispatches correctly (check jobs table)
Manually approve submission in admin → documents generate → download returns password-protected PDF
PDF is read-only: verify cannot copy text or print in Adobe Reader
Stale badge appears on dashboard after editing practice profile post-approval
Admin middleware blocks client role from /admin/\* routes
Signed download route returns 403 if accessed by a different user
Further Considerations
Claude rate limits — For high volume, wrap the Claude HTTP call in a retry() with exponential backoff; consider ProcessIntakeUpload job $tries = 3.
File security — All uploads must go to private (never public); only served via the signed download route after auth check.
.docx template maintenance — The .docx templates in storage/app/templates/ will need to be created/maintained manually by admin staff as the content evolves; a future admin UI for template uploads could be added.

---

# Phase 2: Free trial checkout + automatic post-trial billing

**Status (2026-09-09): Removed from active code, deferred to Phase 2.** The free-trial checkout
flow (payFreeTrial/confirmTrialContinuation/cancelTrialSubscription, the trial emails, the daily
lifecycle command, and the direct Clover eCommerce service) was built and fully tested, but has
been stripped out of `⚡portal.blade.php` and the app pending a decision on which payment gateway
can actually support it — see "What we learned" below. `applyDiscountCode()` rejects a
`DiscountType::FreeTrial` code with "Free trial checkout is not available yet."

## What's still in place (deliberately, so Phase 2 doesn't redo migrations)

- `orders` table columns (migration `2026_09_04_065220_add_trial_fields_to_orders_table.php`):
  `trial_ends_at`, `trial_confirmed_at`, `trial_reminder_sent_at`, `clover_card_token`.
- `PaymentStatus::Trialing` enum case, included in `Order::PAID_STATUSES`.
- `Order::blockedFromAiGeneration()` — true when `status === Cancelled && payment_status ===
  Trialing`. Still wired into guards in `submitIntake()`, `submitForReview()`, and
  `regenerateDocument()` in `⚡portal.blade.php`, and still covered by
  `test_a_client_with_a_cancelled_trial_cannot_submit_intake` /
  `..._cannot_regenerate_a_document` in `PortalTest.php`. The dashboard still shows a banner via
  `@if($this->currentOrder?->blockedFromAiGeneration())` if any such order exists.
- `OrderFactory::trialing()` / `trialCancelled()` factory states (used by the tests above).
- `DiscountType::FreeTrial` enum case and the admin discount-code form's free-trial fields
  (`trial_days`, auto-filled valid-from/until) — a separate, already-shipped admin feature;
  admins can still create Free Trial discount codes, they just can't be applied at checkout yet.

## What was removed (2026-09-09)

- `app/Services/CloverEcommerceService.php`, `CloverCustomerResult.php`
- `app/Mail/ClientTrialStartedMail.php`, `ClientTrialEndingReminderMail.php`,
  `ClientTrialCancelledMail.php` + their Blade views under `resources/views/emails/client/`
- `app/Console/Commands/ProcessTrialLifecycle.php` (the `trials:process` daily command) and its
  scheduling line in `routes/console.php`
- `tests/Feature/TrialLifecycleTest.php`
- `config/services.php`'s `clover_ecomm` block and the `CLOVER_ECOMM_*` env vars
- From `⚡portal.blade.php`: `cloverToken` property, `isFreeTrialCheckout` computed,
  `validateBillingAndTrialFields()`, `payFreeTrial()`, `confirmTrialContinuation()`,
  `cancelTrialSubscription()`, the Clover.js RSA tokenization JS block, and the dashboard's
  active-trial banner (Proceed with Payment / Cancel Subscription UI)
- The corresponding free-trial-checkout tests in `PortalTest.php` (applying a code showed no
  error, checkout created a Trialing order, decline handling, confirm/cancel continuation) —
  replaced with one test asserting the code is now rejected, plus the two
  `blockedFromAiGeneration()` tests kept as-is.

## What we learned: MTBC's Clover gateway cannot do this

The original assumption (a colleague's C# reference using `intent: save_credential_on_file` /
`stored_credentials` / `source`) suggested MTBC's Clover integration could tokenize a card and
charge it again later. This turned out to be false for the endpoint our app actually has
credentials for. Confirmed two ways:

1. **The endpoint's own published OpenAPI spec** —
   `https://qa-webservices.mtbc.com/Clover_Api/swagger/v1/swagger.json` — lists exactly two
   routes, `POST /api/payment/Create_Charge` and `POST /api/payment/Create_Charge_Response`, both
   sharing one closed request schema (`additionalProperties: false`) with only: `username`,
   `password`, `name`, `address1`, `city`, `state`, `zip`, `product_Name`, `business_Name`,
   `amount`, `cardNumber`, `expMonth`, `expYear`, `cvv`. No `source`, `token`, `customer`, or
   `intent` field exists anywhere in the schema, and there is no other endpoint on this API.
2. **Live sandbox testing against both routes** (using `CLOVER_MTBC_*` credentials), which confirmed:
   - `amount: 0` is rejected outright (`"Invalid amount"`) — no true $0 registration charge is
     possible.
   - `capture: false` is silently ignored — the charge is always captured/settled for real.
   - `intent`, `stored_credentials`, and `source` are all silently ignored/dropped; sending them
     produces no different behavior than a plain charge.
   - Charging by `source` alone (no raw card fields) is rejected by the endpoint's own model
     validation: `CardNumber` and `Cvv` are hard-required on every request, always.
   - The response never includes any card/token reference (`paymentMethodDetails` is always
     `null`) — there is nothing to store for reuse even if the above weren't true.
   - A second, real production C# reference (`PayUserDirectAsync`, calling
     `Clover_Api/api/payment/Create_Charge` on `mhealth.mtbc.com`) confirms this independently:
     its request never includes `source`/`intent`/`stored_credentials`, and its response DTO
     (`CloverPaymentData`/`CloverOutcome`) matches exactly what we observed live — a plain
     one-shot charge, nothing more.

**Conclusion**: `CloverChargeService` (MTBC) is one-shot-charge-only and always will be, on the
credentials/endpoints we have. It remains correct and unchanged for normal (non-trial) checkout.

### If Phase 2 still wants to use MTBC for this

Ask MTBC (via Sammar) directly, since their stack demonstrably supports Clover's stored-credential
model somewhere (per the original C# snippet) — just not on either endpoint above:

1. Does a different endpoint/URL exist that accepts `intent`/`stored_credentials`/`source`, and is
   it reachable on a sandbox/QA host (not production-only)?
2. Can a card be registered for future use without a real non-zero charge (a true $0 or auth-only
   `capture: false` request)? If not, is there an automatic void/refund for the registration
   charge, since the product requirement is "no charge during the trial"?
3. Exact field name for the reusable card/token reference in the response (the two known endpoints
   never return one — `paymentMethodDetails` is always `null`).
4. Exact field name to charge a previously-saved card, and whether raw `CardNumber`/`Cvv` are
   still required alongside it.
5. Separate sandbox credentials for this endpoint (the second C# reference reads from a
   `YourPay:Username`/`YourPay:Password` config, distinct from our current `mtbcpayments` creds) —
   sandbox only.
6. A live example request/response (not just reference code) for both the registration call and
   the reuse call.
7. Confirm the `amount` unit (dollars vs. cents) for whichever endpoint this turns out to be.

## The proven alternative: direct Clover eCommerce API (built + tested, now removed)

This is what was actually implemented and passing all tests before being stripped out today. It's
the recommended default for Phase 2 unless MTBC comes back with a real answer above, since it's
the only mechanism actually confirmed (via live sandbox testing) to save a card and charge it
again weeks later.

### Architecture

- **Separate, direct Clover integration** from MTBC — Public + Private API keys for a Clover
  eCommerce test merchant (`CLOVER_ECOMM_PUBLIC_KEY`/`CLOVER_ECOMM_PRIVATE_KEY`), obtained directly
  from Clover's own developer dashboard, not via MTBC.
- **Card tokenization happens entirely in the browser**, never on our server:
  1. Client-side RSA-OAEP encryption of the card's PAN using Clover's published TransArmor public
     key (`https://checkout.clover.com/assets/keys.json`, keyed `TA_PUBLIC_KEY_DEV`/`_PROD`) via
     the native Web Crypto API (`RSA-OAEP`, `hash: 'SHA-1'` — Web Crypto ties MGF1's hash to the
     main hash param, reproducing Java's `RSA/None/OAEPWithSHA1AndMGF1Padding` with zero external
     libraries). Plaintext is an 8-zero prefix (`"00000000"`) + the card number. Ported faithfully
     from Clover's own official Java reference implementation (`EncryptPan.java`).
  2. The browser POSTs `{card: {encrypted_pan, first6, last4, exp_month, exp_year, cvv, brand}}`
     directly to `POST {tokens_base_url}/v1/tokens` with the `apikey` header (Public key) —
     `tokens_base_url` (`token-sandbox.dev.clover.com`) is a **different host** from `base_url`
     (`scl-sandbox.dev.clover.com`). Server-to-server calls to `/v1/tokens` are blocked (confirmed
     via live testing — HTTP 403, a WAF/bot-fingerprint check), which is exactly why this step must
     happen in the browser.
  3. The resulting single-use token is written into a Livewire property (`cloverToken`) via
     `$wire.set('cloverToken', token, false)`.
- **Server-side, using the Private key** (`CloverEcommerceService`):
  - `createCustomerWithCard(email, source: $cloverToken)` → `POST {base_url}/v1/customers`,
    Bearer-token auth. Response: `{"id": "...", "sources": {"data": ["<cardId>", ...]}}`. Returns
    a `CloverCustomerResult` (customerId, cardId).
  - `chargeSavedCard(customerId, amount, description)` → `POST {base_url}/v1/charges` with
    `source: customerId` (charging a saved card uses the **customerId itself** as `source`, not a
    separate card id — confirmed via Clover's docs). Returns a `ChargeResult`.
  - Error shape for both: `{"error": {"message": "..."}}`.
  - **Not verified**: the `amount` unit on `/v1/charges` (assumed cents) — needs a real
    trial-conversion test before relying on it.

### Checkout flow

1. Client applies a `DiscountType::FreeTrial` discount code at Step 1 (`applyDiscountCode()` — the
   Phase-2 re-enable is just deleting the rejection block added today).
2. `isFreeTrialCheckout` computed drives Step 1 UI: "Due Today: $0.00", "Then $X/year once your
   free trial ends."
3. Card fields tokenize client-side (above) instead of `pay()`'s normal raw-card POST.
   `payFreeTrial()` validates billing fields + `cloverToken`, calls `createCustomerWithCard()`,
   creates the account (if guest), then creates an `Order` with `payment_status: Trialing`,
   `status: Paid`, `amount_paid: 0`, `original_price` = package price, `trial_ends_at =
   now()->addDays($discountCode->trial_days)`, `clover_card_token = customerId`. Sends
   `ClientTrialStartedMail`.
4. **3 days before `trial_ends_at`**: a daily scheduled command (`trials:process`,
   `ProcessTrialLifecycle`) sends `ClientTrialEndingReminderMail` with two actions — proceed or
   cancel — and sets `trial_reminder_sent_at` so it only sends once.
5. **Client clicks "Proceed with Payment"** (dashboard banner or email link) →
   `confirmTrialContinuation($orderId)` charges the saved card **immediately** via
   `chargeSavedCard()`, regardless of whether `trial_ends_at` has passed. On success:
   `payment_status: Paid`, `amount_paid` = package price, `trial_confirmed_at = now()`, sends
   `ClientPaymentReceiptMail`. On decline: stays `Trialing`, shows the decline message, client can
   retry.
6. **Client clicks "Cancel my subscription"** → `cancelTrialSubscription($orderId)`: immediately
   `status: Cancelled`, sends `ClientTrialCancelledMail`.
7. **No response by `trial_ends_at`**: the same daily command's second pass cancels it exactly
   like an explicit rejection (`trial_ends_at` is the hard deadline, not the reminder).
8. Once `status: Cancelled && payment_status: Trialing`
   (`Order::blockedFromAiGeneration()` — already in place), new AI generation
   (`submitIntake()`/`submitForReview()`/`regenerateDocument()`) is blocked with a "subscribe to
   continue" prompt; documents already generated/approved stay downloadable.

### Activity/payment log conventions used

- `ActivityLog` event types: `trial.started`, `trial.reminder_sent`, `trial.cancelled`, plus the
  existing `order.paid` reused for trial→paid conversion.
- `PaymentLog::record()` always passed an explicit `message` for trial call sites (a $0 amount
  doesn't explain itself): `"Free trial started — card saved via Clover, no charge"`, `"Trial
  converted to paid"`, plus the existing decline-message plumbing for a failed conversion.

### Files to recreate (all existed and were fully tested before removal today)

1. `app/Services/CloverEcommerceService.php`, `app/Services/CloverCustomerResult.php`
2. `config/services.php` `clover_ecomm` block + `CLOVER_ECOMM_PUBLIC_KEY` /
   `CLOVER_ECOMM_PRIVATE_KEY` / `CLOVER_ECOMM_BASE_URL` / `CLOVER_ECOMM_TOKENS_BASE_URL` env vars
   (sandbox values were: `https://scl-sandbox.dev.clover.com` / `https://token-sandbox.dev.clover.com`)
3. `⚡portal.blade.php`: `cloverToken` property, `isFreeTrialCheckout` computed,
   `validateBillingAndTrialFields()`, `payFreeTrial()`, `confirmTrialContinuation()`,
   `cancelTrialSubscription()`, the RSA/tokenization Alpine `x-data` block on the Payment Details
   card, the Step 1 free-trial summary UI, the dashboard active-trial banner, and removing the
   `DiscountType::FreeTrial` rejection in `applyDiscountCode()`
4. `app/Mail/ClientTrialStartedMail.php`, `ClientTrialEndingReminderMail.php`,
   `ClientTrialCancelledMail.php` + `resources/views/emails/client/trial-*.blade.php`
5. `app/Console/Commands/ProcessTrialLifecycle.php` + `Schedule::command('trials:process')->daily()`
   in `routes/console.php`
6. Tests: restore the free-trial-checkout section of `PortalTest.php` (checkout creates a Trialing
   order, decline handling, requires `cloverToken`, confirm/cancel continuation) and
   `tests/Feature/TrialLifecycleTest.php` (reminder fires once, expired trial gets cancelled)

### Before shipping Phase 2

1. Verify the `amount` unit on `/v1/charges` (cents vs. decimal dollars) with a real
   trial-conversion test against the Clover sandbox.
2. Full end-to-end live sandbox test: tokenize → save card → (simulate trial end) → charge saved
   card, on a network that can reach `checkout.clover.com` / `token-sandbox.dev.clover.com`
   without certificate interception (a corporate/Windows network hit `net::ERR_CERT_AUTHORITY_INVALID`
   here before — a Mac on a different network reached it fine).
3. Decide MTBC vs. direct-Clover based on Sammar's answers above, if pursued.
