#!/usr/bin/env bash
set -euo pipefail

# Usage: ./scripts/opcache-load-test.sh [requests] [concurrency] [port]
REQUESTS=${1:-500}
CONCURRENCY=${2:-10}
PORT=${3:-8888}
BASE_URL="http://localhost:${PORT}"

echo "=== OPcache Load Test ==="
echo ""

# 1. Verify the app is up
echo "Checking app is running on ${BASE_URL}..."
if ! curl -sf -o /dev/null "${BASE_URL}/"; then
    echo "ERROR: App not responding at ${BASE_URL}. Is 'docker compose up -d' running?"
    exit 1
fi
echo "App is up."
echo ""

# 2. Warm-up: hit every route once
echo "Warming up routes..."
curl -sf -o /dev/null "${BASE_URL}/"
curl -sf -o /dev/null "${BASE_URL}/about"
echo "Done."
echo ""

# 3. Load test
echo "Running load test: ${REQUESTS} requests, ${CONCURRENCY} concurrent..."
echo ""
ab -n "${REQUESTS}" -c "${CONCURRENCY}" -q "${BASE_URL}/" 2>&1 | grep -E '(Requests per second|Time per request|Transfer rate|Failed requests|Complete requests)'
echo ""

# 4. Fetch OPcache status from inside the worker
echo "Fetching OPcache metrics from worker process..."
echo ""
curl -sf "${BASE_URL}/_opcache" 2>/dev/null || echo "ERROR: /_opcache route not found. Did you register the diagnostic route?"