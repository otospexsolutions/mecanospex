import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { createJournalEntry, postJournalEntry } from '../api'
import type { CreateJournalEntryData } from '../api'

export function useCreateJournalEntry() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()

  return useMutation({
    mutationFn: (data: CreateJournalEntryData) => createJournalEntry(data),
    onSuccess: (data) => {
      void queryClient.invalidateQueries({ queryKey: ['journal-entries'] })
      navigate(`/finance/journal-entries/${data.id}`)
    },
  })
}

export function usePostJournalEntry() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: string) => postJournalEntry(id),
    onSuccess: (data) => {
      void queryClient.invalidateQueries({ queryKey: ['journal-entries'] })
      void queryClient.invalidateQueries({ queryKey: ['journal-entry', data.id] })
    },
  })
}
