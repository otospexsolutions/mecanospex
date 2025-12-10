import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { countingApi } from './countingApi';
import type { CountingFilters, CreateCountingFormData } from '../../../../../packages/shared/src/inventory-counting';
import { toast } from 'sonner';

// Query Keys
export const countingKeys = {
  all: ['counting'] as const,
  lists: () => [...countingKeys.all, 'list'] as const,
  list: (filters: CountingFilters) => [...countingKeys.lists(), filters] as const,
  details: () => [...countingKeys.all, 'detail'] as const,
  detail: (id: number) => [...countingKeys.details(), id] as const,
  reconciliation: (id: number) => [...countingKeys.all, 'reconciliation', id] as const,
  report: (id: number) => [...countingKeys.all, 'report', id] as const,
  dashboard: () => [...countingKeys.all, 'dashboard'] as const,
};

// Queries
export function useCountingDashboard() {
  return useQuery({
    queryKey: countingKeys.dashboard(),
    queryFn: countingApi.getDashboard,
    refetchInterval: 30000, // Refresh every 30s
  });
}

export function useCountingList(filters: CountingFilters) {
  return useQuery({
    queryKey: countingKeys.list(filters),
    queryFn: () => countingApi.list(filters),
  });
}

export function useCountingDetail(id: number) {
  return useQuery({
    queryKey: countingKeys.detail(id),
    queryFn: () => countingApi.getDetail(id),
    enabled: !!id,
  });
}

export function useReconciliation(countingId: number) {
  return useQuery({
    queryKey: countingKeys.reconciliation(countingId),
    queryFn: () => countingApi.getReconciliation(countingId),
    enabled: !!countingId,
  });
}

export function useDiscrepancyReport(countingId: number) {
  return useQuery({
    queryKey: countingKeys.report(countingId),
    queryFn: () => countingApi.getReport(countingId),
    enabled: !!countingId,
  });
}

// Mutations
export function useCreateCounting() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: CreateCountingFormData) => countingApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: countingKeys.lists() });
      queryClient.invalidateQueries({ queryKey: countingKeys.dashboard() });
      toast.success('Counting operation created successfully');
    },
    onError: (error: Error) => {
      toast.error(`Failed to create counting: ${error.message}`);
    },
  });
}

export function useActivateCounting() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => countingApi.activate(id),
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: countingKeys.detail(id) });
      queryClient.invalidateQueries({ queryKey: countingKeys.dashboard() });
      toast.success('Counting activated');
    },
    onError: (error: Error) => {
      toast.error(`Failed to activate: ${error.message}`);
    },
  });
}

export function useCancelCounting() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) =>
      countingApi.cancel(id, reason),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: countingKeys.detail(id) });
      queryClient.invalidateQueries({ queryKey: countingKeys.dashboard() });
      toast.success('Counting cancelled');
    },
    onError: (error: Error) => {
      toast.error(`Failed to cancel: ${error.message}`);
    },
  });
}

export function useFinalizeCounting() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => countingApi.finalize(id),
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: countingKeys.detail(id) });
      queryClient.invalidateQueries({ queryKey: countingKeys.dashboard() });
      toast.success('Counting finalized successfully');
    },
    onError: (error: Error) => {
      toast.error(`Failed to finalize: ${error.message}`);
    },
  });
}

export function useTriggerThirdCount() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ countingId, itemIds }: { countingId: number; itemIds: number[] }) =>
      countingApi.triggerThirdCount(countingId, itemIds),
    onSuccess: (_, { countingId }) => {
      queryClient.invalidateQueries({ queryKey: countingKeys.reconciliation(countingId) });
      queryClient.invalidateQueries({ queryKey: countingKeys.detail(countingId) });
      toast.success('Third count triggered');
    },
    onError: (error: Error) => {
      toast.error(`Failed to trigger third count: ${error.message}`);
    },
  });
}

export function useManualOverride() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({
      itemId,
      quantity,
      notes,
    }: {
      itemId: number;
      quantity: number;
      notes: string;
    }) => countingApi.manualOverride(itemId, quantity, notes),
    onSuccess: () => {
      // Invalidate all reconciliation queries
      queryClient.invalidateQueries({ queryKey: countingKeys.all });
      toast.success('Override applied');
    },
    onError: (error: Error) => {
      toast.error(`Failed to apply override: ${error.message}`);
    },
  });
}

export function useExportReport() {
  return useMutation({
    mutationFn: ({ id, format }: { id: number; format: 'pdf' | 'xlsx' }) =>
      countingApi.exportReport(id, format),
    onSuccess: (blob, { format }) => {
      // Download the file
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `discrepancy-report.${format}`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      a.remove();
      toast.success('Report downloaded');
    },
    onError: (error: Error) => {
      toast.error(`Failed to export report: ${error.message}`);
    },
  });
}
