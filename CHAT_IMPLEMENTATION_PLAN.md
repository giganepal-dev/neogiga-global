# CHAT_IMPLEMENTATION_PLAN (2026-07-09)

Planning only (per cycle instruction — RFQ shipped first). Blueprint informed by the archive's
Chat/Conversation module (pattern only). Build as its own cycle with tests.

## Schema (guarded additive migration)
- `conversations`: id, subject, `type` enum support|seller|product_qa|ai, status
  open|assigned|resolved|closed, user_id (customer, FK users), vendor_id nullable,
  product_id nullable (product Q&A), order_id nullable, assigned_admin_id nullable,
  last_message_at, needs_human boolean (AI handoff flag), meta json, timestamps.
- `conversation_messages`: id, conversation_id FK cascade, sender_user_id nullable
  (null = system/AI), sender_role enum customer|seller|admin|ai|system, body text,
  is_internal_note boolean (admin-only), read_at, timestamps.
- `conversation_participants`: id, conversation_id FK, user_id FK, role enum
  customer|seller|admin, joined_at, last_read_at — supports multi-party + unread counts.
- `conversation_status_histories`: same audit pattern as orders/RFQs (assignment + status).

## Scopes & flows
- **Customer (support)**: create conversation, list own, reply. api.token routes:
  `POST/GET /api/v1/conversations`, `POST /api/v1/conversations/{id}/messages` (throttle:writes,
  ownership-checked).
- **Seller chat**: same tables, `type=seller`, vendor_id set; seller portal lists conversations
  for its vendor; "Chat with seller" button on product page creates/reuses a conversation.
- **Product Q&A**: `type=product_qa` + product_id; optional public display of answered Q&A later.
- **AI assistant**: `sender_role=ai` messages via CommerceAI module (already live) as responder;
  placeholder: `needs_human=true` toggles when the AI declines/user requests — appears in the
  admin inbox "Handoff" filter. No live AI calls until reviewed.
- **Admin inbox** `/admin/chats`: KPI row (open/assigned/handoff/resolved), filterable list,
  detail thread with reply + internal notes, **assignment to staff** (assigned_admin_id from
  admin/staff users), close/resolve with audit row.

## Non-functional
- CSP-safe SSR (no websockets initially; refresh/poll later), CSRF + throttle on all POSTs,
  ownership checks server-side, transcript retention = rows are append-only, no deletes.
- Permission placeholders: admin.chat.view / admin.chat.reply / admin.chat.assign.

## Order of work (one cycle)
migration → models → customer API + tests on neogiga_test → admin inbox pages → seller portal
hook → product-page "Chat with seller" button → deploy (migrate --path, caches, wallet canary).
Estimate: one focused cycle, no shared-file risk beyond web.php/api.php unions.
