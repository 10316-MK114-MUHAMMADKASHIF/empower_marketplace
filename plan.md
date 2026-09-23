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

### Update (2026-09-15): a lead — MTBC's TokenEx wrapper

MTBC handed over a curl for `POST https://qa-webservices.mtbc.com/TokenXTest/api/TokenEx/TokenizeWithCVV`
(bearer-JWT auth, body `{"data": "<cardNumber>", "cvv": "..."}`). This is a **different subsystem**
from `Clover_Api` — it's MTBC's own thin wrapper in front of TokenEx (a real third-party
tokenization vendor), not TokenEx's raw API directly (TokenEx's actual API authenticates with
`TX_TokenExID`/`TX_APIKey` headers on `test-api.tokenex.com`, confirmed via TokenEx's own public
docs — so MTBC's wrapper's behavior, response shape, and whatever charge-side endpoint it exposes
are not documented anywhere public; only MTBC can answer them).

Tried the curl live: 401. The bearer token's own JWT claims show a 30-minute lifetime
(issued 17:53 UTC, expired 18:23 UTC 2026-09-14) — it was already ~12 hours stale by the time we
tested it, so this isn't a static credential to hardcode; whatever issues it needs to be called
fresh as part of the real integration.

**Still needed from MTBC before this can become a real plan** (tokenizing a card is useless for
recurring billing without a way to charge that token later):
1. How to obtain/refresh the bearer token (login endpoint + required credentials).
2. A real example response from `TokenizeWithCVV` — which field holds the reusable token, and
   whether it returns card metadata (last4/brand/expiry) for display.
3. Whether the token is single-use or reusable, and if/when it expires.
4. **The charge-with-token endpoint** — exact path + sample request/response. Does
   `Create_Charge`/`Create_Charge_Response` now accept a token in place of raw `cardNumber`/`cvv`,
   or is there a separate endpoint? This is the one question that determines whether this path is
   viable at all.
5. Whether raw `cardNumber`/`cvv` are still required alongside the token when charging.
6. Whether `TokenizeWithCVV` itself ever moves money, or is guaranteed a pure vaulting call.
7. Declined/failed response shape for both tokenize and charge-with-token.
8. `amount` unit (dollars vs. cents) on the charge-with-token call.
9. Whether this is sandbox-only (`qa-webservices.mtbc.com`) or also available in production.

### Update (2026-09-17): MTBC's answers — still not enough to proceed

MTBC (via CCPM/ROI) responded to the 9-question list above. Net result: **the one blocking
question — the charge-with-token endpoint — is still unanswered**, and one of their answers raises
a new concern.

**Actually answered:**
- Auth is username/password (not an API key/client secret); they will provide the credentials.
- Sandbox credentials for this TokenEx wrapper are separate per environment, and distinct from
  `CLOVER_MTBC_USERNAME`/`PASSWORD`.
- Amount unit: "support both dollar and cents" — ambiguous as stated (a real API normally takes one
  fixed unit); treat as unconfirmed until proven with a live test.
- Currently QA-only (`qa-webservices.mtbc.com`); production availability not confirmed.
- Rate limits/latency: unknown — blocked on their own pending VAPT (security review). This alone
  means the wrapper isn't production-ready regardless of anything else.
- Scope clarified: MTBC's side only ever covers tokenize + (eventually) charge-by-token. All
  subscription lifecycle management — storing the token, tracking next-bill-date, card-expiry,
  cancelled subscriptions — is explicitly on us to build in the Laravel app. This is exactly the
  lifecycle machinery already designed below for the Clover eCommerce path
  (`trial_ends_at`/`ProcessTrialLifecycle`/etc.), so it isn't incremental new complexity either way.

**Still not answered (deferred with no concrete detail):**
- Q1 (login endpoint path) — only the credential *type* was confirmed, not the endpoint or the
  refresh mechanism (Q2 explicitly deferred as "dependent on Point#1").
- Q4 (real `TokenizeWithCVV` response, which field is the reusable token) — "will be shared today."
- **Q6 — the charge-with-token endpoint itself, exact path + sample request/response — answered
  only "Same as Point#5," which itself just said "we will provide the endpoint."** This is the
  single fact that determines whether this integration is viable at all, and it still doesn't
  exist as a concrete answer.
- Q7 (whether raw card/cvv are still required alongside the token) — deferred back to Q5/Q6 again.

**New concern:** the example given for Q9 (declined/failed response shape) is actually a
*success* response (`"Status":true`, `"Message":"Payment Successful"`), not a decline — and its
shape (`PaymentMethodDetails: null`, `Id`/`Outcome`/`Captured` fields) is identical to the
already-documented `Create_Charge_Response` from the plain one-shot `Clover_Api` endpoint (see
"What we learned" above — the same endpoint already confirmed to have zero token/vaulting
capability). That suggests MTBC may not actually have a distinct, tested charge-with-token endpoint
to show yet, and possibly answered from the existing one-shot endpoint by default.

**Recommendation: don't block Phase 2 on this.** Proceed with the direct Clover eCommerce path
below as the real Phase 2 implementation now — it's still the only mechanism actually proven (live
sandbox testing) to save a card and charge it again later. Keep the MTBC thread open in parallel,
but only worth revisiting if they come back with an unambiguous answer to Q6 (a real
charge-with-token endpoint that actually exists and has been tested) — until then there's nothing
further to build against on that side.

### Update (2026-09-19): MTBC delivered a concrete tokenize/detokenize API — but it reintroduces the CVV-storage blocker

MTBC handed over full working credentials + sample curls for a **new, separate API**:
`https://uat-webservices.mtbc.com/Empower_Payment_Api` (distinct host+path from `Clover_Api` above)
— `POST /api/auth/token` (username/password → short-lived JWT, ~10 min expiry per their notes,
10 req/min/IP rate limit), `POST /api/payment/tokenize`, `POST /api/payment/detokenize`. Card data
sent to tokenize/detokenize must be pre-encrypted by us with a shared AES key they provided
(`AesKey=2595874569321569`) — algorithm reverse-engineered from their C# reference: AES-128-CBC,
PKCS7 padding, **static all-zero 16-byte IV** (not random — `GenerateIV()` is called then
immediately overwritten), key = the raw ASCII bytes of the shared key string used as-is (their
`GetKey()` byte-cycling loop is a no-op here since the key string is exactly 16 bytes = the AES-128
key size). **Verified compatible**: a PHP `openssl_encrypt('aes-128-cbc', ..., OPENSSL_RAW_DATA,
$zeroIv)` + base64 round-trips correctly in isolation (self-consistency confirmed; full live
round-trip against their tokenize→detokenize not completed — see blocker below).

Pulled this API's own `swagger.json` (`/Empower_Payment_Api/swagger/v1/swagger.json`) to see the
complete route list, since the sample curls didn't mention a charge endpoint at all. It has 5 routes
total: `auth/token`, `tokenize`, `detokenize`, and **`Create_Charge` / `Create_Charge_Response`** —
and those last two use a `CloverChargeRequest` schema (`username`, `password`, `name`, `address1`,
`city`, `state`, `zip`, `product_Name`, `business_Name`, `amount`, `cardNumber`, `expMonth`,
`expYear`, `cvv`) that is **byte-for-byte identical** to the old `Clover_Api`'s already-proven
charge-only schema above — no `token` field anywhere. So even in this new API, there is still no
literal "charge by token" call.

**The actual intended flow, reverse-engineered from this and confirmed by the user (2026-09-19):**
tokenize once at signup (get back a masked/format-preserving reference — first6+last4 real, middle
digits randomized — for display and lookup only); to charge again later, call detokenize to get the
encrypted blob back, decrypt it ourselves with the same AES key, and submit the recovered **raw**
`cardNumber`+`cvv` to `Create_Charge` exactly like a fresh one-shot charge — decrypting detokenize's
`value`/`cvv` fields yields the actual, usable card number and CVV, not an intermediate value.
`Create_Charge`'s body-level `username`/`password`, and its plaintext (not AES-encrypted)
`cardNumber`/`cvv` fields, are **confirmed to be the same mechanism/credentials already used for the
existing single (one-time) payment flow** — i.e. `CLOVER_MTBC_USERNAME`/`PASSWORD`, matching
`CloverChargeService`'s existing usage — not something new to figure out. (Empirically, the
`auth/token` credentials, `mtbcpayments`/..., do NOT work here — live rejection: `"Invalid username
or password"` — which is consistent with this confirmation.)

**Why this doesn't actually resolve Q6 (the real blocker): detokenize hands back the CVV.**
`tokenize`'s sample request includes a `cvv` field, and `detokenize`'s response returns a decrypted
`cvv` value alongside the card number — meaning MTBC is storing the CVV (encrypted, but stored) for
later retrieval and reuse. **PCI-DSS prohibits storing CVV/CVV2 after the initial authorization,
full stop — encryption does not remove it from scope, and this applies to any party in the payment
chain, not just the merchant.** This is the exact same rule that ruled out naive "encrypt and store
the raw card" designs on 2026-09-04 (see project memory `trial-billing-clover-ecomm`). The reason
this AES dance exists at all is almost certainly to keep feeding a CVV back into `Create_Charge`,
which — being schema-identical to the already-proven old endpoint — very likely hard-requires CVV on
every call with no stored-credential/MIT exception (exactly like "What we learned" item 2 above:
"CardNumber and Cvv are hard-required on every request, always"). That would make this whole API,
as demoed, structurally unable to support compliant recurring billing, regardless of how the crypto
or credentials shake out.

Compare to the direct Clover eCommerce design below: `chargeSavedCard(customerId, amount, ...)`
never touches CVV again after the first charge, because a real card-on-file reference is sufficient
— the standard, compliant pattern for recurring/stored-credential transactions industry-wide. The
AES tokenize/detokenize flow does not match that pattern — it does not remove CVV from scope.

**Decision (2026-09-19): built anyway, on the user's explicit instruction, with the CVV-storage
trade-off knowingly accepted.** The user confirmed live (a real `Create_Charge` test call returned
`"incorrect_cvc": "Please provide valid cvv value."`) that CVV is in fact mandatory on every call,
with no stored-credential/MIT exception discovered — so the sharp follow-up question above is
answered, and it's the answer that made this API structurally incompatible with a CVV-free
recurring-billing design. Rather than fall back to the (more complex, browser-side-crypto-dependent)
Clover eCommerce path below, the user chose to proceed with this API regardless, accepting that MTBC
is retaining the CVV after authorization. The one mitigation fully within this app's control was
applied: **the app itself never persists a CVV anywhere** — `TrialBillingService::chargeStoredCard()`
is the sole place the recovered raw card/CVV briefly exist, and they go out of scope the instant the
charge call returns (no `orders` column can hold one; nothing is ever logged with it either — see
`EmpowerPaymentApiClientTest::test_declined_charge_is_logged_without_leaking_the_raw_card_number`).

**What was actually built** (see `resources/views/components/⚡portal.blade.php`,
`app/Services/{MtbcCardCipher,EmpowerPaymentApiClient,TrialBillingService}.php`,
`app/Console/Commands/ProcessSubscriptionBilling.php`, and the 5 new `App\Mail\Client*` classes):
free-trial signup tokenizes the card server-side and creates a `Trialing` order with no charge; the
client explicitly clicks "Proceed with Payment" to convert to the first paid year; every subsequent
annual renewal is fully automatic (detokenize → decrypt → `Create_Charge`), with a 3-attempt
(0/3/7-day) retry policy before cancelling on repeated failure; a self-service "Update Card" action
covers an expired/declined stored card. An admin-only "End Trial" action (users list) forces this
exact same charge attempt on demand, specifically so the real gateway integration can be verified by
hand without waiting for a client to convert. 429 tests passing, including live-verified crypto
compatibility and full dashboard-state rendering. The direct Clover eCommerce path below remains
documented but is no longer the active plan.

### Update (2026-09-19): `Create_Charge` also requires the Bearer token — confirmed by a real failure

First live use of "End Trial" against the real UAT sandbox failed with a logged, empty-body 401:
`{"http_status":401,"body":""}`. This is a materially different failure shape from an app-level
rejection (which comes back as `{"status":false,"message":"..."}` — e.g. the `"Invalid username or
password"` seen earlier in this doc when the wrong body credentials were used **with** a Bearer
token attached). An empty-body 401 means the request never reached the app's own controller logic at
all — it was rejected by the API's auth gateway before that point. The only difference between the
"Invalid username or password" test (worked, reached app logic) and this live failure (rejected at
the gateway) was the Bearer header.

**Conclusion, now fixed in code**: `Create_Charge` on this API requires the same Bearer token as
tokenize/detokenize, contrary to the assumption this doc's "actual flow" section stated (mirroring
the older, separate `Clover_Api` gateway, which never used one). `EmpowerPaymentApiClient::charge()`
now calls the same cached `accessToken()` used by tokenize/detokenize, and retries once with a fresh
token on a 401 — identical resilience to `postAuthenticated()`. The body-level `username`/`password`
(`CLOVER_MTBC_*`) are unaffected by this and remain the confirmed credentials for that field.

Caught a second, unrelated issue while fixing this: several `EmpowerPaymentApiClientTest` cases for
`charge()` didn't fake `/api/auth/token`, so — since `Http::fake()` only stubs the URLs it's given —
those tests were silently making a real network call to MTBC's live UAT sandbox on every run. It
happened to "work" because the sandbox is genuinely reachable, but it's exactly the kind of hidden
live-network dependency a test suite must never have (slow, consumes MTBC's 10-req/min limit on every
test run, would fail unpredictably if the sandbox were ever down). Every test now explicitly fakes
`/api/auth/token`; a new `test_charge_clears_the_cached_token_and_retries_once_on_a_gateway_401` test
reproduces the exact empty-body-401 shape seen live.

### Update (2026-09-22): Annual/Monthly billing choice added to Step 1 — deliberately one-time-only on the direct `pay()` path

Clients now pick Annually or Monthly on Step 1 (right after the discount code field, default
Annually). New `App\Enums\BillingCycle`, `Package::priceForCycle()`/`hasMonthlyPricing()` (reusing
the `Package.monthly_price`/`Order.billing_cycle` columns that already existed in the schema but
were never wired up), the toggle only renders when the selected package has a `monthly_price` set.

**Deliberately asymmetric, confirmed with the user — do not "fix" this without asking again**: the
cycle choice only produces genuine recurring billing on the **free-trial path**
(`payFreeTrial()` → `TrialBillingService::convertTrialToPaid()` → `ProcessSubscriptionBilling`'s
`renew()`, all now cycle-aware for both charge amount and `next_bill_date` cadence). The direct,
no-trial `pay()` checkout has never set `next_bill_date` on the order it creates (true before this
feature too), so picking Monthly there charges the monthly amount **once**, with no follow-up
billing — same one-time nature Annual already had on that path. The user explicitly chose to leave
this as-is for now rather than wire `pay()` into the renewal engine.

**Update (2026-09-23): reversed — `pay()` is now wired into the same renewal engine.** The user
decided the asymmetry above was a temporary gap, not a permanent design choice. `renew()` and
`ProcessSubscriptionBilling` turned out to already be fully origin-agnostic (they only ever look at
`payment_status`/`next_bill_date`/`clover_card_token` on the order, nothing trial-specific), and the
dashboard's renewal UI (`⚡portal.blade.php`, the `Paid && next_bill_date` "Renews {date}" banner and
the `PastDue` "Update Card" banner) was already equally generic — so the entire fix was contained to
`pay()` itself: right after its existing `CloverChargeService::charge()` call succeeds, it now also
calls `EmpowerPaymentApiClient::tokenize()` (same call `payFreeTrial()` already made) and stores
`clover_card_token`/`card_expiry_month`/`card_expiry_year`/`card_last_four`/`mtbc_reference_number`
plus a cycle-aware `next_bill_date` on the created order. A tokenize failure never fails checkout
(money already moved by that point) — the order is created without a card on file and degrades
through the existing "no payment method on file" decline → `PastDue` → self-service "Update Card"
path on its first renewal attempt. No changes were needed to `TrialBillingService`,
`ProcessSubscriptionBilling`, or the dashboard Blade.

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
