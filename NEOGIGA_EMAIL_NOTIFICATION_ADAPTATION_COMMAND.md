# NeoGiga Email Notification Adaptation Command

Implement notification/email foundation using Digikash, LivaChat, and Salesy as reference only.

Build:
- Notification templates with channel variants.
- Notification preferences per user/vendor/customer.
- Event-triggered sends for order, POS sale, payment, refund, vendor approval, stock alert, LMS enrollment, certificate, campaign, OTP.
- Queue-backed send log with status, retries, provider response, and error message.
- Newsletter/campaign subscriber lists with unsubscribe tokens.
- SMS/WhatsApp/push provider placeholders; no live credentials.

Rules:
- Do not copy provider secrets or templates directly.
- Do not send real campaigns during verification.
- Add incremental migrations only.
- Update `CHANGELOG.md`.

