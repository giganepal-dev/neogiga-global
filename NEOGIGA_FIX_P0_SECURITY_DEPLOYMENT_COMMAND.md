# COMMAND — Fix P0 (Security & Deployment)

**Purpose:** Resolve the three P0 findings from `NEOGIGA_PRIORITIZED_GAP_REPORT.md`.
**Safety:** Additive/deploy-only. **No** `migrate:fresh/reset/wipe/truncate`. No `.env` overwrite. No production DB schema change. Back up before touching Apache/`.env`.

---

## P0-1 — Deploy the already-built frontend `/categories` pages (fixes 177 sitemap 404s)

**Files (exist locally, verified last session):**
- `resources/views/frontend/layout.blade.php`
- `resources/views/frontend/categories/index.blade.php`
- `resources/views/frontend/categories/show.blade.php`
- `app/Http/Controllers/Web/CategoryController.php`
- `routes/web.php` (already contains `/categories` + `/categories/{slug}` routes)

**Pre-check (local):**
```
php -l app/Http/Controllers/Web/CategoryController.php
php artisan view:cache && php artisan view:clear          # compiles all blades (catch syntax errors)
# optional runtime test against isolated DB (NOT prod):
APP_ENV=testing php artisan serve --port=8099   # then curl /categories, a slug, a bad slug
```

**Deploy (server = `precious`, app at `/home/neogiga/laravel/current`):**
```
# from local giga-nepal-backend:
scp -r resources/views/frontend precious:/home/neogiga/laravel/current/resources/views/
scp app/Http/Controllers/Web/CategoryController.php precious:/home/neogiga/laravel/current/app/Http/Controllers/Web/
scp routes/web.php precious:/home/neogiga/laravel/current/routes/
ssh precious 'cd /home/neogiga/laravel/current && \
  chown -R neogiga:neogiga resources/views/frontend app/Http/Controllers/Web/CategoryController.php routes/web.php && \
  sudo -u neogiga php artisan route:clear  && sudo -u neogiga php artisan route:cache && \
  sudo -u neogiga php artisan view:clear   && sudo -u neogiga php artisan view:cache'
```
> **Important (ops hazard):** `routes/web.php` changed → the **route cache MUST be rebuilt** or the new routes stay 404. Do NOT run any `--env` migrate command on prod (config is cached; `--env` is ignored — see `neogiga-ops-safety`).

**Verify (live):**
```
curl -so/dev/null -w '%{http_code}\n' https://neogiga.com/categories                 # expect 200
curl -so/dev/null -w '%{http_code}\n' -L https://neogiga.com/categories/semiconductors # expect 200
curl -so/dev/null -w '%{http_code}\n' https://neogiga.com/categories/not-a-real-slug   # expect 404
```

**Rollback:** the route/view caches are regenerated from source; to revert, remove the three view files + controller + the two `/categories` routes from `routes/web.php`, then `route:cache && view:cache`. Backups of `routes/web.php` already exist under `/home/neogiga/deploy-backups/…`.

---

## P0-2 — Put the repo under version control

```
cd "~/Downloads/neogiga-main 2/giga-nepal-backend"
rm -rf .git 2>/dev/null   # only the EMPTY placeholder; confirm 'git status' says not-a-repo first
printf '/vendor\n/node_modules\n/.env\n/.env.*\n!/.env.example\n/storage/*.key\n/storage/logs/*\n/bootstrap/cache/*.php\n/public/build\n*.sqlite\n' > .gitignore
git init && git add -A && git commit -m "Snapshot: NeoGiga marketplace (live baseline)"
```
> Do **not** commit `.env`, keys, or `storage/logs`. Verify with `git status` that no secret is staged before commit.
**Rollback:** `rm -rf .git` (removes history only; files untouched).

---

## P0-3 — Rotate the admin password

```
ssh precious 'cd /home/neogiga/laravel/current && sudo -u neogiga php artisan tinker --execute="\$u=\App\Models\User::where(\"email\",\"admin@neogiga.com\")->first(); \$u->password=\"<OWNER_CHOSEN_STRONG_PW>\"; \$u->save(); echo \"rotated\";"'
```
Then confirm login at https://admin.neogiga.com/admin/login. **Do not store the password in any file or this repo.**

---

## P0-adjacent — Persist Apache directives in Virtualmin (prevents regeneration)
The www→non-www redirect and admin-alias removal were hand-edited in `neogiga.com.conf` (Virtualmin-managed). Re-add them via Virtualmin → *Server Configuration → Directives and settings* so a future domain edit doesn't revert them. `admin.neogiga.com.conf` is standalone and safe.

---

## Post-run report
After completing, append results to `NEOGIGA_LIVE_SITE_AUDIT.md` (or a new `NEOGIGA_P0_FIX_REPORT.md`): category URL codes, git commit hash, confirmation admin login works with the new password, and Virtualmin directive status.
