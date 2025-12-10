import { api, apiGet, apiPost } from '@/lib/api';
import type {
  InventoryCounting,
  CountingDashboard,
  ReconciliationItem,
  DiscrepancyReport,
  CreateCountingFormData,
  CountingFilters,
  PaginatedResponse,
} from '../../../../../packages/shared/src/inventory-counting';

const BASE_URL = '/inventory/countings';

export const countingApi = {
  // Dashboard
  getDashboard: async (): Promise<CountingDashboard> => {
    return await apiGet<CountingDashboard>(`${BASE_URL}/dashboard`);
  },

  // List
  list: async (filters: CountingFilters): Promise<PaginatedResponse<InventoryCounting>> => {
    const response = await api.get(BASE_URL, { params: filters });
    return response.data;
  },

  // Detail
  getDetail: async (id: number): Promise<InventoryCounting> => {
    return await apiGet<InventoryCounting>(`${BASE_URL}/${id}`);
  },

  // Create
  create: async (data: CreateCountingFormData): Promise<InventoryCounting> => {
    return await apiPost<InventoryCounting>(BASE_URL, data);
  },

  // Activate
  activate: async (id: number): Promise<void> => {
    await apiPost<void>(`${BASE_URL}/${id}/activate`);
  },

  // Cancel
  cancel: async (id: number, reason: string): Promise<void> => {
    await apiPost<void>(`${BASE_URL}/${id}/cancel`, { reason });
  },

  // Finalize
  finalize: async (id: number): Promise<void> => {
    await apiPost<void>(`${BASE_URL}/${id}/finalize`);
  },

  // Reconciliation
  getReconciliation: async (id: number): Promise<{
    summary: {
      total: number;
      auto_resolved: number;
      needs_attention: number;
      manually_overridden: number;
    };
    items: ReconciliationItem[];
  }> => {
    return await apiGet<{
      summary: {
        total: number;
        auto_resolved: number;
        needs_attention: number;
        manually_overridden: number;
      };
      items: ReconciliationItem[];
    }>(`${BASE_URL}/${id}/reconciliation`);
  },

  // Trigger third count
  triggerThirdCount: async (countingId: number, itemIds: number[]): Promise<void> => {
    await apiPost<void>(`${BASE_URL}/${countingId}/trigger-third-count`, { item_ids: itemIds });
  },

  // Manual override
  manualOverride: async (
    itemId: number,
    quantity: number,
    notes: string
  ): Promise<void> => {
    await apiPost<void>(`${BASE_URL}/items/${itemId}/override`, {
      quantity,
      notes,
    });
  },

  // Report
  getReport: async (id: number): Promise<DiscrepancyReport> => {
    return await apiGet<DiscrepancyReport>(`${BASE_URL}/${id}/report`);
  },

  // Export report
  exportReport: async (id: number, format: 'pdf' | 'xlsx'): Promise<Blob> => {
    const response = await api.get(`${BASE_URL}/${id}/report/export`, {
      params: { format },
      responseType: 'blob',
    });
    return response.data;
  },
};
