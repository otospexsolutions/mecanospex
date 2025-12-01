import { useQuery } from '@tanstack/react-query'
import { getLedger, getJournalEntries } from '../api'
import type { LedgerFilters } from '../types'

export function useLedger(filters?: LedgerFilters) {
  return useQuery({
    queryKey: ['ledger', filters],
    queryFn: () => getLedger(filters),
  })
}

export function useJournalEntries(page = 1) {
  return useQuery({
    queryKey: ['journal-entries', page],
    queryFn: () => getJournalEntries(page),
  })
}
