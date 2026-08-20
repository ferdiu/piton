#!/usr/bin/env bash
#
# test-db.sh — manage the throwaway MySQL instance used by the test suite.
#
# The container is ephemeral ON PURPOSE (no volume): every start is a clean
# slate. Tests expect MySQL at 127.0.0.1:3306 with database "test" and
# credentials test/test — the same coordinates as CI's MySQL service.
#
# Container engine: podman is preferred; docker is used as fallback.
#
# Usage: bin/test-db.sh {start|stop|restart}

set -euo pipefail

CONTAINER_NAME="piton-test-mysql"
IMAGE="docker.io/library/mysql:latest"
# Override with e.g. BIND_ADDR=0.0.0.0 when the test runner lives in a
# different network namespace (sandbox) and reaches the host via a gateway.
BIND_ADDR="${BIND_ADDR:-127.0.0.1}"
PORT="3306"
ROOT_PASSWORD="root"
DB_NAME="test"
DB_USER="test"
DB_PASSWORD="test"
READY_TIMEOUT_SECONDS=60

# --log-bin-trust-function-creators=1 mirrors conf/management-db.cnf (production):
# required so the test user can run the raw-SQL trigger migration
# (2025_05_21_172625_create_monetary_account_trigger) without SUPER privilege.
#
# The remaining flags are throwaway-DB performance settings (crash-unsafe, fine
# for ephemeral tests): binlog off and relaxed fsync semantics. Without them the
# suite is pathologically slow (e.g. MetalSeeder::addHistory issues ~3.8k
# single-row inserts, each one a full sync commit: ~45s per test).

if command -v podman >/dev/null 2>&1; then
    ENGINE="podman"
elif command -v docker >/dev/null 2>&1; then
    ENGINE="docker"
else
    echo "ERROR: neither podman nor docker found in PATH." >&2
    exit 1
fi

is_running() {
    [ "$("${ENGINE}" ps -q -f "name=^${CONTAINER_NAME}$")" != "" ]
}

exists() {
    [ "$("${ENGINE}" ps -aq -f "name=^${CONTAINER_NAME}$")" != "" ]
}

wait_ready() {
    echo "Waiting for MySQL to accept connections (timeout ${READY_TIMEOUT_SECONDS}s)..."
    local elapsed=0
    until "${ENGINE}" exec "${CONTAINER_NAME}" \
            mysqladmin ping -h 127.0.0.1 -u root --password="${ROOT_PASSWORD}" --silent 2>/dev/null; do
        sleep 1
        elapsed=$((elapsed + 1))
        if [ "${elapsed}" -ge "${READY_TIMEOUT_SECONDS}" ]; then
            echo "ERROR: MySQL did not become ready within ${READY_TIMEOUT_SECONDS}s." >&2
            "${ENGINE}" logs --tail 20 "${CONTAINER_NAME}" >&2 || true
            exit 1
        fi
    done
}

cmd_start() {
    if is_running; then
        echo "Container '${CONTAINER_NAME}' is already running."
    else
        if exists; then
            echo "Removing stopped container '${CONTAINER_NAME}' (ephemeral, no data is kept)..."
            "${ENGINE}" rm "${CONTAINER_NAME}" >/dev/null
        fi
        echo "Starting '${CONTAINER_NAME}' (${IMAGE} via ${ENGINE}) on ${BIND_ADDR}:${PORT}..."
        "${ENGINE}" run -d --name "${CONTAINER_NAME}" \
            -e "MYSQL_ROOT_PASSWORD=${ROOT_PASSWORD}" \
            -e "MYSQL_DATABASE=${DB_NAME}" \
            -e "MYSQL_USER=${DB_USER}" \
            -e "MYSQL_PASSWORD=${DB_PASSWORD}" \
            -p "${BIND_ADDR}:${PORT}:3306" \
            "${IMAGE}" \
            --log-bin-trust-function-creators=1 \
            --skip-log-bin \
            --innodb-flush-log-at-trx-commit=0 \
            --sync-binlog=0 \
            --innodb-doublewrite=0 >/dev/null
    fi
    wait_ready
    echo "Ready. Test database coordinates:"
    echo "  host=${BIND_ADDR} port=${PORT} database=${DB_NAME} user=${DB_USER} password=${DB_PASSWORD}"
}

cmd_stop() {
    if ! exists; then
        echo "Container '${CONTAINER_NAME}' does not exist — nothing to stop."
        return 0
    fi
    if is_running; then
        echo "Stopping '${CONTAINER_NAME}'..."
        "${ENGINE}" stop "${CONTAINER_NAME}" >/dev/null
    fi
    echo "Removing '${CONTAINER_NAME}' (ephemeral, no data is kept)..."
    "${ENGINE}" rm "${CONTAINER_NAME}" >/dev/null
    echo "Stopped."
}

case "${1:-}" in
    start)   cmd_start ;;
    stop)    cmd_stop ;;
    restart) cmd_stop; cmd_start ;;
    *)
        echo "Usage: $0 {start|stop|restart}" >&2
        exit 1
        ;;
esac
