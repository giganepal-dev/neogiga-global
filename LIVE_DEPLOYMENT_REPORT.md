# LIVE DEPLOYMENT REPORT — 2026-07-09

Companion to [LIVE_DEPLOYMENT_PLAN.md](LIVE_DEPLOYMENT_PLAN.md). Host `precious`,
live path `/home/neogiga/laravel/current` (not a git checkout; file-level sync).

## Git commit deployed
- Live code == local git **032b5a6** (verified byte-identical across app/, database/,
  config/, routes/ — zero drift, both route canaries match).
- Local git contains all of `origin/main` (**a63fccc**, through PR#4) + 9 integration/safety
  commits not yet on GitHub (**push blocked: no credentials on this machine**).

## Files changed on live this cycle
- **Code: none.** Nothing on GitHub main was new; nothing local was missing from live.
- `public/storage` symlink created (`storage:link` — was missing).
- This report + LIVE_DEPLOYMENT_PLAN.md copied to the live app root.

## New models/modules found on GitHub
- **Branch `git-repository-access-e1d13` (unmerged): Sanctum auth rewrite** — new
  EmailVerification/ForgotPassword/ResetPassword controllers, Sanctum config/middleware,
  personal_access_tokens migration, and **edits to the live AuthController, User model,
  config/auth.php, routes/api.php, and the already-run base users migration**.
  **NOT deployed — high risk**: live auth is custom bearer (`users.api_token_hash`, verified
  working); this branch would invalidate it. Needs human review before merging.
- Branch `neogiga-platform-audit-and-strategy-b85a9` (unmerged): docs-only (2 launch MDs).

## New migrations run
- **None pending, none run** (`migrate:status`: 0 pending). Yesterday's region_stock
  migration (4 tables) remains the latest applied.

## Docs synced
- **68 live-only documents pulled live→git** into `giga-nepal-backend/` (implementation/
  verification reports, API guides, CODEX audit series — written by the prod-side process).
- Live app-level `CHANGELOG.md` (16 KB, newer) synced into `giga-nepal-backend/CHANGELOG.md`.
- Nothing on live was modified or deleted; repo-root CHANGELOG.md untouched.

## Validation result
| Check | Result |
|---|---|
| Pending migrations | **0** ✓ |
| `queue:failed` | **0 failed jobs** ✓ |
| Homepage / health / categories / sitemap | **200** ✓ |
| API `/api/v1/marketplaces` | **200** ✓ |
| Wallet canary `/api/v1/wallet` | **401** ✓ (payments routes intact) |
| Admin login / region-stock | **200 / 302 (auth-gated)** ✓ |
| `public/storage` link | created ✓ |
| Skipped (per plan) | `composer install`, `config:clear`, `cache:clear` — no code changed; clearing config on live is the known 500-outage vector |

## Queue backlog status ✓ RESOLVED (2026-07-09 13:22)
- Found: **471 jobs** in the `default` queue (oldest 2026-07-06 16:15), zero processed —
  no worker existed for this app (the box's workers serve other apps).
- Payload audit before starting: all internal analytics jobs (DetectAbandonedCarts ×114,
  CalculateTrending*/TopSearchTerms ×~84/cycle, RefreshCustomerSegment, RegionalSalesReport) —
  **no outbound email/SMS**, safe to drain.
- Fix applied: installed **`/etc/systemd/system/neogiga-queue.service`** (User=neogiga,
  `queue:work database --sleep=3 --tries=3 --max-time=3600 --memory=256`, Restart=always,
  logs → `storage/logs/queue-worker.log`), `systemctl enable --now neogiga-queue`.
- Result: **backlog drained 471 → 0 in ~2 minutes, 0 failed jobs.** Wallet canary 401,
  health 200 after.

## Risks
1. **Sanctum branch** (`git-repository-access-e1d13`): if the auto-PR process merges it into
   main and it gets deployed unreviewed, live API auth breaks. Review/close it proactively.
2. **Unpushed local commits (9):** GitHub-based PRs keep branching off stale code (this cycle's
   Sanctum branch edits files GitHub doesn't have current versions of). Push local main ASAP.
3. ~~Queue backlog~~ **RESOLVED** — worker installed and backlog drained (see above).
4. Standing: the prod-side build process edits live files directly — always re-diff before
   any deploy (union-merge procedure), wallet canary after every change.

## Next required server fix
~~Queue worker~~ **DONE** — `neogiga-queue.service` installed, enabled, and draining.
Remaining: (a) push local git main to GitHub (credentials needed), (b) human review of the
`git-repository-access-e1d13` Sanctum branch before anything merges it. Note: the Laravel
scheduler cron already exists (`* * * * * … schedule:run`, user neogiga) — it was enqueuing
jobs all along; only the worker was missing. Pipeline is now complete: cron → queue → worker.

## Backup (rollback point)
`/home/neogiga/backups/20260709_125406/` — code snapshot tar (excl. vendor/storage),
CHANGELOG.md, all NEOGIGA_* docs, `.env` (chmod 600).
