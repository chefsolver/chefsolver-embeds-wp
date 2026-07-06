# build/

Output directory for installable plugin ZIPs used in QA and releases.

ZIPs are gitignored — this directory only tracks this README. A build script will
produce `build/chefsolver-embeds.zip` with the `chefsolver-embeds/` plugin folder
at the ZIP root, containing runtime files only (no `docs/`, no `.git`).
