// packages/shared/src/inventory-counting/types.ts

export type CountingScopeType =
  | 'product_location'
  | 'product'
  | 'location'
  | 'category'
  | 'warehouse'
  | 'full_inventory';

export type CountingStatus =
  | 'draft'
  | 'scheduled'
  | 'count_1_in_progress'
  | 'count_1_completed'
  | 'count_2_in_progress'
  | 'count_2_completed'
  | 'count_3_in_progress'
  | 'count_3_completed'
  | 'pending_review'
  | 'finalized'
  | 'cancelled';

export type CountingExecutionMode = 'parallel' | 'sequential';

export type ItemResolutionMethod =
  | 'pending'
  | 'auto_all_match'
  | 'auto_counters_agree'
  | 'third_count_decisive'
  | 'manual_override';

export type AssignmentStatus = 'pending' | 'in_progress' | 'completed' | 'overdue';

export interface CountingUser {
  id: number;
  name: string;
  email: string;
  avatar_url?: string;
}

export interface CountingProgress {
  count_1: { counted: number; total: number; percentage: number } | null;
  count_2: { counted: number; total: number; percentage: number } | null;
  count_3: { counted: number; total: number; percentage: number } | null;
  overall: number;
}

export interface CountingAssignment {
  id: number;
  user_id: number;
  user: CountingUser;
  count_number: 1 | 2 | 3;
  status: AssignmentStatus;
  assigned_at: string;
  started_at: string | null;
  completed_at: string | null;
  deadline: string | null;
  total_items: number;
  counted_items: number;
  progress_percentage: number;
}

export interface InventoryCounting {
  id: number;
  uuid: string;
  company_id: number;
  scope_type: CountingScopeType;
  scope_filters: Record<string, unknown>;
  execution_mode: CountingExecutionMode;
  status: CountingStatus;
  scheduled_start: string | null;
  scheduled_end: string | null;
  requires_count_2: boolean;
  requires_count_3: boolean;
  allow_unexpected_items: boolean;
  instructions: string | null;

  count_1_user: CountingUser | null;
  count_2_user: CountingUser | null;
  count_3_user: CountingUser | null;
  created_by: CountingUser;

  assignments: CountingAssignment[];
  progress: CountingProgress;

  items_count?: number;

  created_at: string;
  updated_at: string;
  activated_at: string | null;
  finalized_at: string | null;
  cancelled_at: string | null;
  cancellation_reason: string | null;
}

export interface CountingItemProduct {
  id: number;
  name: string;
  sku: string;
  barcode: string | null;
  image_url: string | null;
}

export interface CountingItemLocation {
  id: number;
  code: string;
  name: string;
}

export interface CountingItemCount {
  qty: number;
  at: string;
  notes: string | null;
}

export interface ReconciliationItem {
  id: number;
  product: CountingItemProduct;
  variant: { id: number; name: string } | null;
  location: CountingItemLocation;
  warehouse: { id: number; name: string };

  theoretical_qty: number;
  count_1: CountingItemCount | null;
  count_2: CountingItemCount | null;
  count_3: CountingItemCount | null;

  final_qty: number | null;
  variance: number | null;
  variance_percentage: number | null;

  resolution_method: ItemResolutionMethod;
  resolution_notes: string | null;

  is_flagged: boolean;
  flag_reason: string | null;
}

export interface CountingDashboard {
  summary: {
    active: number;
    pending_review: number;
    completed_this_month: number;
    overdue: number;
  };
  active_counts: InventoryCounting[];
  pending_review: InventoryCounting[];
}

export interface DiscrepancyReportSummary {
  total_items_counted: number;
  items_no_variance: number;
  items_with_variance: number;
  variance_breakdown: {
    auto_all_match: number;
    auto_counters_agree: number;
    third_count_decisive: number;
    manual_override: number;
  };
  total_variance_value: {
    positive: number;
    negative: number;
    net: number;
    currency: string;
  };
}

export interface DiscrepancyReportCounterPerformance {
  user: CountingUser;
  items_counted: number;
  matched_other_counter: number;
  matched_theoretical: number;
  times_proven_wrong_by_3rd: number;
  accuracy_rate: number;
}

export interface DiscrepancyReport {
  report_id: string;
  generated_at: string;
  generated_by: CountingUser;
  counting: InventoryCounting;
  summary: DiscrepancyReportSummary;
  flagged_items: ReconciliationItem[];
  counter_performance: DiscrepancyReportCounterPerformance[];
}

// Form types
export interface CreateCountingFormData {
  scope_type: CountingScopeType;
  scope_filters: {
    product_ids?: number[];
    category_ids?: number[];
    warehouse_ids?: number[];
    location_ids?: number[];
    location_id?: number;
  };
  execution_mode: CountingExecutionMode;
  requires_count_2: boolean;
  requires_count_3: boolean;
  allow_unexpected_items: boolean;
  count_1_user_id: number;
  count_2_user_id?: number;
  count_3_user_id?: number;
  scheduled_start?: string;
  scheduled_end?: string;
  instructions?: string;
}

export interface ManualOverrideFormData {
  quantity: number;
  notes: string;
}

// API Response types
export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

export interface CountingFilters {
  status?: CountingStatus | 'all';
  warehouse_id?: number;
  search?: string;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
