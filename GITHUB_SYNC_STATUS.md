# GITHUB_SYNC_STATUS (2026-07-09)

| Item | Value |
|---|---|
| Branch | `main` |
| Remote | `origin` → `https://github.com/giganepal-dev/neogiga-global.git` (fetch + push) |
| Divergence | **local 14 commits ahead of `origin/main`, 0 behind** (fast-forward push — no force needed, none will be used) |
| Local HEAD | `70664c0` |
| origin/main | `a63fccc` (PR#4 merge) — fully contained in local |

## Local commits awaiting push (newest first)
```
70664c0 Reference integration cycle: final deliverable docs
6b426d8 Reference integration: admin Orders + Invoice, frontend product pages
e90b92b Surface password reset on frontend + admin panel
f7ee7a2 Add password reset + email verification (reviewed cherry-pick, no Sanctum)
c20bdfe Queue worker installed on live: neogiga-queue.service, backlog drained
51e4c23 Release cycle 2026-07-09: sync 68 live-only docs, deployment plan+report
032b5a6 Integrate PR#4 region-stock module: guarded migration, admin Region Stock page
6e918be Merge remote-tracking branch 'origin/main'
4943558 Merge PR#3 (product advanced specs): keep our ProductCategory, guard migration
2854f10 Merge remote-tracking branch 'origin/main'
6ec07b7 Add admin Applications page for the Onboarding module
f98d507 Resolve PR#2 merge: keep prod-union api.php, guard duplicate migrations inert
3066223 Merge origin/main (PR#2) — keep prod-union api.php
94ef075 Sync prod parallel build: Onboarding+Auth+CommerceAI; restore payments routes
```
(Later commits from the current cycle append to this list; re-run `git log --oneline origin/main..HEAD`.)

## Exact push procedure once credentials exist
```bash
cd "~/Downloads/neogiga-main 2"
git fetch origin
git log --oneline HEAD..origin/main     # if non-empty, merge first (union api.php rule!)
git push origin main                    # plain push — NEVER --force
```
Credential options (either works):
- `gh auth login` (install GitHub CLI), then the push above, or
- a PAT: `git remote set-url origin https://<TOKEN>@github.com/giganepal-dev/neogiga-global.git`
  (or store via `git credential` helper), then push, then optionally restore the tokenless URL.

## Why this matters
The repo's automated PR process branches off `origin/main`. While it's stale, every new PR
edits outdated versions of files we've since fixed (this already caused the payments-route
clobber and the Sanctum branch basing off a pre-fix AuthController). Pushing closes that gap.
**⚠ Also on GitHub:** unmerged branch `git-repository-access-e1d13` (Sanctum rewrite) must NOT
be merged without human review — it would break live token auth (see LIVE_DEPLOYMENT_REPORT).
