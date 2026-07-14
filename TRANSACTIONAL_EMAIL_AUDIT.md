# Transactional Email Audit

Generated: 2026-07-13

## Existing capabilities

- Laravel mail defaults to `log`; database queues are configured.
- `EmailQueueService`, `TransactionalEmailService`, `OrderNotificationService`, `InvoiceEmailService`, OTP flows, and `SendTransactionalEmailJob` exist.
- Transactional message records are stored in `email_messages` and `email_message_events`.

## Root causes and gaps

1. The provider manager does not call Laravel mail or a transactional API.
2. Marketing and transactional provider configuration share the same basic manager.
3. Transactional jobs do not select a dedicated high-priority queue or define retry/backoff behavior.
4. There are no sender profiles, domain verification records, failover/circuit state, delivery attempt records, or communication failures.
5. Unsubscribe and global suppression effects are not classified by message type.
6. Order/payment/shipment events are not consistently idempotent and do not all record the related entity.
7. Required template variables and regional links are not validated before delivery.

## Upgrade decision

Keep Laravel mail as the transactional transport boundary and preserve log mode. Add a separate transactional configuration block, queue assignment, bounded retries, message idempotency keys, communication logs/failures, regional sender profiles, and a global-suppression policy that still permits legitimate required service messages unless the address is invalid, hard-bounced, complained, blocked, or on security hold.
