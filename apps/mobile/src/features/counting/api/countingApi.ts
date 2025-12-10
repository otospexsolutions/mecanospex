import { api } from '@/lib/api';

export interface CountingTask {
  id: number;
  uuid: string;
  status: string;
  scope_type: string;
  scheduled_end: string | null;
  instructions: string | null;
  my_count_number: 1 | 2 | 3;
  progress: {
    counted: number;
    total: number;
  };
}

/**
 * CRITICAL: This interface must NEVER include theoretical_qty
 * This is BLIND COUNTING - counters must not know expected values.
 */
export interface CountingItem {
  id: number;
  product: {
    id: number;
    name: string;
    sku: string;
    barcode: string | null;
    image_url: string | null;
  };
  variant: {
    id: number;
    name: string;
  } | null;
  location: {
    id: number;
    code: string;
    name: string;
  };
  warehouse: {
    id: number;
    name: string;
  };
  unit_of_measure: string;
  is_counted: boolean;
  my_count: number | null;
  my_count_at: string | null;
  // NEVER INCLUDE: theoretical_qty, count_1_qty, count_2_qty, count_3_qty
}

export interface CountingSession {
  counting: {
    id: number;
    uuid: string;
    status: string;
    instructions: string | null;
    deadline: string | null;
  };
  my_count_number: 1 | 2 | 3;
  items: CountingItem[];
  progress: {
    counted: number;
    total: number;
  };
}

export const countingApi = {
  // Get assigned tasks
  getTasks: async (): Promise<CountingTask[]> => {
    const response = await api.get('/inventory/countings/my-tasks');
    return response.data.data;
  },

  // Get counting session (counter view - BLIND)
  getSession: async (countingId: number): Promise<CountingSession> => {
    const response = await api.get(
      `/inventory/countings/${countingId}/counter-view`
    );
    return response.data.data;
  },

  // Get items to count (BLIND - no theoretical qty!)
  getItems: async (
    countingId: number,
    uncountedOnly = false
  ): Promise<CountingItem[]> => {
    const response = await api.get(
      `/inventory/countings/${countingId}/items/to-count`,
      {
        params: { uncounted_only: uncountedOnly },
      }
    );
    return response.data.data;
  },

  // Submit count
  submitCount: async (
    countingId: number,
    itemId: number,
    quantity: number,
    notes?: string
  ): Promise<void> => {
    await api.post(`/inventory/countings/${countingId}/items/${itemId}/count`, {
      quantity,
      notes,
    });
  },

  // Lookup by barcode
  lookupByBarcode: async (
    countingId: number,
    barcode: string
  ): Promise<{ found: boolean; data?: CountingItem; message?: string }> => {
    const response = await api.get(
      `/inventory/countings/${countingId}/lookup`,
      {
        params: { barcode },
      }
    );
    return response.data;
  },
};
