# WhatsApp Campaign Guide

This document covers the NeoGiga Phase 2 marketing automation foundation added as an upgrade layer.

## Tables Created
CRM, newsletter, email automation, WhatsApp, abandoned cart, OTP, analytics, dashboard, and settings tables are created by `database/migrations/marketing/2026_07_06_180000_create_marketing_automation_foundation_tables.php`.

## Services Created
Services live under `app/Services/Marketing` and provide segmentation, consent, preferences, email provider abstraction, email queueing, campaign targeting, analytics, and order/transactional notification placeholders.

## APIs Created
Public write APIs validate input and use throttling. Admin APIs are protected by `admin.token`. Marketing unsubscribe and suppression checks are centralised in `ConsentManagementService`.

## Jobs Created
Queue-ready jobs live under `app/Jobs/Marketing`; they are placeholder-safe and log execution until real provider credentials and business workflows are enabled.

## Env Variables
`MARKETING_EMAIL_PROVIDER`, `MARKETING_EMAIL_TEST_MODE`, `WHATSAPP_PROVIDER`, `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `OTP_EXPIRY_MINUTES`, `OTP_RESEND_COOLDOWN`, `GA_MEASUREMENT_ID`, `VITE_GA_MEASUREMENT_ID`. No credentials are committed.

## Security And Consent
Marketing emails and WhatsApp campaigns must respect consent, opt-in, unsubscribe, and suppression lists. Transactional email is separated from marketing content. WhatsApp remains placeholder/manual export unless explicitly configured. OTP values are hashed and expire.

## Remaining Limitations
Provider webhooks, real email/WhatsApp sending, rich admin UI, and advanced dynamic audience SQL are intentionally provider-safe placeholders for the next phase.
