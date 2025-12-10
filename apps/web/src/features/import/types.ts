// Import Types - matches backend API

export type ImportType = 'partners' | 'products' | 'stock_levels' | 'opening_balances'

export type ImportStatus =
  | 'pending'
  | 'validating'
  | 'validated'
  | 'importing'
  | 'completed'
  | 'failed'

export interface ImportJob {
  id: number
  type: ImportType
  status: ImportStatus
  original_filename: string
  total_rows: number
  processed_rows: number
  successful_rows: number
  failed_rows: number
  progress_percentage: number
  error_message: string | null
  started_at: string | null
  completed_at: string | null
  created_at: string
}

export interface ImportRow {
  row_number: number
  data: Record<string, string>
  is_valid: boolean
  errors: ImportRowError[]
}

export interface ImportRowError {
  field: string
  error: string
}

// Migration Wizard Types - matches backend exactly
export interface ImportTypeMetadata {
  type: ImportType
  label: string
  description: string
}

export interface MigrationWizardOrder {
  data: ImportTypeMetadata[]
}

export interface DependencyCheck {
  can_import: boolean
  missing_dependencies: string[]
  warnings: string[]
}

export interface ColumnMappingSuggestions {
  suggestions: Record<string, string | null>
  unmapped_source: string[]
  unmapped_target: string[]
}

export interface MigrationStatus {
  partners: { count: number; has_data: boolean }
  products: { count: number; has_data: boolean }
  stock_levels: { count: number; has_data: boolean }
  accounts: { count: number; has_data: boolean }
}

// API Response types
export interface ImportJobResponse {
  data: ImportJob
}

export interface ImportJobListResponse {
  data: ImportJob[]
  meta: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export interface ImportErrorsResponse {
  data: ImportRow[]
}

export interface CreateImportResponse {
  data: ImportJob
  errors?: {
    missing_columns: string[]
    unknown_columns: string[]
  }
}
