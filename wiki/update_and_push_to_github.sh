#!/usr/bin/env bash
set -euo pipefail

SCRIPT_NAME="update_and_push_to_github.sh"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCRIPT="${SCRIPT_DIR}/../../../../../../.scripts/push-to-github.sh"

URL="git@github.com:Mondial-IT/bm_drupal11_enhancement_modules.wiki.git"
DIR="wiki"
BRANCH="master"

COMMIT_MESSAGE="${1:-}"
shift || true

# Call the central script, passing the message explicitly and any remaining args.
echo "Running ${SCRIPT_NAME} via ${SCRIPT}"
bash "${SCRIPT}" \
  --url "$URL" \
  --dir "$DIR" \
  --branch "$BRANCH" \
  --message "$COMMIT_MESSAGE" \
  "$@"
