import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { countingApi } from './countingApi'
import type { CountingFilters, CreateCountingFormData } from '../types'
import { toast } from 'sonner'
import { useTranslation } from 'react-i18next'

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
}

// Queries
export function useCountingDashboard() {
  return useQuery({
    queryKey: countingKeys.dashboard(),
    queryFn: countingApi.getDashboard,
    refetchInterval: 30000, // Refresh every 30s
  })
}

export function useCountingList(filters: CountingFilters) {
  return useQuery({
    queryKey: countingKeys.list(filters),
    queryFn: () => countingApi.list(filters),
  })
}

export function useCountingDetail(id: number) {
  return useQuery({
    queryKey: countingKeys.detail(id),
    queryFn: () => countingApi.getDetail(id),
    enabled: !!id,
  })
}

export function useReconciliation(countingId: number) {
  return useQuery({
    queryKey: countingKeys.reconciliation(countingId),
    queryFn: () => countingApi.getReconciliation(countingId),
    enabled: !!countingId,
  })
}

export function useDiscrepancyReport(countingId: number) {
  return useQuery({
    queryKey: countingKeys.report(countingId),
    queryFn: () => countingApi.getReport(countingId),
    enabled: !!countingId,
  })
}

// Mutations
export function useCreateCounting() {
  const queryClient = useQueryClient()
  const { t } = useTranslation('inventory')

  return useMutation({
    mutationFn: (data: CreateCountingFormData) => countingApi.create(data),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: countingKeys.lists() })
      void queryClient.invalidateQueries({ queryKey: countingKeys.dashboard() })
      toast.success(t('counting.messages.created'))
    },
    onError: (error: Error) => {
      toast.error(t('counting.messages.createFailed', { error: error.message }))
    },
  })
}

export function useActivateCounting() {
  const queryClient = useQueryClient()
  const { t } = useTranslation('inventory')

  return useMutation({
    mutationFn: (id: number) => countingApi.activate(id),
    onSuccess: (_, id) => {
      void queryClient.invalidateQueries({ queryKey: countingKeys.detail(id) })
      void queryClient.invalidateQueries({ queryKey: countingKeys.dashboard() })
      toast.success(t('counting.messages.activated'))
    },
    onError: (error: Error) => {
      toast.error(t('counting.messages.activateFailed', { error: error.message }))
    },
  })
}

export function useCancelCounting() {
  const queryClient = useQueryClient()
  const { t } = useTranslation('inventory')

  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) =>
      countingApi.cancel(id, reason),
    onSuccess: (_, { id }) => {
      void queryClient.invalidateQueries({ queryKey: countingKeys.detail(id) })
      void queryClient.invalidateQueries({ queryKey: countingKeys.dashboard() })
      toast.success(t('counting.messages.cancelled'))
    },
    onError: (error: Error) => {
      toast.error(t('counting.messages.cancelFailed', { error: error.message }))
    },
  })
}

export function useFinalizeCounting() {
  const queryClient = useQueryClient()
  const { t } = useTranslation('inventory')

  return useMutation({
    mutationFn: (id: number) => countingApi.finalize(id),
    onSuccess: (_, id) => {
      void queryClient.invalidateQueries({ queryKey: countingKeys.detail(id) })
      void queryClient.invalidateQueries({ queryKey: countingKeys.dashboard() })
      toast.success(t('counting.messages.finalized'))
    },
    onError: (error: Error) => {
      toast.error(t('counting.messages.finalizeFailed', { error: error.message }))
    },
  })
}

export function useTriggerThirdCount() {
  const queryClient = useQueryClient()
  const { t } = useTranslation('inventory')

  return useMutation({
    mutationFn: ({ countingId, itemIds }: { countingId: number; itemIds: number[] }) =>
      countingApi.triggerThirdCount(countingId, itemIds),
    onSuccess: (_, { countingId }) => {
      void queryClient.invalidateQueries({ queryKey: countingKeys.reconciliation(countingId) })
      void queryClient.invalidateQueries({ queryKey: countingKeys.detail(countingId) })
      toast.success(t('counting.messages.thirdCountTriggered'))
    },
    onError: (error: Error) => {
      toast.error(t('counting.messages.thirdCountFailed', { error: error.message }))
    },
  })
}

export function useManualOverride(countingId: number) {
  const queryClient = useQueryClient()
  const { t } = useTranslation('inventory')

  return useMutation({
    mutationFn: ({
      itemId,
      quantity,
      notes,
    }: {
      itemId: number
      quantity: number
      notes: string
    }) => countingApi.manualOverride(itemId, quantity, notes),
    onSuccess: () => {
      // Invalidate reconciliation for this counting
      void queryClient.invalidateQueries({ queryKey: countingKeys.reconciliation(countingId) })
      void queryClient.invalidateQueries({ queryKey: countingKeys.detail(countingId) })
      toast.success(t('counting.messages.overrideApplied'))
    },
    onError: (error: Error) => {
      toast.error(t('counting.messages.overrideFailed', { error: error.message }))
    },
  })
}

export function useExportReport() {
  const { t } = useTranslation('inventory')

  return useMutation({
    mutationFn: ({ id, format }: { id: number; format: 'pdf' | 'xlsx' }) =>
      countingApi.exportReport(id, format),
    onSuccess: (blob, { format }) => {
      // Download the file
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `discrepancy-report.${format}`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      a.remove()
      toast.success(t('counting.messages.reportDownloaded'))
    },
    onError: (error: Error) => {
      toast.error(t('counting.messages.exportFailed', { error: error.message }))
    },
  })
}

export function useSendReminder() {
  const { t } = useTranslation('inventory')

  return useMutation({
    mutationFn: (id: number) => countingApi.sendReminder(id),
    onSuccess: () => {
      toast.success(t('counting.messages.reminderSent'))
    },
    onError: (error: Error) => {
      toast.error(t('counting.messages.reminderFailed', { error: error.message }))
    },
  })
}
