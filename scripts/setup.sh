#!/bin/bash
set -e

# =============================================================================
# AutoERP First-Time Setup Script
# =============================================================================
# This script initializes the development environment from scratch.
# Run this once after cloning the repository.
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# -----------------------------------------------------------------------------
# Pre-flight checks
# -----------------------------------------------------------------------------
check_dependencies() {
    log_info "Checking dependencies..."

    local missing=()

    if ! command -v docker &> /dev/null; then
        missing+=("docker")
    fi

    if ! command -v docker compose &> /dev/null; then
        missing+=("docker compose")
    fi

    if ! command -v pnpm &> /dev/null; then
        missing+=("pnpm")
    fi

    if ! command -v php &> /dev/null; then
        missing+=("php")
    fi

    if ! command -v composer &> /dev/null; then
        missing+=("composer")
    fi

    if [ ${#missing[@]} -ne 0 ]; then
        log_error "Missing required dependencies: ${missing[*]}"
        log_info "Please install the missing dependencies and try again."
        exit 1
    fi

    log_success "All dependencies found"
}

# -----------------------------------------------------------------------------
# Environment setup
# -----------------------------------------------------------------------------
setup_env() {
    log_info "Setting up environment files..."

    cd "$PROJECT_ROOT"

    if [ ! -f .env ]; then
        cp .env.example .env
        log_success "Created .env from .env.example"
    else
        log_warning ".env already exists, skipping"
    fi

    if [ -d apps/api ] && [ ! -f apps/api/.env ]; then
        if [ -f apps/api/.env.example ]; then
            cp apps/api/.env.example apps/api/.env
            log_success "Created apps/api/.env from .env.example"
        fi
    fi
}

# -----------------------------------------------------------------------------
# Docker services
# -----------------------------------------------------------------------------
start_docker_services() {
    log_info "Starting Docker services..."

    cd "$PROJECT_ROOT"

    docker compose up -d

    log_info "Waiting for services to be healthy..."

    local max_attempts=30
    local attempt=1

    while [ $attempt -le $max_attempts ]; do
        local healthy_count=$(docker compose ps --format json 2>/dev/null | grep -c '"Health":"healthy"' || echo "0")
        local total_services=4

        if [ "$healthy_count" -ge "$total_services" ]; then
            log_success "All Docker services are healthy"
            return 0
        fi

        echo -n "."
        sleep 2
        ((attempt++))
    done

    echo ""
    log_warning "Some services may not be fully healthy yet. Check with: docker compose ps"
}

# -----------------------------------------------------------------------------
# Node dependencies
# -----------------------------------------------------------------------------
install_node_deps() {
    log_info "Installing Node.js dependencies..."

    cd "$PROJECT_ROOT"
    pnpm install

    log_success "Node.js dependencies installed"
}

# -----------------------------------------------------------------------------
# PHP dependencies
# -----------------------------------------------------------------------------
install_php_deps() {
    log_info "Installing PHP dependencies..."

    if [ -d "$PROJECT_ROOT/apps/api" ] && [ -f "$PROJECT_ROOT/apps/api/composer.json" ]; then
        cd "$PROJECT_ROOT/apps/api"
        composer install
        log_success "PHP dependencies installed"
    else
        log_warning "Laravel app not yet initialized (apps/api/composer.json not found)"
        log_info "This will be set up in Task 1.3"
    fi
}

# -----------------------------------------------------------------------------
# Database setup
# -----------------------------------------------------------------------------
setup_database() {
    log_info "Setting up database..."

    if [ -d "$PROJECT_ROOT/apps/api" ] && [ -f "$PROJECT_ROOT/apps/api/artisan" ]; then
        cd "$PROJECT_ROOT/apps/api"
        php artisan migrate --force
        php artisan db:seed --force
        log_success "Database migrated and seeded"
    else
        log_warning "Laravel app not yet initialized, skipping database setup"
    fi
}

# -----------------------------------------------------------------------------
# MinIO bucket setup
# -----------------------------------------------------------------------------
setup_minio() {
    log_info "Setting up MinIO buckets..."

    # Wait for MinIO to be ready
    sleep 2

    # Create default bucket using MinIO client inside container
    docker compose exec -T minio mc alias set local http://localhost:9000 autoerp_minio autoerp_minio_secret 2>/dev/null || true
    docker compose exec -T minio mc mb local/autoerp --ignore-existing 2>/dev/null || true

    log_success "MinIO buckets configured"
}

# -----------------------------------------------------------------------------
# Main
# -----------------------------------------------------------------------------
main() {
    echo ""
    echo "=============================================="
    echo "       AutoERP Development Setup"
    echo "=============================================="
    echo ""

    check_dependencies
    setup_env
    start_docker_services
    install_node_deps
    install_php_deps
    setup_database
    setup_minio

    echo ""
    echo "=============================================="
    log_success "Setup complete!"
    echo "=============================================="
    echo ""
    echo "Next steps:"
    echo "  1. cd apps/api && php artisan serve     # Start Laravel"
    echo "  2. cd apps/web && pnpm dev              # Start React"
    echo "  3. Open http://localhost:8000           # API"
    echo "  4. Open http://localhost:5173           # Web app"
    echo ""
    echo "Service URLs:"
    echo "  - PostgreSQL: localhost:5432"
    echo "  - Redis:      localhost:6379"
    echo "  - Meilisearch: http://localhost:7700"
    echo "  - MinIO API:   http://localhost:9000"
    echo "  - MinIO Console: http://localhost:9001"
    echo ""
}

main "$@"
