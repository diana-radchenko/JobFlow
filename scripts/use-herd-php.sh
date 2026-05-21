#!/usr/bin/env bash
# Git Bash: put Herd php84/php.exe before XAMPP in PATH (do not use php.bat).
# Usage: source ./scripts/use-herd-php.sh

HERD_BIN="${HOME}/.config/herd/bin"
HERD_PHP84="${HERD_BIN}/php84"

if [[ ! -x "${HERD_PHP84}/php.exe" ]]; then
  echo "Herd PHP not found at: ${HERD_PHP84}/php.exe" >&2
  echo "Install Herd: https://herd.laravel.com/windows" >&2
  return 1 2>/dev/null || exit 1
fi

export PATH="${HERD_PHP84}:${HERD_BIN}:${PATH}"
hash -r 2>/dev/null || true

echo "Using: $("${HERD_PHP84}/php.exe" -v 2>/dev/null | head -1)"
