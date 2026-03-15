#!/usr/bin/env bash
set -euo pipefail

REPOS_DIR="$(cd "$(dirname "$0")" && pwd)/docs/repos"

find "$REPOS_DIR" -name .git -type d | while read -r gitdir; do
    repo="$(dirname "$gitdir")"
    echo "Pulling ${repo#"$REPOS_DIR"/}..."
    git -C "$repo" pull
done