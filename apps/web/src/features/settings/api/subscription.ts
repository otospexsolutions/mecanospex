import { apiGet } from '@/lib/api'
import type { SubscriptionInfo } from '../types/subscription'

export async function getSubscription(): Promise<SubscriptionInfo> {
  const response = await apiGet<{ data: SubscriptionInfo }>('/subscription')
  return response.data
}
