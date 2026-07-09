# LIVE DEPLOYMENT PLAN — 2026-07-09

Release manager cycle for neogiga.com (`/home/neogiga/laravel/current`, host `precious`).
Note: the live path is **not a git checkout** — sync is file-level (rsync/scp), with local git
(`~/Downloads/neogiga-main 2`) as the source of truth. Local git is a strict superset of
`origin/main` (a63fccc / PR#4) plus 9 safety/integration commits GitHub doesn't have yet
(push blocked: no credentials on this machine).

## GitHub survey (2026-07-09)
| Ref | State | Decision |
|---|---|---|
| `main` (a63fccc, PR#4) | fully contained in local git and already deployed | nothing to do |
| `neogiga-platform-audit-and-strategy-b85a9` (e7b5bd5) | unmerged; docs-only (2 launch-readiness MDs + .gitignore) | wait for PR merge |
| `git-repository-access-e1d13` (510d92d) — **NEW** | unmerged; **Sanctum auth rewrite** touching live AuthController, User model, config/auth.php, routes/api.php, base users migration | **EXCLUDED — high risk** (would break live custom-token auth `users.api_token_hash`); needs human review before any merge |

## What will be updated
- **Live server: NO code changes.** Verified zero drift: every live PHP file (app/, database/,
  config/, routes/) is byte-identical to local git; route canaries (api.php, web.php) match.
- **Repo: 68 live-only docs synced live→git** (`giga-nepal-backend/NEOGIGA_*.md` reports/guides
  written by the prod-side build process) + live's app-level `CHANGELOG.md` (16 KB, newer).
- This plan + LIVE_DEPLOYMENT_REPORT.md committed to git and copied to the live app root.

## What will be preserved (untouched)
- `.env` (backed up separately, never redeployed), `storage/`, uploads, logs
- All live documentation (backed up + synced to git, never deleted)
- Live-only runtime state: DB, caches, queue tables
- PR#2/PR#3 reference code stays git-only (never deployed — duplicates of live modules;
  their migrations are hasTable-guarded in git as defense-in-depth)

## Risk level: **LOW**
No code deltas to apply. Validation + smoke tests only. The one risky item on GitHub
(Sanctum branch) is explicitly excluded.

## Rollback plan
- Backup at `/home/neogiga/backups/20260709_125406/` (code snapshot tar excl. vendor/storage,
  CHANGELOG, all NEOGIGA_* docs, `.env` chmod 600).
- Any regressed file: restore from the tar or `git show <sha>:<path>` (live == git 032b5a6).
- Caches: `php artisan route:cache && view:cache && config:cache` (config **last** — a cleared
  config cache falls back to sqlite and 500s the site; incident 2026-07-07).
- Canary after any change: `GET /api/v1/wallet` must return **401** (404 = route cache lost the
  payments block — rebuild union from git and re-cache).

## Validation to run (deviations from the requested script, for safety)
- `migrate --pretend` first; `--force` only if something pending is known-safe. **Skipping
  `config:clear`/`cache:clear`** (no code changed; clearing config on live is the known outage
  vector) and **skipping `composer install`** (composer.json/lock unchanged on live).
- Queue: `queue:failed`, jobs-table backlog, worker process check.
- Smoke: health, homepage, API, admin, wallet canary, pending-migrations count.
