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
