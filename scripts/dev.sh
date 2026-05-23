#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
source ./scripts/use-herd-php.sh

echo "PHP: $(php -v | head -1)"
echo "Site: http://jobflowmu.test (Herd — php artisan serve not required)"
echo "Starting Vite..."
npm run dev
