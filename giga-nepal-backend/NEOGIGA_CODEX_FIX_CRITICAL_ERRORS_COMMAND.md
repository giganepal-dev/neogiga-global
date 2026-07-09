# NeoGiga Codex Fix Critical Errors Command

Goal: fix P0 blockers identified in `NEOGIGA_CODEX_MASTER_AUDIT_REPORT.md` without destructive changes.

Evidence:
- Active DB observed as `neogiga`; requirement says `neogiga_prod`.
- `php artisan test` is unavailable.
- AI/BOM, POS refund, import/export, and invoice endpoints return 501.
- Admin API auth is a placeholder token gate.

Rules:
- Do not run destructive DB commands.
- Do not overwrite `.env`.
- Create restore point before edits.
- Add incremental migrations only.
- Update `CHANGELOG.md`.

Tasks:
1. Produce a DB separation plan for `neogiga_prod`; do not migrate data until explicitly approved.
2. Restore test tooling in a safe/staging environment and make `php artisan test` available.
3. Add route audit tests for admin protection and public write throttles.
4. Decide per 501 endpoint: implement, hide, or mark as disabled in API response.
5. Replace or harden admin token gate with user-bound admin auth plan and policy scaffolding.
6. Add `/health` endpoint for app/db/cache/queue/storage checks.

Verification:
- `composer validate`
- `php artisan route:list`
- `php artisan config:cache && php artisan route:cache`
- `php artisan test`
- HTTP checks for admin/API protection

Rollback:
- Restore files from timestamped restore point.
- Do not roll back migrations destructively; add forward fix migrations if needed.

Deliverable:
- `NEOGIGA_CRITICAL_FIX_IMPLEMENTATION_REPORT.md`

