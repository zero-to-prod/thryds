#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPOS_DIR="$SCRIPT_DIR/docs/repos"
COMPOSER_JSON="$SCRIPT_DIR/composer.json"
COMPOSER_LOCK="$SCRIPT_DIR/composer.lock"

# Arbitrary repos not derived from composer.json
EXTRA_REPOS=(
    "laravel/docs"
)

# Extract GitHub org/repo for each direct dependency via composer.lock source URLs
composer_repos() {
    python3 -c "
import json, re

with open('$COMPOSER_JSON') as f:
    composer = json.load(f)

direct = set()
for key in ('require', 'require-dev'):
    for pkg in composer.get(key, {}):
        if pkg != 'php':
            direct.add(pkg)

with open('$COMPOSER_LOCK') as f:
    lock = json.load(f)

for pkg in lock.get('packages', []) + lock.get('packages-dev', []):
    if pkg['name'] in direct:
        url = pkg.get('source', {}).get('url', '')
        match = re.search(r'github\.com[/:]([^/]+/[^/.]+)', url)
        if match:
            print(match.group(1))
"
}

all_repos() {
    composer_repos
    for repo in "${EXTRA_REPOS[@]}"; do
        echo "$repo"
    done
}

install() {
    while IFS= read -r repo; do
        local target="$REPOS_DIR/$repo"
        if [ -d "$target/.git" ]; then
            echo "Already installed: $repo"
        else
            echo "Cloning $repo..."
            mkdir -p "$(dirname "$target")"
            git clone --depth 1 "https://github.com/$repo.git" "$target"
        fi
    done < <(all_repos)
}

update() {
    while IFS= read -r repo; do
        local target="$REPOS_DIR/$repo"
        if [ -d "$target/.git" ]; then
            echo "Pulling $repo..."
            git -C "$target" pull --ff-only
        else
            echo "Not installed: $repo (run ./docs.sh install)"
        fi
    done < <(all_repos)
}

case "${1-}" in
    install) install ;;
    update)  update ;;
    *)
        echo "Usage: ./docs.sh {install|update}"
        exit 1
        ;;
esac
