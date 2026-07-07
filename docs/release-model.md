# Release model — ChefSolver Embeds

How this plugin is developed, QA'd and (eventually) distributed. This describes the *future* model; today only the GitHub development stage is active.

## 1. Development — GitHub (active)

- All development happens in this repository (`chefsolver/chefsolver-embeds-wp`), default branch `main`.
- Changes land via commits/PRs on GitHub. Issues and QA findings are tracked here.
- The WordPress.org directory and SVN play **no role** during development.

## 2. QA — installable ZIP (next)

- Each QA round is done against an installable ZIP built from `main` (folder `chefsolver-embeds/` at the ZIP root, only runtime files — no `docs/`, no `.git`).
- ZIPs are produced into `build/` (gitignored) by `bin/build-zip.sh` and attached to GitHub pre-releases for testers.
- Gate: the manual checklist in `docs/qa.md` plus `php -l` on every PHP file.

## 3. WordPress.org submission (future, not scheduled)

- After the plugin reaches feature completeness and passes QA, it *may* be submitted to the WordPress.org plugin directory for review.
- Submission is a manual step, done once, with the ZIP from step 2. Nothing in this repo automates it.
- Until approval, the plugin is only distributed as a ZIP from this repository; no claim of WordPress.org availability is made anywhere.

## 4. SVN sync (future, only after approval)

- If and when WordPress.org approves the plugin, the assigned SVN repository becomes the **release-only channel**:
  - Only tagged, approved releases from GitHub `main` are copied to SVN `trunk`/`tags`.
  - `assets/` (icons, banners, screenshots) sync to the SVN `assets/` directory.
  - No development, branches, or experiments in SVN — GitHub remains the source of truth.
- Version flow: bump version in `chefsolver-embeds.php` + `readme.txt` (`Stable tag`) → tag on GitHub → build ZIP → QA → copy to SVN.

## Out of scope (by design)

- No automated WordPress.org submission or SVN deploy tooling.
- No telemetry/tracking in any release.
- No premium tier.
