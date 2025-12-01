import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getAccounts, getAccount, createAccount, updateAccount } from '../api'
import type { AccountFilters, UpdateAccountData } from '../types'

export function useAccounts(filters?: AccountFilters) {
  return useQuery({
    queryKey: ['accounts', filters],
    queryFn: () => getAccounts(filters),
  })
}

export function useAccount(id: string) {
  return useQuery({
    queryKey: ['accounts', id],
    queryFn: () => getAccount(id),
    enabled: !!id,
  })
}

export function useCreateAccount() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: createAccount,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['accounts'] })
    },
  })
}

export function useUpdateAccount() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: UpdateAccountData }) =>
      updateAccount(id, data),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['accounts'] })
    },
  })
}
