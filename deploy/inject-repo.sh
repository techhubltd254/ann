#!/usr/bin/env bash
set -Eeuo pipefail
TARGET=${1:?Usage: bash inject-repo.sh /path/to/ann}
SOURCE=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
TARGET=$(cd -- "$TARGET" && pwd)
[[ "$TARGET" != "$SOURCE" ]] || { echo 'Target must be your ann checkout, not the extracted overlay.' >&2; exit 1; }
[[ -d "$TARGET/.git" ]] || { echo 'Not a Git checkout.' >&2; exit 1; }
[[ -z "$(git -C "$TARGET" status --porcelain)" ]] || { echo 'Commit or back up existing changes first. Nothing replaced.' >&2; exit 1; }
REMOTE=$(git -C "$TARGET" remote get-url origin)
[[ "$REMOTE" == *techhubltd254/ann* || "$REMOTE" == *techhubltd254:ann* ]] || { echo 'Origin does not identify techhubltd254/ann. Nothing replaced.' >&2; exit 1; }
BRANCH="release/kicc-experience-$(date -u +%Y%m%d-%H%M%S)"
git -C "$TARGET" switch -c "$BRANCH"
BACKUP="$(dirname "$TARGET")/ann-before-injection-$(date -u +%Y%m%d-%H%M%S)"
mkdir -m 700 "$BACKUP"
git -C "$TARGET" archive HEAD | gzip > "$BACKUP/tracked-source.tar.gz"
# Move old executable source out of the Git/workspace root. Preserve .git,
# .env, vendor, node_modules, runtime storage and uploads. No old routes/views
# are merged into the new application.
for path in app bootstrap config database public resources routes tests migration verification deploy src .github; do
 [[ ! -e "$TARGET/$path" ]] || mv "$TARGET/$path" "$BACKUP/$path"
done
for path in artisan composer.json composer.lock package.json package-lock.json vite.config.js vite.config.ts phpunit.xml README.md COVERAGE.md INVENTORY.md .env.example .gitignore; do
 [[ ! -e "$TARGET/$path" ]] || mv "$TARGET/$path" "$BACKUP/$path"
done
rsync -a --exclude='.git/' --exclude='.env' --exclude='vendor/' --exclude='node_modules/' --exclude='storage/' "$SOURCE/" "$TARGET/"
git -C "$TARGET" add -A -- .
echo "Overlay applied to a NEW branch: $BRANCH"
echo "Original files retained in: $BACKUP"
echo 'Review git status, commit and push this release branch. This script does not push or deploy.'
