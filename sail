#!/bin/bash

# ScreenBuddy Docker Command Wrapper
# Usage: ./sail <command>
# Examples:
#   ./sail artisan migrate
#   ./sail artisan videos:convert-hls
#   ./sail composer install
#   ./sail npm run build

cd "$(dirname "$0")"

CONTAINER="screensense-app"

# Check if Docker is running
if ! docker ps &> /dev/null; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Check if container is running
if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
    echo "❌ Container '${CONTAINER}' is not running."
    echo "   Run: docker compose up -d"
    exit 1
fi

# Handle different command types
case "$1" in
    artisan|art)
        shift
        docker exec -it "$CONTAINER" php artisan "$@"
        ;;
    composer)
        shift
        docker exec -it "$CONTAINER" composer "$@"
        ;;
    php)
        shift
        docker exec -it "$CONTAINER" php "$@"
        ;;
    shell|bash|sh)
        docker exec -it "$CONTAINER" bash
        ;;
    test)
        shift
        docker exec -it "$CONTAINER" php artisan test "$@"
        ;;
    pint)
        shift
        docker exec -it "$CONTAINER" ./vendor/bin/pint "$@"
        ;;
    tinker)
        docker exec -it "$CONTAINER" php artisan tinker
        ;;
    *)
        # Default: run as-is
        docker exec -it "$CONTAINER" "$@"
        ;;
esac
