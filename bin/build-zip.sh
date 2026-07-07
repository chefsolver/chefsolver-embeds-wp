#!/usr/bin/env sh
# Build the installable WordPress plugin ZIP from the repo source.
#
# Output: build/chefsolver-embeds.zip
#   - plugin folder `chefsolver-embeds/` at the ZIP root (WP Admin upload-ready)
#   - runtime files only: main file, includes/, blocks/, readme.txt, LICENSE,
#     uninstall.php — no docs/, no build/, no .git, no repo README.
#
# Usage: bin/build-zip.sh   (run from the repo root)
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAGE="$(mktemp -d)/chefsolver-embeds"
OUT="$ROOT/build/chefsolver-embeds.zip"

mkdir -p "$STAGE" "$ROOT/build"
cp "$ROOT/chefsolver-embeds.php" "$ROOT/uninstall.php" "$ROOT/readme.txt" "$ROOT/LICENSE" "$STAGE/"
cp -R "$ROOT/includes" "$ROOT/blocks" "$STAGE/"

rm -f "$OUT"
( cd "$(dirname "$STAGE")" && zip -r "$OUT" chefsolver-embeds >/dev/null )
rm -rf "$(dirname "$STAGE")"

echo "Built: $OUT ($(wc -c < "$OUT") bytes)"
