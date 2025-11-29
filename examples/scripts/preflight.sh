#!/bin/bash
#
# AutoERP Pre-Flight Check Script
# Run this before every commit and before marking a task as complete.
#
# Usage: ./scripts/preflight.sh
#

set -e  # Exit on first error

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🔍 AutoERP Pre-Flight Checks${NC}"
echo "================================"

# Track if we're in the root directory
if [ ! -f "pnpm-workspace.yaml" ]; then
    echo -e "${RED}❌ Error: Must run from project root${NC}"
    exit 1
fi

# ============================================
# Backend Checks
# ============================================
echo -e "\n${YELLOW}📦 Backend Checks${NC}"

cd apps/api

echo -n "  Code Style (Pint)... "
if ./vendor/bin/pint --test > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo "  Run: cd apps/api && ./vendor/bin/pint"
    exit 1
fi

echo -n "  Static Analysis (PHPStan L8)... "
if ./vendor/bin/phpstan analyse --level=8 --no-progress > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    ./vendor/bin/phpstan analyse --level=8 --no-progress
    exit 1
fi

echo -n "  Module Boundaries (Deptrac)... "
if ./vendor/bin/deptrac analyse --no-progress > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    ./vendor/bin/deptrac analyse
    exit 1
fi

echo -n "  Tests (PHPUnit)... "
if php artisan test --parallel --no-coverage > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    php artisan test --parallel
    exit 1
fi

# ============================================
# Type Generation
# ============================================
echo -e "\n${YELLOW}🔄 Type Generation${NC}"

echo -n "  TypeScript Transform... "
if php artisan typescript:transform > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    php artisan typescript:transform
    exit 1
fi

cd ../..

# ============================================
# Frontend Checks
# ============================================
echo -e "\n${YELLOW}🌐 Frontend Checks${NC}"

cd apps/web

echo -n "  TypeScript Check... "
if pnpm typecheck > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    pnpm typecheck
    exit 1
fi

echo -n "  ESLint... "
if pnpm lint > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    pnpm lint
    exit 1
fi

echo -n "  Tests (Vitest)... "
if pnpm test --run > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    pnpm test --run
    exit 1
fi

cd ../..

# ============================================
# Summary
# ============================================
echo -e "\n================================"
echo -e "${GREEN}✅ All pre-flight checks passed!${NC}"
echo -e "================================"
echo ""
echo "Safe to commit. Remember to:"
echo "  1. Update TASKS.md (mark task as [x])"
echo "  2. Use conventional commit: feat(module): description"
