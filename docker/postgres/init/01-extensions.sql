-- AutoERP PostgreSQL Initialization
-- This script runs automatically when the container is first created

-- Enable TimescaleDB extension for time-series audit logs
CREATE EXTENSION IF NOT EXISTS timescaledb;

-- Enable UUID generation
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Enable trigram similarity for fuzzy search
CREATE EXTENSION IF NOT EXISTS pg_trgm;

-- Enable unaccent for search normalization
CREATE EXTENSION IF NOT EXISTS unaccent;

-- Log successful initialization
DO $$
BEGIN
    RAISE NOTICE 'AutoERP PostgreSQL extensions initialized successfully';
END $$;
