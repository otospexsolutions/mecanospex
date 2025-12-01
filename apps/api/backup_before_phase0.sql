pg_dump: warning: there are circular foreign-key constraints on this table:
pg_dump: detail: hypertable
pg_dump: hint: You might not be able to restore the dump without using --disable-triggers or temporarily dropping the constraints.
pg_dump: hint: Consider using a full dump instead of a --data-only dump to avoid this problem.
pg_dump: warning: there are circular foreign-key constraints on this table:
pg_dump: detail: chunk
pg_dump: hint: You might not be able to restore the dump without using --disable-triggers or temporarily dropping the constraints.
pg_dump: hint: Consider using a full dump instead of a --data-only dump to avoid this problem.
pg_dump: warning: there are circular foreign-key constraints on this table:
pg_dump: detail: continuous_agg
pg_dump: hint: You might not be able to restore the dump without using --disable-triggers or temporarily dropping the constraints.
pg_dump: hint: Consider using a full dump instead of a --data-only dump to avoid this problem.
--
-- PostgreSQL database dump
--

\restrict n0bGmdjEbHrfqY34cpgjhWhKi7P6ngvBDD0iiawcPxDRyGItLckDHFiUb9xUC0Y

-- Dumped from database version 16.10
-- Dumped by pg_dump version 16.10

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: timescaledb; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS timescaledb WITH SCHEMA public;


--
-- Name: EXTENSION timescaledb; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION timescaledb IS 'Enables scalable inserts and complex queries for time-series data (Community Edition)';


--
-- Name: pg_trgm; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_trgm WITH SCHEMA public;


--
-- Name: EXTENSION pg_trgm; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_trgm IS 'text similarity measurement and index searching based on trigrams';


--
-- Name: unaccent; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS unaccent WITH SCHEMA public;


--
-- Name: EXTENSION unaccent; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION unaccent IS 'text search dictionary that removes accents';


--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA public;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION "uuid-ossp" IS 'generate universally unique identifiers (UUIDs)';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: accounts; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.accounts (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    parent_id uuid,
    code character varying(20) NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(20) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    is_system boolean DEFAULT false NOT NULL,
    balance numeric(19,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.accounts OWNER TO autoerp;

--
-- Name: audit_events; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.audit_events (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    user_id uuid,
    event_type character varying(100) NOT NULL,
    aggregate_type character varying(100) NOT NULL,
    aggregate_id character varying(100) NOT NULL,
    payload jsonb DEFAULT '{}'::jsonb NOT NULL,
    metadata jsonb DEFAULT '{}'::jsonb NOT NULL,
    event_hash character varying(64) NOT NULL,
    occurred_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.audit_events OWNER TO autoerp;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO autoerp;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO autoerp;

--
-- Name: devices; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.devices (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) DEFAULT 'desktop'::character varying NOT NULL,
    device_id character varying(255),
    push_token character varying(255),
    platform character varying(255),
    platform_version character varying(255),
    app_version character varying(255),
    is_trusted boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    last_used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.devices OWNER TO autoerp;

--
-- Name: document_lines; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.document_lines (
    id uuid NOT NULL,
    document_id uuid NOT NULL,
    product_id uuid,
    line_number smallint NOT NULL,
    description text NOT NULL,
    quantity numeric(15,4) NOT NULL,
    unit_price numeric(15,2) NOT NULL,
    discount_percent numeric(5,2),
    discount_amount numeric(15,2),
    tax_rate numeric(5,2),
    line_total numeric(15,2) NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.document_lines OWNER TO autoerp;

--
-- Name: document_sequences; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.document_sequences (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    type character varying(20) NOT NULL,
    year smallint NOT NULL,
    last_number integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.document_sequences OWNER TO autoerp;

--
-- Name: documents; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.documents (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    partner_id uuid NOT NULL,
    vehicle_id uuid,
    type character varying(20) NOT NULL,
    status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    document_number character varying(50) NOT NULL,
    document_date date NOT NULL,
    due_date date,
    valid_until date,
    currency character varying(3) DEFAULT 'EUR'::character varying NOT NULL,
    subtotal numeric(15,2),
    discount_amount numeric(15,2),
    tax_amount numeric(15,2),
    total numeric(15,2),
    balance_due numeric(15,2),
    notes text,
    internal_notes text,
    reference character varying(100),
    source_document_id uuid,
    payload jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    fiscal_hash character varying(64),
    previous_hash character varying(64),
    chain_sequence bigint
);


ALTER TABLE public.documents OWNER TO autoerp;

--
-- Name: domains; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.domains (
    id uuid NOT NULL,
    domain character varying(255) NOT NULL,
    tenant_id uuid NOT NULL,
    is_primary boolean DEFAULT false NOT NULL,
    is_verified boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.domains OWNER TO autoerp;

--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO autoerp;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: autoerp
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO autoerp;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: autoerp
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: import_jobs; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.import_jobs (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    user_id uuid NOT NULL,
    type character varying(50) NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying NOT NULL,
    original_filename character varying(255) NOT NULL,
    file_path character varying(500) NOT NULL,
    total_rows integer DEFAULT 0 NOT NULL,
    processed_rows integer DEFAULT 0 NOT NULL,
    successful_rows integer DEFAULT 0 NOT NULL,
    failed_rows integer DEFAULT 0 NOT NULL,
    column_mapping jsonb,
    options jsonb,
    error_message text,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.import_jobs OWNER TO autoerp;

--
-- Name: import_rows; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.import_rows (
    id uuid NOT NULL,
    import_job_id uuid NOT NULL,
    row_number integer NOT NULL,
    data jsonb NOT NULL,
    is_valid boolean DEFAULT false NOT NULL,
    errors jsonb,
    is_imported boolean DEFAULT false NOT NULL,
    imported_entity_id uuid,
    import_error text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.import_rows OWNER TO autoerp;

--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO autoerp;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO autoerp;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: autoerp
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO autoerp;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: autoerp
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: journal_entries; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.journal_entries (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    entry_number character varying(255) NOT NULL,
    entry_date date NOT NULL,
    description text,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    source_type character varying(255),
    source_id uuid,
    hash character varying(255),
    previous_hash character varying(255),
    posted_at timestamp(0) without time zone,
    posted_by uuid,
    reversed_at timestamp(0) without time zone,
    reversed_by uuid,
    reversal_entry_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    chain_sequence bigint
);


ALTER TABLE public.journal_entries OWNER TO autoerp;

--
-- Name: journal_lines; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.journal_lines (
    id uuid NOT NULL,
    journal_entry_id uuid NOT NULL,
    account_id uuid NOT NULL,
    debit numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    credit numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    description text,
    line_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.journal_lines OWNER TO autoerp;

--
-- Name: locations; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.locations (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    code character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    address text,
    is_active boolean DEFAULT true NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.locations OWNER TO autoerp;

--
-- Name: migrations; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO autoerp;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: autoerp
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO autoerp;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: autoerp
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id uuid NOT NULL,
    tenant_id uuid NOT NULL
);


ALTER TABLE public.model_has_permissions OWNER TO autoerp;

--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id uuid NOT NULL,
    tenant_id uuid NOT NULL
);


ALTER TABLE public.model_has_roles OWNER TO autoerp;

--
-- Name: partners; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.partners (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(20) NOT NULL,
    code character varying(50),
    email character varying(255),
    phone character varying(50),
    country_code character varying(2),
    vat_number character varying(50),
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.partners OWNER TO autoerp;

--
-- Name: payment_allocations; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.payment_allocations (
    id uuid NOT NULL,
    payment_id uuid NOT NULL,
    document_id uuid NOT NULL,
    amount numeric(15,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payment_allocations OWNER TO autoerp;

--
-- Name: payment_instruments; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.payment_instruments (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    payment_method_id uuid NOT NULL,
    reference character varying(100) NOT NULL,
    partner_id uuid,
    drawer_name character varying(150),
    amount numeric(15,2) NOT NULL,
    currency character varying(3) DEFAULT 'TND'::character varying NOT NULL,
    received_date date NOT NULL,
    maturity_date date,
    expiry_date date,
    status character varying(30) DEFAULT 'received'::character varying NOT NULL,
    repository_id uuid,
    bank_name character varying(100),
    bank_branch character varying(100),
    bank_account character varying(50),
    deposited_at timestamp(0) without time zone,
    deposited_to_id uuid,
    cleared_at timestamp(0) without time zone,
    bounced_at timestamp(0) without time zone,
    bounce_reason character varying(255),
    payment_id uuid,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payment_instruments OWNER TO autoerp;

--
-- Name: payment_methods; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.payment_methods (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    code character varying(30) NOT NULL,
    name character varying(100) NOT NULL,
    is_physical boolean DEFAULT false NOT NULL,
    has_maturity boolean DEFAULT false NOT NULL,
    requires_third_party boolean DEFAULT false NOT NULL,
    is_push boolean DEFAULT true NOT NULL,
    has_deducted_fees boolean DEFAULT false NOT NULL,
    is_restricted boolean DEFAULT false NOT NULL,
    fee_type character varying(20),
    fee_fixed numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    fee_percent numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    restriction_type character varying(50),
    default_journal_id uuid,
    default_account_id uuid,
    fee_account_id uuid,
    is_active boolean DEFAULT true NOT NULL,
    "position" integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payment_methods OWNER TO autoerp;

--
-- Name: payment_repositories; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.payment_repositories (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    code character varying(30) NOT NULL,
    name character varying(100) NOT NULL,
    type character varying(30) NOT NULL,
    bank_name character varying(100),
    account_number character varying(50),
    iban character varying(50),
    bic character varying(20),
    balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    last_reconciled_at timestamp(0) without time zone,
    last_reconciled_balance numeric(15,2),
    location_id uuid,
    responsible_user_id uuid,
    account_id uuid,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payment_repositories OWNER TO autoerp;

--
-- Name: payments; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.payments (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    partner_id uuid NOT NULL,
    payment_method_id uuid NOT NULL,
    instrument_id uuid,
    repository_id uuid,
    amount numeric(15,2) NOT NULL,
    currency character varying(3) DEFAULT 'TND'::character varying NOT NULL,
    payment_date date NOT NULL,
    status character varying(30) DEFAULT 'pending'::character varying NOT NULL,
    reference character varying(100),
    notes text,
    journal_entry_id uuid,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payments OWNER TO autoerp;

--
-- Name: permissions; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.permissions OWNER TO autoerp;

--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: autoerp
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permissions_id_seq OWNER TO autoerp;

--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: autoerp
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id uuid NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO autoerp;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: autoerp
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.personal_access_tokens_id_seq OWNER TO autoerp;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: autoerp
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.products (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    name character varying(255) NOT NULL,
    sku character varying(100) NOT NULL,
    type character varying(20) NOT NULL,
    description text,
    sale_price numeric(15,2),
    purchase_price numeric(15,2),
    tax_rate numeric(5,2),
    unit character varying(50),
    barcode character varying(100),
    is_active boolean DEFAULT true NOT NULL,
    oem_numbers json,
    cross_references json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.products OWNER TO autoerp;

--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


ALTER TABLE public.role_has_permissions OWNER TO autoerp;

--
-- Name: roles; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    tenant_id uuid,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.roles OWNER TO autoerp;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: autoerp
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO autoerp;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: autoerp
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: snapshots; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.snapshots (
    id bigint NOT NULL,
    aggregate_uuid uuid NOT NULL,
    aggregate_version bigint NOT NULL,
    state jsonb NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.snapshots OWNER TO autoerp;

--
-- Name: snapshots_id_seq; Type: SEQUENCE; Schema: public; Owner: autoerp
--

CREATE SEQUENCE public.snapshots_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.snapshots_id_seq OWNER TO autoerp;

--
-- Name: snapshots_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: autoerp
--

ALTER SEQUENCE public.snapshots_id_seq OWNED BY public.snapshots.id;


--
-- Name: stock_levels; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.stock_levels (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    product_id uuid NOT NULL,
    location_id uuid NOT NULL,
    quantity numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    reserved numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    min_quantity numeric(15,2),
    max_quantity numeric(15,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.stock_levels OWNER TO autoerp;

--
-- Name: stock_movements; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.stock_movements (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    product_id uuid NOT NULL,
    location_id uuid NOT NULL,
    movement_type character varying(255) NOT NULL,
    quantity numeric(15,2) NOT NULL,
    quantity_before numeric(15,2) NOT NULL,
    quantity_after numeric(15,2) NOT NULL,
    reference character varying(255),
    notes text,
    user_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.stock_movements OWNER TO autoerp;

--
-- Name: stored_events; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.stored_events (
    id bigint NOT NULL,
    aggregate_uuid uuid,
    aggregate_version bigint,
    event_version smallint DEFAULT '1'::smallint NOT NULL,
    event_class character varying(255) NOT NULL,
    event_properties jsonb NOT NULL,
    meta_data jsonb NOT NULL,
    created_at timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.stored_events OWNER TO autoerp;

--
-- Name: stored_events_id_seq; Type: SEQUENCE; Schema: public; Owner: autoerp
--

CREATE SEQUENCE public.stored_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.stored_events_id_seq OWNER TO autoerp;

--
-- Name: stored_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: autoerp
--

ALTER SEQUENCE public.stored_events_id_seq OWNED BY public.stored_events.id;


--
-- Name: tenants; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.tenants (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    plan character varying(255) DEFAULT 'trial'::character varying NOT NULL,
    tax_id character varying(255),
    country_code character varying(2),
    currency_code character varying(3),
    settings jsonb DEFAULT '{}'::jsonb NOT NULL,
    data jsonb,
    trial_ends_at timestamp(0) without time zone,
    subscription_ends_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.tenants OWNER TO autoerp;

--
-- Name: users; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.users (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    phone character varying(255),
    password character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending_verification'::character varying NOT NULL,
    locale character varying(10),
    timezone character varying(50),
    preferences jsonb DEFAULT '{}'::jsonb NOT NULL,
    email_verified_at timestamp(0) without time zone,
    last_login_at timestamp(0) without time zone,
    last_login_ip character varying(45),
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.users OWNER TO autoerp;

--
-- Name: vehicles; Type: TABLE; Schema: public; Owner: autoerp
--

CREATE TABLE public.vehicles (
    id uuid NOT NULL,
    tenant_id uuid NOT NULL,
    partner_id uuid,
    license_plate character varying(20) NOT NULL,
    brand character varying(100) NOT NULL,
    model character varying(100) NOT NULL,
    year smallint,
    color character varying(50),
    mileage integer,
    vin character varying(17),
    engine_code character varying(50),
    fuel_type character varying(30),
    transmission character varying(30),
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.vehicles OWNER TO autoerp;

--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: snapshots id; Type: DEFAULT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.snapshots ALTER COLUMN id SET DEFAULT nextval('public.snapshots_id_seq'::regclass);


--
-- Name: stored_events id; Type: DEFAULT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stored_events ALTER COLUMN id SET DEFAULT nextval('public.stored_events_id_seq'::regclass);


--
-- Data for Name: hypertable; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.hypertable (id, schema_name, table_name, associated_schema_name, associated_table_prefix, num_dimensions, chunk_sizing_func_schema, chunk_sizing_func_name, chunk_target_size, compression_state, compressed_hypertable_id, status) FROM stdin;
\.


--
-- Data for Name: chunk; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.chunk (id, hypertable_id, schema_name, table_name, compressed_chunk_id, dropped, status, osm_chunk, creation_time) FROM stdin;
\.


--
-- Data for Name: chunk_column_stats; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.chunk_column_stats (id, hypertable_id, chunk_id, column_name, range_start, range_end, valid) FROM stdin;
\.


--
-- Data for Name: dimension; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.dimension (id, hypertable_id, column_name, column_type, aligned, num_slices, partitioning_func_schema, partitioning_func, interval_length, compress_interval_length, integer_now_func_schema, integer_now_func) FROM stdin;
\.


--
-- Data for Name: dimension_slice; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.dimension_slice (id, dimension_id, range_start, range_end) FROM stdin;
\.


--
-- Data for Name: chunk_constraint; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.chunk_constraint (chunk_id, dimension_slice_id, constraint_name, hypertable_constraint_name) FROM stdin;
\.


--
-- Data for Name: compression_chunk_size; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.compression_chunk_size (chunk_id, compressed_chunk_id, uncompressed_heap_size, uncompressed_toast_size, uncompressed_index_size, compressed_heap_size, compressed_toast_size, compressed_index_size, numrows_pre_compression, numrows_post_compression, numrows_frozen_immediately) FROM stdin;
\.


--
-- Data for Name: compression_settings; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.compression_settings (relid, compress_relid, segmentby, orderby, orderby_desc, orderby_nullsfirst, index) FROM stdin;
\.


--
-- Data for Name: continuous_agg; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.continuous_agg (mat_hypertable_id, raw_hypertable_id, parent_mat_hypertable_id, user_view_schema, user_view_name, partial_view_schema, partial_view_name, direct_view_schema, direct_view_name, materialized_only, finalized) FROM stdin;
\.


--
-- Data for Name: continuous_agg_migrate_plan; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.continuous_agg_migrate_plan (mat_hypertable_id, start_ts, end_ts, user_view_definition) FROM stdin;
\.


--
-- Data for Name: continuous_agg_migrate_plan_step; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.continuous_agg_migrate_plan_step (mat_hypertable_id, step_id, status, start_ts, end_ts, type, config) FROM stdin;
\.


--
-- Data for Name: continuous_aggs_bucket_function; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.continuous_aggs_bucket_function (mat_hypertable_id, bucket_func, bucket_width, bucket_origin, bucket_offset, bucket_timezone, bucket_fixed_width) FROM stdin;
\.


--
-- Data for Name: continuous_aggs_hypertable_invalidation_log; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.continuous_aggs_hypertable_invalidation_log (hypertable_id, lowest_modified_value, greatest_modified_value) FROM stdin;
\.


--
-- Data for Name: continuous_aggs_invalidation_threshold; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.continuous_aggs_invalidation_threshold (hypertable_id, watermark) FROM stdin;
\.


--
-- Data for Name: continuous_aggs_materialization_invalidation_log; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.continuous_aggs_materialization_invalidation_log (materialization_id, lowest_modified_value, greatest_modified_value) FROM stdin;
\.


--
-- Data for Name: continuous_aggs_materialization_ranges; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.continuous_aggs_materialization_ranges (materialization_id, lowest_modified_value, greatest_modified_value) FROM stdin;
\.


--
-- Data for Name: continuous_aggs_watermark; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.continuous_aggs_watermark (mat_hypertable_id, watermark) FROM stdin;
\.


--
-- Data for Name: metadata; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.metadata (key, value, include_in_telemetry) FROM stdin;
install_timestamp	2025-11-29 23:13:39.496643+00	t
timescaledb_version	2.23.1	f
exported_uuid	9732c925-35eb-4802-a71c-f566743a6705	t
\.


--
-- Data for Name: tablespace; Type: TABLE DATA; Schema: _timescaledb_catalog; Owner: autoerp
--

COPY _timescaledb_catalog.tablespace (id, hypertable_id, tablespace_name) FROM stdin;
\.


--
-- Data for Name: bgw_job; Type: TABLE DATA; Schema: _timescaledb_config; Owner: autoerp
--

COPY _timescaledb_config.bgw_job (id, application_name, schedule_interval, max_runtime, max_retries, retry_period, proc_schema, proc_name, owner, scheduled, fixed_schedule, initial_start, hypertable_id, config, check_schema, check_name, timezone) FROM stdin;
\.


--
-- Data for Name: accounts; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.accounts (id, tenant_id, parent_id, code, name, type, description, is_active, is_system, balance, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: audit_events; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.audit_events (id, tenant_id, user_id, event_type, aggregate_type, aggregate_id, payload, metadata, event_hash, occurred_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: devices; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.devices (id, user_id, name, type, device_id, push_token, platform, platform_version, app_version, is_trusted, is_active, last_used_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: document_lines; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.document_lines (id, document_id, product_id, line_number, description, quantity, unit_price, discount_percent, discount_amount, tax_rate, line_total, notes, created_at, updated_at) FROM stdin;
019ad5c8-7db7-73a4-bc4b-6c0b424b8ebc	019ad5c8-7db5-71e4-87b8-ce1ef25d7d8e	\N	1	Brake Pad Test	3.0000	45.99	\N	\N	20.00	137.97	\N	2025-11-30 17:21:17	2025-11-30 17:21:17
019ad5c8-a061-70a9-9209-e30ce930f07e	019ad5c8-a05c-7062-ba31-d15614ea5cd2	\N	1	Oil Change Service	1.0000	75.00	\N	\N	20.00	75.00	\N	2025-11-30 17:21:26	2025-11-30 17:21:26
019ad5dd-4df1-7145-8e36-cc8551744341	019ad5dd-4def-711f-929d-29e8fb630a76	019ad5c3-3c37-7089-a378-41551f00d3da	1	Test Brake Pad	5.0000	25.00	\N	\N	20.00	125.00	\N	2025-11-30 17:44:01	2025-11-30 17:44:01
019ad6ab-01d3-7041-8c40-bfd9e1097eb7	019ad5c8-3c06-727f-8b46-74e58bfa4053	019ad5c3-3c37-7089-a378-41551f00d3da	1	Brake Pad Test	2.0000	45.99	\N	\N	20.00	91.98	\N	2025-11-30 21:28:42	2025-11-30 21:28:42
019ad6ab-496d-7273-aee6-c0f9e271018a	019ad6ab-4966-72cf-af2a-98b355d339d6	\N	1	fefee	1.0000	10.00	\N	\N	10.00	10.00	\N	2025-11-30 21:29:01	2025-11-30 21:29:01
019ad6ab-9513-71f4-9e03-2fcea7a6c915	019ad6ab-950e-72e2-bb84-6f46e623fc95	\N	1	edede	1.0000	10.00	\N	\N	10.00	10.00	\N	2025-11-30 21:29:20	2025-11-30 21:29:20
\.


--
-- Data for Name: document_sequences; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.document_sequences (id, tenant_id, type, year, last_number, created_at, updated_at) FROM stdin;
019ad5c8-3c03-7308-9edc-a987cce03502	019ad4d5-1102-7139-98a0-b3d59490ee38	quote	2025	1	2025-11-30 17:21:00	2025-11-30 17:21:00
019ad5dd-4dec-7395-b4f5-76ebd7ef7540	019ad4d5-1102-7139-98a0-b3d59490ee38	delivery_note	2025	1	2025-11-30 17:44:01	2025-11-30 17:44:01
019ad5c8-7db2-71e9-ab3c-2e8b3c422192	019ad4d5-1102-7139-98a0-b3d59490ee38	sales_order	2025	2	2025-11-30 17:21:17	2025-11-30 21:29:01
019ad5c8-a055-7105-b811-6c7e1b8474fc	019ad4d5-1102-7139-98a0-b3d59490ee38	invoice	2025	2	2025-11-30 17:21:26	2025-11-30 21:29:20
\.


--
-- Data for Name: documents; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.documents (id, tenant_id, partner_id, vehicle_id, type, status, document_number, document_date, due_date, valid_until, currency, subtotal, discount_amount, tax_amount, total, balance_due, notes, internal_notes, reference, source_document_id, payload, created_at, updated_at, deleted_at, fiscal_hash, previous_hash, chain_sequence) FROM stdin;
019ad5c8-7db5-71e4-87b8-ce1ef25d7d8e	019ad4d5-1102-7139-98a0-b3d59490ee38	21615954-311a-480c-aa10-0858151d477b	\N	sales_order	draft	SO-2025-0001	2025-11-30	\N	\N	EUR	137.97	\N	27.59	165.56	\N	Test sales order	\N	\N	\N	\N	2025-11-30 17:21:17	2025-11-30 17:21:17	\N	\N	\N	\N
019ad5c8-a05c-7062-ba31-d15614ea5cd2	019ad4d5-1102-7139-98a0-b3d59490ee38	21615954-311a-480c-aa10-0858151d477b	\N	invoice	draft	INV-2025-0001	2025-11-30	2025-12-30	\N	EUR	75.00	\N	15.00	90.00	\N	Test invoice	\N	\N	\N	\N	2025-11-30 17:21:26	2025-11-30 17:21:26	\N	\N	\N	\N
019ad5dd-4def-711f-929d-29e8fb630a76	019ad4d5-1102-7139-98a0-b3d59490ee38	21615954-311a-480c-aa10-0858151d477b	\N	delivery_note	draft	DN-2025-0001	2025-11-30	\N	\N	EUR	125.00	\N	25.00	150.00	\N	\N	\N	\N	\N	\N	2025-11-30 17:44:01	2025-11-30 17:44:01	\N	\N	\N	\N
019ad5c8-3c06-727f-8b46-74e58bfa4053	019ad4d5-1102-7139-98a0-b3d59490ee38	21615954-311a-480c-aa10-0858151d477b	\N	quote	draft	QT-2025-0001	2025-11-29	\N	\N	EUR	91.98	\N	18.39	110.37	\N	Test quote from API verification	\N	\N	\N	\N	2025-11-30 17:21:00	2025-11-30 21:28:42	\N	\N	\N	\N
019ad6ab-4966-72cf-af2a-98b355d339d6	019ad4d5-1102-7139-98a0-b3d59490ee38	7d408592-b468-496a-acac-da98275ab35a	\N	sales_order	draft	SO-2025-0002	2025-11-30	\N	\N	EUR	10.00	\N	1.00	11.00	\N	\N	\N	\N	\N	\N	2025-11-30 21:29:01	2025-11-30 21:29:01	\N	\N	\N	\N
019ad6ab-950e-72e2-bb84-6f46e623fc95	019ad4d5-1102-7139-98a0-b3d59490ee38	21615954-311a-480c-aa10-0858151d477b	\N	invoice	draft	INV-2025-0002	2025-11-30	\N	\N	EUR	10.00	\N	1.00	11.00	\N	\N	\N	\N	\N	\N	2025-11-30 21:29:20	2025-11-30 21:29:20	\N	\N	\N	\N
\.


--
-- Data for Name: domains; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.domains (id, domain, tenant_id, is_primary, is_verified, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: import_jobs; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.import_jobs (id, tenant_id, user_id, type, status, original_filename, file_path, total_rows, processed_rows, successful_rows, failed_rows, column_mapping, options, error_message, started_at, completed_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: import_rows; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.import_rows (id, import_job_id, row_number, data, is_valid, errors, is_imported, imported_entity_id, import_error, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: journal_entries; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.journal_entries (id, tenant_id, entry_number, entry_date, description, status, source_type, source_id, hash, previous_hash, posted_at, posted_by, reversed_at, reversed_by, reversal_entry_id, created_at, updated_at, chain_sequence) FROM stdin;
\.


--
-- Data for Name: journal_lines; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.journal_lines (id, journal_entry_id, account_id, debit, credit, description, line_order, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: locations; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.locations (id, tenant_id, code, name, address, is_active, is_default, created_at, updated_at) FROM stdin;
019ad5d5-84dc-714e-9147-739d04066198	019ad4d5-1102-7139-98a0-b3d59490ee38	MAIN	Main Warehouse	\N	t	f	2025-11-30 17:35:31	2025-11-30 17:35:31
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000001_create_cache_table	1
2	0001_01_01_000002_create_jobs_table	1
3	2025_11_29_231806_create_permission_tables	1
4	2025_11_29_234638_create_personal_access_tokens_table	1
5	2025_11_30_000001_create_tenants_table	1
6	2025_11_30_000002_create_domains_table	1
7	2025_11_30_000003_create_users_table	1
8	2025_11_30_000004_create_devices_table	1
9	2025_11_30_052119_create_partners_table	1
10	2025_11_30_052910_create_products_table	1
11	2025_11_30_070000_create_vehicles_table	1
12	2025_11_30_080000_create_documents_table	1
13	2025_11_30_080001_create_document_lines_table	1
14	2025_11_30_080002_create_document_sequences_table	1
15	2025_11_30_090000_create_accounts_table	1
16	2025_11_30_100000_create_journal_entries_table	1
17	2025_11_30_102448_create_stored_events_table	1
18	2025_11_30_102449_create_snapshots_table	1
19	2025_11_30_110000_create_inventory_tables	1
20	2025_11_30_120000_create_treasury_tables	1
21	2025_11_30_130000_add_hash_chain_to_documents	1
22	2025_11_30_140000_create_audit_events_table	1
23	2025_11_30_150000_create_import_tables	1
\.


--
-- Data for Name: model_has_permissions; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.model_has_permissions (permission_id, model_type, model_id, tenant_id) FROM stdin;
\.


--
-- Data for Name: model_has_roles; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.model_has_roles (role_id, model_type, model_id, tenant_id) FROM stdin;
2	App\\Modules\\Identity\\Domain\\User	37a277d6-5099-4870-8d43-bf06cebf264e	019ad4d5-1102-7139-98a0-b3d59490ee38
1	App\\Modules\\Identity\\Domain\\User	9ee43c15-c82b-4625-9886-380788af9500	019ad4d5-1102-7139-98a0-b3d59490ee38
\.


--
-- Data for Name: partners; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.partners (id, tenant_id, name, type, code, email, phone, country_code, vat_number, notes, created_at, updated_at, deleted_at) FROM stdin;
21615954-311a-480c-aa10-0858151d477b	019ad4d5-1102-7139-98a0-b3d59490ee38	Acme Corporation	customer	ACME	contact@acme.com	+33123456789	FR	\N	\N	2025-11-30 12:55:25	2025-11-30 12:55:25	\N
e21724ee-2b94-43a3-97e4-7d78c2739069	019ad4d5-1102-7139-98a0-b3d59490ee38	TechSupply Inc	supplier	TECH	orders@techsupply.com	+33987654321	FR	\N	\N	2025-11-30 12:55:25	2025-11-30 12:55:25	\N
7d408592-b468-496a-acac-da98275ab35a	019ad4d5-1102-7139-98a0-b3d59490ee38	Auto Parts France	customer	APF	info@autoparts.fr	+33111222333	FR	\N	\N	2025-11-30 12:55:25	2025-11-30 12:55:25	\N
93df37db-3d81-47ac-beb3-6bc6f610151a	019ad4d5-1102-7139-98a0-b3d59490ee38	Client Premium SA	customer	PREM	premium@client.com	+33444555666	FR	\N	\N	2025-11-30 12:55:25	2025-11-30 12:55:25	\N
019ad5a4-b3d9-72bd-8f5e-481976ac1456	019ad4d5-1102-7139-98a0-b3d59490ee38	deedede fede	customer	\N	demo@locabooster.com	\N	\N	\N	\N	2025-11-30 16:42:12	2025-11-30 16:42:12	\N
\.


--
-- Data for Name: payment_allocations; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.payment_allocations (id, payment_id, document_id, amount, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: payment_instruments; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.payment_instruments (id, tenant_id, payment_method_id, reference, partner_id, drawer_name, amount, currency, received_date, maturity_date, expiry_date, status, repository_id, bank_name, bank_branch, bank_account, deposited_at, deposited_to_id, cleared_at, bounced_at, bounce_reason, payment_id, created_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: payment_methods; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.payment_methods (id, tenant_id, code, name, is_physical, has_maturity, requires_third_party, is_push, has_deducted_fees, is_restricted, fee_type, fee_fixed, fee_percent, restriction_type, default_journal_id, default_account_id, fee_account_id, is_active, "position", created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: payment_repositories; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.payment_repositories (id, tenant_id, code, name, type, bank_name, account_number, iban, bic, balance, last_reconciled_at, last_reconciled_balance, location_id, responsible_user_id, account_id, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: payments; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.payments (id, tenant_id, partner_id, payment_method_id, instrument_id, repository_id, amount, currency, payment_date, status, reference, notes, journal_entry_id, created_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.permissions (id, name, guard_name, created_at, updated_at) FROM stdin;
1	partners.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
2	partners.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
3	partners.update	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
4	partners.delete	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
5	products.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
6	products.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
7	products.update	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
8	products.delete	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
9	products.import	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
10	vehicles.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
11	vehicles.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
12	vehicles.update	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
13	vehicles.delete	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
14	quotes.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
15	quotes.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
16	quotes.update	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
17	quotes.delete	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
18	quotes.convert	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
19	orders.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
20	orders.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
21	orders.update	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
22	orders.delete	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
23	orders.confirm	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
24	invoices.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
25	invoices.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
26	invoices.update	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
27	invoices.delete	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
28	invoices.post	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
29	invoices.cancel	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
30	invoices.print	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
31	credit-notes.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
32	credit-notes.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
33	credit-notes.post	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
34	inventory.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
35	inventory.adjust	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
36	inventory.transfer	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
37	inventory.receive	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
38	deliveries.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
39	deliveries.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
40	deliveries.confirm	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
41	payments.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
42	payments.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
43	payments.allocate	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
44	payments.void	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
45	instruments.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
46	instruments.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
47	instruments.transfer	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
48	instruments.clear	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
49	repositories.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
50	repositories.manage	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
51	treasury.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
52	treasury.manage	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
53	journal.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
54	journal.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
55	journal.post	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
56	accounts.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
57	accounts.manage	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
58	reports.financial	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
59	reports.operational	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
60	work-orders.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
61	work-orders.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
62	work-orders.update	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
63	work-orders.complete	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
64	users.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
65	users.create	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
66	users.update	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
67	users.delete	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
68	users.assign-roles	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
69	roles.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
70	roles.manage	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
71	settings.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
72	settings.update	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
73	audit.view	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
74	imports.manage	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
75	documents.view	sanctum	2025-11-30 13:37:54	2025-11-30 13:37:54
76	purchase-orders.view	sanctum	2025-11-30 20:46:08	2025-11-30 20:46:08
77	purchase-orders.create	sanctum	2025-11-30 20:46:08	2025-11-30 20:46:08
78	purchase-orders.update	sanctum	2025-11-30 20:46:08	2025-11-30 20:46:08
79	purchase-orders.delete	sanctum	2025-11-30 20:46:08	2025-11-30 20:46:08
80	purchase-orders.confirm	sanctum	2025-11-30 20:46:08	2025-11-30 20:46:08
81	purchase-orders.receive	sanctum	2025-11-30 20:46:08	2025-11-30 20:46:08
\.


--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
1	App\\Modules\\Identity\\Domain\\User	37a277d6-5099-4870-8d43-bf06cebf264e	api-token	a660ac5f55a0b37f24653baddc3b83b0cd5ab164b03665600b65aeb5933525a5	["*"]	2025-11-30 12:55:39	\N	2025-11-30 12:55:38	2025-11-30 12:55:39
2	App\\Modules\\Identity\\Domain\\User	37a277d6-5099-4870-8d43-bf06cebf264e	api-token	160cc5f4057072bbb4dcfbe42841134fd8a184e98d0eb5c534c47178af883fdc	["*"]	\N	\N	2025-11-30 13:02:39	2025-11-30 13:02:39
3	App\\Modules\\Identity\\Domain\\User	37a277d6-5099-4870-8d43-bf06cebf264e	api-token	cd30f376a1eee711c18bf1e510b1314f06ee87db3592f90b7452f6874bb3cd74	["*"]	2025-11-30 13:06:33	\N	2025-11-30 13:06:33	2025-11-30 13:06:33
5	App\\Modules\\Identity\\Domain\\User	37a277d6-5099-4870-8d43-bf06cebf264e	api-token	1fff4ae3786b492e257685343ea2999b6b71abb8086c980e8fa6d5810d7723a4	["*"]	2025-11-30 13:10:17	\N	2025-11-30 13:10:17	2025-11-30 13:10:17
15	App\\Modules\\Identity\\Domain\\User	37a277d6-5099-4870-8d43-bf06cebf264e	api-token	517ae58af752c29c473cd6750c9be5e88f7c39bd4d32fd83f1930be201aaef20	["*"]	2025-11-30 21:29:20	\N	2025-11-30 21:07:12	2025-11-30 21:29:20
13	App\\Modules\\Identity\\Domain\\User	37a277d6-5099-4870-8d43-bf06cebf264e	test	451723cb5d593b118dcdb117aa82c66b1c8a9145d17646430b07cbc42f30242c	["*"]	2025-11-30 20:54:22	\N	2025-11-30 20:54:09	2025-11-30 20:54:22
9	App\\Modules\\Identity\\Domain\\User	9ee43c15-c82b-4625-9886-380788af9500	api-token	436e6357309e04aa40ce08904d0391039b8e76a80b540c1b54337051249dfbe2	["*"]	2025-11-30 17:35:59	\N	2025-11-30 17:20:26	2025-11-30 17:35:59
7	App\\Modules\\Identity\\Domain\\User	9ee43c15-c82b-4625-9886-380788af9500	api-token	a863b766e93e70e74c37825df14afede2a6234c880b9d08779dc8c789d87c82b	["*"]	2025-11-30 17:14:43	\N	2025-11-30 17:14:26	2025-11-30 17:14:43
10	App\\Modules\\Identity\\Domain\\User	9ee43c15-c82b-4625-9886-380788af9500	api-token	1542f349ca2f9241ae14f45ff14046ea1d7b8335849d553d9d46e36920b550ce	["*"]	2025-11-30 17:45:45	\N	2025-11-30 17:40:47	2025-11-30 17:45:45
8	App\\Modules\\Identity\\Domain\\User	9ee43c15-c82b-4625-9886-380788af9500	api-token	1d3faa0058ab02cb93c5339618fe37a4ac7077e49d377f67cb488ec2f49e6bfc	["*"]	2025-11-30 17:16:49	\N	2025-11-30 17:15:08	2025-11-30 17:16:49
\.


--
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.products (id, tenant_id, name, sku, type, description, sale_price, purchase_price, tax_rate, unit, barcode, is_active, oem_numbers, cross_references, created_at, updated_at, deleted_at) FROM stdin;
019ad5c3-3c37-7089-a378-41551f00d3da	019ad4d5-1102-7139-98a0-b3d59490ee38	Test Brake Pad	BP-001	part	\N	45.99	25.00	20.00	pcs	\N	t	\N	\N	2025-11-30 17:15:33	2025-11-30 17:15:33	\N
\.


--
-- Data for Name: role_has_permissions; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.role_has_permissions (permission_id, role_id) FROM stdin;
1	1
2	1
3	1
4	1
5	1
6	1
7	1
8	1
9	1
10	1
11	1
12	1
13	1
14	1
15	1
16	1
17	1
18	1
19	1
20	1
21	1
22	1
23	1
24	1
25	1
26	1
27	1
28	1
29	1
30	1
31	1
32	1
33	1
34	1
35	1
36	1
37	1
38	1
39	1
40	1
41	1
42	1
43	1
44	1
45	1
46	1
47	1
48	1
49	1
50	1
51	1
52	1
53	1
54	1
55	1
56	1
57	1
58	1
59	1
60	1
61	1
62	1
63	1
64	1
65	1
66	1
67	1
68	1
69	1
70	1
71	1
72	1
73	1
74	1
75	1
76	1
77	1
78	1
79	1
80	1
81	1
1	2
2	2
3	2
5	2
6	2
7	2
9	2
10	2
11	2
12	2
75	2
14	2
15	2
16	2
18	2
19	2
20	2
21	2
23	2
76	2
77	2
78	2
80	2
81	2
24	2
25	2
26	2
28	2
30	2
31	2
32	2
33	2
34	2
35	2
36	2
37	2
38	2
39	2
40	2
41	2
42	2
43	2
45	2
46	2
47	2
49	2
51	2
53	2
56	2
58	2
59	2
60	2
61	2
62	2
63	2
64	2
71	2
1	3
2	3
5	3
10	3
75	3
14	3
15	3
19	3
24	3
25	3
30	3
34	3
38	3
41	3
42	3
45	3
46	3
60	3
1	4
5	4
10	4
75	4
14	4
19	4
76	4
24	4
31	4
34	4
38	4
41	4
45	4
49	4
53	4
56	4
59	4
60	4
71	4
1	5
5	5
10	5
34	5
60	5
62	5
63	5
1	7
2	7
3	7
5	7
10	7
11	7
12	7
75	7
14	7
15	7
16	7
19	7
20	7
21	7
76	7
77	7
78	7
24	7
25	7
30	7
34	7
38	7
39	7
41	7
42	7
60	7
61	7
62	7
1	6
75	6
24	6
28	6
31	6
33	6
41	6
42	6
43	6
45	6
47	6
48	6
49	6
50	6
51	6
52	6
53	6
54	6
55	6
56	6
57	6
58	6
73	6
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.roles (id, tenant_id, name, guard_name, created_at, updated_at) FROM stdin;
1	\N	admin	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
2	\N	manager	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
3	\N	cashier	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
4	\N	viewer	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
5	\N	technician	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
6	\N	accountant	sanctum	2025-11-30 12:55:24	2025-11-30 12:55:24
7	\N	operator	sanctum	2025-11-30 20:46:08	2025-11-30 20:46:08
\.


--
-- Data for Name: snapshots; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.snapshots (id, aggregate_uuid, aggregate_version, state, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: stock_levels; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.stock_levels (id, tenant_id, product_id, location_id, quantity, reserved, min_quantity, max_quantity, created_at, updated_at) FROM stdin;
019ad5d5-abbf-7223-af80-33fa3ed09255	019ad4d5-1102-7139-98a0-b3d59490ee38	019ad5c3-3c37-7089-a378-41551f00d3da	019ad5d5-84dc-714e-9147-739d04066198	48.00	0.00	\N	\N	2025-11-30 17:35:41	2025-11-30 17:41:31
\.


--
-- Data for Name: stock_movements; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.stock_movements (id, tenant_id, product_id, location_id, movement_type, quantity, quantity_before, quantity_after, reference, notes, user_id, created_at, updated_at) FROM stdin;
019ad5d5-abc5-71ae-a8e3-c226d813651f	019ad4d5-1102-7139-98a0-b3d59490ee38	019ad5c3-3c37-7089-a378-41551f00d3da	019ad5d5-84dc-714e-9147-739d04066198	receipt	50.00	0.00	50.00	Initial stock receipt	\N	9ee43c15-c82b-4625-9886-380788af9500	2025-11-30 17:35:41	2025-11-30 17:35:41
019ad5d5-cb7b-70f7-8c0d-a973d2d1499f	019ad4d5-1102-7139-98a0-b3d59490ee38	019ad5c3-3c37-7089-a378-41551f00d3da	019ad5d5-84dc-714e-9147-739d04066198	adjustment	-5.00	50.00	45.00	Inventory count correction - 5 items damaged	\N	9ee43c15-c82b-4625-9886-380788af9500	2025-11-30 17:35:49	2025-11-30 17:35:49
019ad5da-c8ff-72ba-b33b-d0a67eb1d3da	019ad4d5-1102-7139-98a0-b3d59490ee38	019ad5c3-3c37-7089-a378-41551f00d3da	019ad5d5-84dc-714e-9147-739d04066198	receipt	10.00	45.00	55.00	Test receive via frontend	\N	9ee43c15-c82b-4625-9886-380788af9500	2025-11-30 17:41:16	2025-11-30 17:41:16
019ad5da-e55e-72c0-a519-b5bd8fa71afe	019ad4d5-1102-7139-98a0-b3d59490ee38	019ad5c3-3c37-7089-a378-41551f00d3da	019ad5d5-84dc-714e-9147-739d04066198	issue	5.00	55.00	50.00	Test issue via frontend	\N	9ee43c15-c82b-4625-9886-380788af9500	2025-11-30 17:41:23	2025-11-30 17:41:23
019ad5db-02b6-7190-a3ec-93dd4b6c3ee9	019ad4d5-1102-7139-98a0-b3d59490ee38	019ad5c3-3c37-7089-a378-41551f00d3da	019ad5d5-84dc-714e-9147-739d04066198	adjustment	-2.00	50.00	48.00	Test adjustment via frontend	\N	9ee43c15-c82b-4625-9886-380788af9500	2025-11-30 17:41:31	2025-11-30 17:41:31
\.


--
-- Data for Name: stored_events; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.stored_events (id, aggregate_uuid, aggregate_version, event_version, event_class, event_properties, meta_data, created_at) FROM stdin;
\.


--
-- Data for Name: tenants; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.tenants (id, name, slug, status, plan, tax_id, country_code, currency_code, settings, data, trial_ends_at, subscription_ends_at, created_at, updated_at) FROM stdin;
019ad4d5-1102-7139-98a0-b3d59490ee38	Demo Garage	demo-garage	active	professional	FR12345678901	FR	EUR	{"locale": "fr", "timezone": "Europe/Paris", "date_format": "d/m/Y", "fiscal_year_start": "01-01"}	[]	\N	2026-11-30 12:55:24	2025-11-30 12:55:24	2025-11-30 12:55:24
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.users (id, tenant_id, name, email, phone, password, status, locale, timezone, preferences, email_verified_at, last_login_at, last_login_ip, remember_token, created_at, updated_at) FROM stdin;
9ee43c15-c82b-4625-9886-380788af9500	019ad4d5-1102-7139-98a0-b3d59490ee38	Admin User	admin@example.com	\N	$2y$12$vDXL.hmKmnIeIpeqdu0.wOrTKOu7Ne2wpsV5SMTHiDiN.xQvLLDkq	active	\N	\N	[]	2025-11-30 12:55:25	2025-11-30 17:40:47	127.0.0.1	\N	2025-11-30 12:55:25	2025-11-30 17:40:47
37a277d6-5099-4870-8d43-bf06cebf264e	019ad4d5-1102-7139-98a0-b3d59490ee38	Test User	test@example.com	\N	$2y$12$w25cN7rSPFmdGGdHD91C7OrHbXU6UiVS/KK5hDqrN2TdEN5yVFR0q	active	\N	\N	[]	2025-11-30 12:55:24	2025-11-30 21:07:12	127.0.0.1	\N	2025-11-30 12:55:24	2025-11-30 21:07:12
\.


--
-- Data for Name: vehicles; Type: TABLE DATA; Schema: public; Owner: autoerp
--

COPY public.vehicles (id, tenant_id, partner_id, license_plate, brand, model, year, color, mileage, vin, engine_code, fuel_type, transmission, notes, created_at, updated_at, deleted_at) FROM stdin;
019ad5de-e234-71ad-b9a2-a12bf4b2dbab	019ad4d5-1102-7139-98a0-b3d59490ee38	21615954-311a-480c-aa10-0858151d477b	AB-123-CD	Renault	Clio	2020	Blue	45000	VF1RFB00X51234567	\N	Petrol	Manual	\N	2025-11-30 17:45:45	2025-11-30 17:45:45	\N
\.


--
-- Name: chunk_column_stats_id_seq; Type: SEQUENCE SET; Schema: _timescaledb_catalog; Owner: autoerp
--

SELECT pg_catalog.setval('_timescaledb_catalog.chunk_column_stats_id_seq', 1, false);


--
-- Name: chunk_constraint_name; Type: SEQUENCE SET; Schema: _timescaledb_catalog; Owner: autoerp
--

SELECT pg_catalog.setval('_timescaledb_catalog.chunk_constraint_name', 1, false);


--
-- Name: chunk_id_seq; Type: SEQUENCE SET; Schema: _timescaledb_catalog; Owner: autoerp
--

SELECT pg_catalog.setval('_timescaledb_catalog.chunk_id_seq', 1, false);


--
-- Name: continuous_agg_migrate_plan_step_step_id_seq; Type: SEQUENCE SET; Schema: _timescaledb_catalog; Owner: autoerp
--

SELECT pg_catalog.setval('_timescaledb_catalog.continuous_agg_migrate_plan_step_step_id_seq', 1, false);


--
-- Name: dimension_id_seq; Type: SEQUENCE SET; Schema: _timescaledb_catalog; Owner: autoerp
--

SELECT pg_catalog.setval('_timescaledb_catalog.dimension_id_seq', 1, false);


--
-- Name: dimension_slice_id_seq; Type: SEQUENCE SET; Schema: _timescaledb_catalog; Owner: autoerp
--

SELECT pg_catalog.setval('_timescaledb_catalog.dimension_slice_id_seq', 1, false);


--
-- Name: hypertable_id_seq; Type: SEQUENCE SET; Schema: _timescaledb_catalog; Owner: autoerp
--

SELECT pg_catalog.setval('_timescaledb_catalog.hypertable_id_seq', 1, false);


--
-- Name: bgw_job_id_seq; Type: SEQUENCE SET; Schema: _timescaledb_config; Owner: autoerp
--

SELECT pg_catalog.setval('_timescaledb_config.bgw_job_id_seq', 1000, false);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: autoerp
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: autoerp
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: autoerp
--

SELECT pg_catalog.setval('public.migrations_id_seq', 23, true);


--
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: autoerp
--

SELECT pg_catalog.setval('public.permissions_id_seq', 81, true);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: autoerp
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 15, true);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: autoerp
--

SELECT pg_catalog.setval('public.roles_id_seq', 7, true);


--
-- Name: snapshots_id_seq; Type: SEQUENCE SET; Schema: public; Owner: autoerp
--

SELECT pg_catalog.setval('public.snapshots_id_seq', 1, false);


--
-- Name: stored_events_id_seq; Type: SEQUENCE SET; Schema: public; Owner: autoerp
--

SELECT pg_catalog.setval('public.stored_events_id_seq', 1, false);


--
-- Name: accounts accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: accounts accounts_tenant_id_code_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_tenant_id_code_unique UNIQUE (tenant_id, code);


--
-- Name: audit_events audit_events_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.audit_events
    ADD CONSTRAINT audit_events_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: devices devices_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.devices
    ADD CONSTRAINT devices_pkey PRIMARY KEY (id);


--
-- Name: document_lines document_lines_document_id_line_number_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.document_lines
    ADD CONSTRAINT document_lines_document_id_line_number_unique UNIQUE (document_id, line_number);


--
-- Name: document_lines document_lines_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.document_lines
    ADD CONSTRAINT document_lines_pkey PRIMARY KEY (id);


--
-- Name: document_sequences document_sequences_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.document_sequences
    ADD CONSTRAINT document_sequences_pkey PRIMARY KEY (id);


--
-- Name: document_sequences document_sequences_tenant_id_type_year_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.document_sequences
    ADD CONSTRAINT document_sequences_tenant_id_type_year_unique UNIQUE (tenant_id, type, year);


--
-- Name: documents documents_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_pkey PRIMARY KEY (id);


--
-- Name: documents documents_tenant_id_type_document_number_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_tenant_id_type_document_number_unique UNIQUE (tenant_id, type, document_number);


--
-- Name: domains domains_domain_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.domains
    ADD CONSTRAINT domains_domain_unique UNIQUE (domain);


--
-- Name: domains domains_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.domains
    ADD CONSTRAINT domains_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: import_jobs import_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.import_jobs
    ADD CONSTRAINT import_jobs_pkey PRIMARY KEY (id);


--
-- Name: import_rows import_rows_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.import_rows
    ADD CONSTRAINT import_rows_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: journal_entries journal_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.journal_entries
    ADD CONSTRAINT journal_entries_pkey PRIMARY KEY (id);


--
-- Name: journal_entries journal_entries_tenant_id_entry_number_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.journal_entries
    ADD CONSTRAINT journal_entries_tenant_id_entry_number_unique UNIQUE (tenant_id, entry_number);


--
-- Name: journal_lines journal_lines_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.journal_lines
    ADD CONSTRAINT journal_lines_pkey PRIMARY KEY (id);


--
-- Name: locations locations_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_pkey PRIMARY KEY (id);


--
-- Name: locations locations_tenant_id_code_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_tenant_id_code_unique UNIQUE (tenant_id, code);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (tenant_id, permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (tenant_id, role_id, model_id, model_type);


--
-- Name: partners partners_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.partners
    ADD CONSTRAINT partners_pkey PRIMARY KEY (id);


--
-- Name: partners partners_tenant_id_code_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.partners
    ADD CONSTRAINT partners_tenant_id_code_unique UNIQUE (tenant_id, code);


--
-- Name: partners partners_tenant_id_vat_number_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.partners
    ADD CONSTRAINT partners_tenant_id_vat_number_unique UNIQUE (tenant_id, vat_number);


--
-- Name: payment_allocations payment_allocations_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_allocations
    ADD CONSTRAINT payment_allocations_pkey PRIMARY KEY (id);


--
-- Name: payment_instruments payment_instruments_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_instruments
    ADD CONSTRAINT payment_instruments_pkey PRIMARY KEY (id);


--
-- Name: payment_methods payment_methods_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_pkey PRIMARY KEY (id);


--
-- Name: payment_methods payment_methods_tenant_id_code_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_tenant_id_code_unique UNIQUE (tenant_id, code);


--
-- Name: payment_repositories payment_repositories_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_repositories
    ADD CONSTRAINT payment_repositories_pkey PRIMARY KEY (id);


--
-- Name: payment_repositories payment_repositories_tenant_id_code_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_repositories
    ADD CONSTRAINT payment_repositories_tenant_id_code_unique UNIQUE (tenant_id, code);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: products products_tenant_id_sku_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_tenant_id_sku_unique UNIQUE (tenant_id, sku);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: roles roles_tenant_id_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_tenant_id_name_guard_name_unique UNIQUE (tenant_id, name, guard_name);


--
-- Name: snapshots snapshots_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.snapshots
    ADD CONSTRAINT snapshots_pkey PRIMARY KEY (id);


--
-- Name: stock_levels stock_levels_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stock_levels
    ADD CONSTRAINT stock_levels_pkey PRIMARY KEY (id);


--
-- Name: stock_levels stock_levels_tenant_id_product_id_location_id_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stock_levels
    ADD CONSTRAINT stock_levels_tenant_id_product_id_location_id_unique UNIQUE (tenant_id, product_id, location_id);


--
-- Name: stock_movements stock_movements_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_pkey PRIMARY KEY (id);


--
-- Name: stored_events stored_events_aggregate_uuid_aggregate_version_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stored_events
    ADD CONSTRAINT stored_events_aggregate_uuid_aggregate_version_unique UNIQUE (aggregate_uuid, aggregate_version);


--
-- Name: stored_events stored_events_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stored_events
    ADD CONSTRAINT stored_events_pkey PRIMARY KEY (id);


--
-- Name: tenants tenants_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_pkey PRIMARY KEY (id);


--
-- Name: tenants tenants_slug_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_slug_unique UNIQUE (slug);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_tenant_id_email_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_tenant_id_email_unique UNIQUE (tenant_id, email);


--
-- Name: vehicles vehicles_pkey; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_pkey PRIMARY KEY (id);


--
-- Name: vehicles vehicles_tenant_id_license_plate_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_tenant_id_license_plate_unique UNIQUE (tenant_id, license_plate);


--
-- Name: vehicles vehicles_tenant_id_vin_unique; Type: CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_tenant_id_vin_unique UNIQUE (tenant_id, vin);


--
-- Name: accounts_tenant_id_parent_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX accounts_tenant_id_parent_id_index ON public.accounts USING btree (tenant_id, parent_id);


--
-- Name: accounts_tenant_id_type_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX accounts_tenant_id_type_index ON public.accounts USING btree (tenant_id, type);


--
-- Name: audit_events_aggregate_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX audit_events_aggregate_id_index ON public.audit_events USING btree (aggregate_id);


--
-- Name: audit_events_aggregate_type_aggregate_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX audit_events_aggregate_type_aggregate_id_index ON public.audit_events USING btree (aggregate_type, aggregate_id);


--
-- Name: audit_events_aggregate_type_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX audit_events_aggregate_type_index ON public.audit_events USING btree (aggregate_type);


--
-- Name: audit_events_event_type_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX audit_events_event_type_index ON public.audit_events USING btree (event_type);


--
-- Name: audit_events_occurred_at_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX audit_events_occurred_at_index ON public.audit_events USING btree (occurred_at);


--
-- Name: audit_events_tenant_id_event_type_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX audit_events_tenant_id_event_type_index ON public.audit_events USING btree (tenant_id, event_type);


--
-- Name: audit_events_tenant_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX audit_events_tenant_id_index ON public.audit_events USING btree (tenant_id);


--
-- Name: audit_events_tenant_id_occurred_at_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX audit_events_tenant_id_occurred_at_index ON public.audit_events USING btree (tenant_id, occurred_at);


--
-- Name: audit_events_tenant_id_user_id_occurred_at_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX audit_events_tenant_id_user_id_occurred_at_index ON public.audit_events USING btree (tenant_id, user_id, occurred_at);


--
-- Name: audit_events_user_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX audit_events_user_id_index ON public.audit_events USING btree (user_id);


--
-- Name: devices_device_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX devices_device_id_index ON public.devices USING btree (device_id);


--
-- Name: devices_user_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX devices_user_id_index ON public.devices USING btree (user_id);


--
-- Name: devices_user_id_is_active_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX devices_user_id_is_active_index ON public.devices USING btree (user_id, is_active);


--
-- Name: document_lines_product_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX document_lines_product_id_index ON public.document_lines USING btree (product_id);


--
-- Name: documents_tenant_id_document_date_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX documents_tenant_id_document_date_index ON public.documents USING btree (tenant_id, document_date);


--
-- Name: documents_tenant_id_partner_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX documents_tenant_id_partner_id_index ON public.documents USING btree (tenant_id, partner_id);


--
-- Name: documents_tenant_id_type_chain_sequence_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX documents_tenant_id_type_chain_sequence_index ON public.documents USING btree (tenant_id, type, chain_sequence);


--
-- Name: documents_tenant_id_type_status_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX documents_tenant_id_type_status_index ON public.documents USING btree (tenant_id, type, status);


--
-- Name: domains_tenant_id_is_primary_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX domains_tenant_id_is_primary_index ON public.domains USING btree (tenant_id, is_primary);


--
-- Name: import_jobs_tenant_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX import_jobs_tenant_id_index ON public.import_jobs USING btree (tenant_id);


--
-- Name: import_jobs_tenant_id_status_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX import_jobs_tenant_id_status_index ON public.import_jobs USING btree (tenant_id, status);


--
-- Name: import_jobs_tenant_id_type_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX import_jobs_tenant_id_type_index ON public.import_jobs USING btree (tenant_id, type);


--
-- Name: import_jobs_user_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX import_jobs_user_id_index ON public.import_jobs USING btree (user_id);


--
-- Name: import_rows_import_job_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX import_rows_import_job_id_index ON public.import_rows USING btree (import_job_id);


--
-- Name: import_rows_import_job_id_is_valid_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX import_rows_import_job_id_is_valid_index ON public.import_rows USING btree (import_job_id, is_valid);


--
-- Name: import_rows_import_job_id_row_number_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX import_rows_import_job_id_row_number_index ON public.import_rows USING btree (import_job_id, row_number);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: journal_entries_entry_number_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX journal_entries_entry_number_index ON public.journal_entries USING btree (entry_number);


--
-- Name: journal_entries_source_type_source_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX journal_entries_source_type_source_id_index ON public.journal_entries USING btree (source_type, source_id);


--
-- Name: journal_entries_tenant_id_chain_sequence_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX journal_entries_tenant_id_chain_sequence_index ON public.journal_entries USING btree (tenant_id, chain_sequence);


--
-- Name: journal_lines_account_id_created_at_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX journal_lines_account_id_created_at_index ON public.journal_lines USING btree (account_id, created_at);


--
-- Name: locations_tenant_id_is_active_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX locations_tenant_id_is_active_index ON public.locations USING btree (tenant_id, is_active);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_permissions_team_foreign_key_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX model_has_permissions_team_foreign_key_index ON public.model_has_permissions USING btree (tenant_id);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: model_has_roles_team_foreign_key_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX model_has_roles_team_foreign_key_index ON public.model_has_roles USING btree (tenant_id);


--
-- Name: partners_tenant_id_name_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX partners_tenant_id_name_index ON public.partners USING btree (tenant_id, name);


--
-- Name: partners_tenant_id_type_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX partners_tenant_id_type_index ON public.partners USING btree (tenant_id, type);


--
-- Name: payment_allocations_document_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payment_allocations_document_id_index ON public.payment_allocations USING btree (document_id);


--
-- Name: payment_allocations_payment_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payment_allocations_payment_id_index ON public.payment_allocations USING btree (payment_id);


--
-- Name: payment_instruments_tenant_id_maturity_date_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payment_instruments_tenant_id_maturity_date_index ON public.payment_instruments USING btree (tenant_id, maturity_date);


--
-- Name: payment_instruments_tenant_id_partner_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payment_instruments_tenant_id_partner_id_index ON public.payment_instruments USING btree (tenant_id, partner_id);


--
-- Name: payment_instruments_tenant_id_repository_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payment_instruments_tenant_id_repository_id_index ON public.payment_instruments USING btree (tenant_id, repository_id);


--
-- Name: payment_instruments_tenant_id_status_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payment_instruments_tenant_id_status_index ON public.payment_instruments USING btree (tenant_id, status);


--
-- Name: payment_methods_tenant_id_is_active_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payment_methods_tenant_id_is_active_index ON public.payment_methods USING btree (tenant_id, is_active);


--
-- Name: payment_repositories_tenant_id_is_active_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payment_repositories_tenant_id_is_active_index ON public.payment_repositories USING btree (tenant_id, is_active);


--
-- Name: payment_repositories_tenant_id_type_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payment_repositories_tenant_id_type_index ON public.payment_repositories USING btree (tenant_id, type);


--
-- Name: payments_tenant_id_partner_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payments_tenant_id_partner_id_index ON public.payments USING btree (tenant_id, partner_id);


--
-- Name: payments_tenant_id_payment_date_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payments_tenant_id_payment_date_index ON public.payments USING btree (tenant_id, payment_date);


--
-- Name: payments_tenant_id_status_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX payments_tenant_id_status_index ON public.payments USING btree (tenant_id, status);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: products_tenant_id_barcode_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX products_tenant_id_barcode_index ON public.products USING btree (tenant_id, barcode);


--
-- Name: products_tenant_id_is_active_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX products_tenant_id_is_active_index ON public.products USING btree (tenant_id, is_active);


--
-- Name: products_tenant_id_name_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX products_tenant_id_name_index ON public.products USING btree (tenant_id, name);


--
-- Name: products_tenant_id_type_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX products_tenant_id_type_index ON public.products USING btree (tenant_id, type);


--
-- Name: roles_team_foreign_key_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX roles_team_foreign_key_index ON public.roles USING btree (tenant_id);


--
-- Name: snapshots_aggregate_uuid_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX snapshots_aggregate_uuid_index ON public.snapshots USING btree (aggregate_uuid);


--
-- Name: stock_levels_tenant_id_location_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX stock_levels_tenant_id_location_id_index ON public.stock_levels USING btree (tenant_id, location_id);


--
-- Name: stock_levels_tenant_id_product_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX stock_levels_tenant_id_product_id_index ON public.stock_levels USING btree (tenant_id, product_id);


--
-- Name: stock_movements_reference_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX stock_movements_reference_index ON public.stock_movements USING btree (reference);


--
-- Name: stock_movements_tenant_id_location_id_created_at_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX stock_movements_tenant_id_location_id_created_at_index ON public.stock_movements USING btree (tenant_id, location_id, created_at);


--
-- Name: stock_movements_tenant_id_movement_type_created_at_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX stock_movements_tenant_id_movement_type_created_at_index ON public.stock_movements USING btree (tenant_id, movement_type, created_at);


--
-- Name: stock_movements_tenant_id_product_id_created_at_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX stock_movements_tenant_id_product_id_created_at_index ON public.stock_movements USING btree (tenant_id, product_id, created_at);


--
-- Name: stored_events_aggregate_uuid_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX stored_events_aggregate_uuid_index ON public.stored_events USING btree (aggregate_uuid);


--
-- Name: stored_events_event_class_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX stored_events_event_class_index ON public.stored_events USING btree (event_class);


--
-- Name: tenants_country_code_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX tenants_country_code_index ON public.tenants USING btree (country_code);


--
-- Name: tenants_plan_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX tenants_plan_index ON public.tenants USING btree (plan);


--
-- Name: tenants_slug_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX tenants_slug_index ON public.tenants USING btree (slug);


--
-- Name: tenants_status_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX tenants_status_index ON public.tenants USING btree (status);


--
-- Name: users_email_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX users_email_index ON public.users USING btree (email);


--
-- Name: users_status_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX users_status_index ON public.users USING btree (status);


--
-- Name: users_tenant_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX users_tenant_id_index ON public.users USING btree (tenant_id);


--
-- Name: vehicles_tenant_id_brand_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX vehicles_tenant_id_brand_index ON public.vehicles USING btree (tenant_id, brand);


--
-- Name: vehicles_tenant_id_partner_id_index; Type: INDEX; Schema: public; Owner: autoerp
--

CREATE INDEX vehicles_tenant_id_partner_id_index ON public.vehicles USING btree (tenant_id, partner_id);


--
-- Name: accounts accounts_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: accounts accounts_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: devices devices_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.devices
    ADD CONSTRAINT devices_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: document_lines document_lines_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.document_lines
    ADD CONSTRAINT document_lines_document_id_foreign FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE CASCADE;


--
-- Name: document_lines document_lines_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.document_lines
    ADD CONSTRAINT document_lines_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE SET NULL;


--
-- Name: document_sequences document_sequences_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.document_sequences
    ADD CONSTRAINT document_sequences_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: documents documents_partner_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_partner_id_foreign FOREIGN KEY (partner_id) REFERENCES public.partners(id);


--
-- Name: documents documents_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: documents documents_vehicle_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_vehicle_id_foreign FOREIGN KEY (vehicle_id) REFERENCES public.vehicles(id) ON DELETE SET NULL;


--
-- Name: domains domains_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.domains
    ADD CONSTRAINT domains_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: import_rows import_rows_import_job_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.import_rows
    ADD CONSTRAINT import_rows_import_job_id_foreign FOREIGN KEY (import_job_id) REFERENCES public.import_jobs(id) ON DELETE CASCADE;


--
-- Name: journal_entries journal_entries_posted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.journal_entries
    ADD CONSTRAINT journal_entries_posted_by_foreign FOREIGN KEY (posted_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: journal_entries journal_entries_reversed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.journal_entries
    ADD CONSTRAINT journal_entries_reversed_by_foreign FOREIGN KEY (reversed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: journal_entries journal_entries_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.journal_entries
    ADD CONSTRAINT journal_entries_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: journal_lines journal_lines_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.journal_lines
    ADD CONSTRAINT journal_lines_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE RESTRICT;


--
-- Name: journal_lines journal_lines_journal_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.journal_lines
    ADD CONSTRAINT journal_lines_journal_entry_id_foreign FOREIGN KEY (journal_entry_id) REFERENCES public.journal_entries(id) ON DELETE CASCADE;


--
-- Name: locations locations_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: partners partners_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.partners
    ADD CONSTRAINT partners_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: payment_allocations payment_allocations_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_allocations
    ADD CONSTRAINT payment_allocations_document_id_foreign FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE RESTRICT;


--
-- Name: payment_allocations payment_allocations_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_allocations
    ADD CONSTRAINT payment_allocations_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE CASCADE;


--
-- Name: payment_instruments payment_instruments_payment_method_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_instruments
    ADD CONSTRAINT payment_instruments_payment_method_id_foreign FOREIGN KEY (payment_method_id) REFERENCES public.payment_methods(id) ON DELETE RESTRICT;


--
-- Name: payment_instruments payment_instruments_repository_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_instruments
    ADD CONSTRAINT payment_instruments_repository_id_foreign FOREIGN KEY (repository_id) REFERENCES public.payment_repositories(id) ON DELETE RESTRICT;


--
-- Name: payment_instruments payment_instruments_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_instruments
    ADD CONSTRAINT payment_instruments_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: payment_methods payment_methods_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: payment_repositories payment_repositories_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payment_repositories
    ADD CONSTRAINT payment_repositories_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: payments payments_partner_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_partner_id_foreign FOREIGN KEY (partner_id) REFERENCES public.partners(id) ON DELETE RESTRICT;


--
-- Name: payments payments_payment_method_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_payment_method_id_foreign FOREIGN KEY (payment_method_id) REFERENCES public.payment_methods(id) ON DELETE RESTRICT;


--
-- Name: payments payments_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: products products_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: stock_levels stock_levels_location_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stock_levels
    ADD CONSTRAINT stock_levels_location_id_foreign FOREIGN KEY (location_id) REFERENCES public.locations(id) ON DELETE CASCADE;


--
-- Name: stock_levels stock_levels_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stock_levels
    ADD CONSTRAINT stock_levels_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: stock_levels stock_levels_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stock_levels
    ADD CONSTRAINT stock_levels_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: stock_movements stock_movements_location_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_location_id_foreign FOREIGN KEY (location_id) REFERENCES public.locations(id) ON DELETE CASCADE;


--
-- Name: stock_movements stock_movements_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: stock_movements stock_movements_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: stock_movements stock_movements_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: users users_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: vehicles vehicles_partner_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_partner_id_foreign FOREIGN KEY (partner_id) REFERENCES public.partners(id) ON DELETE SET NULL;


--
-- Name: vehicles vehicles_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: autoerp
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict n0bGmdjEbHrfqY34cpgjhWhKi7P6ngvBDD0iiawcPxDRyGItLckDHFiUb9xUC0Y

