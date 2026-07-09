# NeoGiga Codex Deployment Hardening Command

Goal: harden production deployment for NeoGiga.

Evidence:
- Active DB is `neogiga`, not required `neogiga_prod`.
- No verified health endpoint, backup script, queue/scheduler status, or monitoring.
- SSL works from prior checks.

Tasks:
1. Create production DB separation plan and execute only after approval.
2. Add `/health` endpoint with app/db/cache/queue/storage checks.
3. Add backup and restore scripts/docs.
4. Add queue worker and scheduler supervisor docs/checks.
5. Add deployment rollback checklist.
6. Verify CORS and security headers.
7. Add production `.env.example` updates without touching `.env`.

Rules:
- Do not overwrite `.env`.
- Do not run destructive database commands.
- Create restore point before edits.

Verification:
- Health endpoint returns expected JSON.
- Backup dry-run produces artifact.
- Queue/scheduler status documented.
- Route/config/view cache pass.

Deliverable:
- `NEOGIGA_DEPLOYMENT_HARDENING_REPORT.md`

