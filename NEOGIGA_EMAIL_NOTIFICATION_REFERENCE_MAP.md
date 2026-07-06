# NeoGiga Email Notification Reference Map

Best email/notification source: Digikash.
Secondary sources: LivaChat and Salesy.

## Digikash

Root: `/tmp/neogiga-reference-rescan/digikash-20/core-v2.0`

Useful files:
- `database/migrations/2024_09_12_080045_create_notifications_table.php`
- `database/migrations/2025_04_24_011128_create_notification_templates_table.php`
- `database/migrations/2025_04_24_011503_create_notification_template_channels_table.php`
- `database/migrations/2025_05_05_123515_create_notification_preferences_table.php`
- `database/migrations/2025_04_14_104122_create_subscribers_table.php`
- `app/Services/TransactionNotifierService.php`
- `app/Traits/Models/Concerns/HasNotificationPreferences.php`

## LivaChat

Root: `/tmp/neogiga-reference-rescan/livachat-20/codecanyon-54326601-livachat-laravel-open-source-live-chat-application/LivaChat`

Useful files:
- `database/migrations/2024_09_26_085724_create_email_campaigns_table.php`
- `database/migrations/2024_09_27_114550_create_campaign_templates_table.php`
- `database/migrations/2024_09_24_053801_create_campaign_user_lists_table.php`
- `database/migrations/2022_04_21_053048_create_verify_otps_table.php`
- `app/Mail/AppMailer.php`
- `app/Mail/VerifyMail.php`
- `app/Jobs/MailSend.php`
- `app/Notifications/TicketCreateNotifications.php`

## Salesy

Useful files:
- `app/Services/MailConfigService.php`
- `app/Services/EmailTemplateService.php`
- `app/Services/TwilioService.php`
- `database/migrations/2025_09_25_090314_create_newsletters_table.php`
- `database/migrations/2025_06_27_115807_create_email_templates_table.php`

## Adaptation Plan

- Template registry by event and channel.
- Channel support: email, database notification, SMS placeholder, WhatsApp manual/export placeholder, push placeholder.
- Subscriber preferences and unsubscribe tokens.
- Queue all outbound sends.
- Audit all notification attempts with provider status and error message.

