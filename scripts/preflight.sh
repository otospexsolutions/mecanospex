#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🔍 Running preflight checks...${NC}"
echo ""

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ROOT_DIR="$( cd "$SCRIPT_DIR/.." && pwd )"

# Backend checks
echo -e "${YELLOW}📦 Backend Checks${NC}"
echo "=================================="

cd "$ROOT_DIR/apps/api"

echo -e "\n${YELLOW}Running Pint (code style)...${NC}"
./vendor/bin/pint --test
echo -e "${GREEN}✓ Pint passed${NC}"

echo -e "\n${YELLOW}Running PHPStan (static analysis)...${NC}"
./vendor/bin/phpstan analyse --level=8 --memory-limit=512M
echo -e "${GREEN}✓ PHPStan passed${NC}"

echo -e "\n${YELLOW}Running PHPUnit tests...${NC}"
php artisan test
echo -e "${GREEN}✓ PHPUnit passed${NC}"

# Type Generation
echo -e "\n${YELLOW}Generating TypeScript types...${NC}"
if php artisan typescript:transform 2>/dev/null; then
    echo -e "${GREEN}✓ TypeScript types generated${NC}"
else
    echo -e "${YELLOW}⚠ TypeScript transformer not configured yet (skipping)${NC}"
fi

# Frontend checks
echo -e "\n${YELLOW}🌐 Frontend Checks${NC}"
echo "=================================="

cd "$ROOT_DIR/apps/web"

echo -e "\n${YELLOW}Running TypeScript check...${NC}"
pnpm typecheck
echo -e "${GREEN}✓ TypeScript passed${NC}"

echo -e "\n${YELLOW}Running ESLint...${NC}"
pnpm lint
echo -e "${GREEN}✓ ESLint passed${NC}"

echo -e "\n${YELLOW}Running Vitest tests...${NC}"
pnpm test
echo -e "${GREEN}✓ Vitest passed${NC}"

# Summary
echo ""
echo "=================================="
echo -e "${GREEN}✅ All preflight checks passed!${NC}"
echo "=================================="
