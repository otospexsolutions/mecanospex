import { useQuery } from '@tanstack/react-query'
import { getJournalEntries, getJournalEntry } from '../api'

export function useJournalEntries(page = 1) {
  return useQuery({
    queryKey: ['journal-entries', page],
    queryFn: () => getJournalEntries(page),
  })
}

export function useJournalEntry(id: string | undefined) {
  return useQuery({
    queryKey: ['journal-entry', id],
    queryFn: () => getJournalEntry(id!),
    enabled: !!id,
  })
}
