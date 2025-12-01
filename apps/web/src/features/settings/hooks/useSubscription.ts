import { useQuery } from '@tanstack/react-query'
import { getSubscription } from '../api/subscription'

export function useSubscription() {
  return useQuery({
    queryKey: ['subscription'],
    queryFn: () => getSubscription(),
  })
}
