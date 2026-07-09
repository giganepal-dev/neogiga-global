# NeoGiga AI Commerce Public Guide

## Public Pages

- `/ai-commerce`
- Homepage AI Commerce section

## Public APIs

- `GET /api/commerce-ai/examples`
- `POST /api/commerce-ai/session`
- `POST /api/commerce-ai/message`
- `POST /api/commerce-ai/build-bom`

Versioned aliases:

- `GET /api/v1/commerce-ai/examples`
- `POST /api/v1/commerce-ai/session`
- `POST /api/v1/commerce-ai/message`
- `POST /api/v1/commerce-ai/build-bom`

## Engine

The demo uses a local NeoGiga rule engine. It does not call paid AI APIs and does not create orders, payments, or stock reservations.

Every BOM response includes source notes, confidence level, last updated timestamp, and an advisory-only disclaimer.
