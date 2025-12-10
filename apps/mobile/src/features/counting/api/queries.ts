import { useQuery } from '@tanstack/react-query';
import { countingApi } from './countingApi';

export const countingKeys = {
  all: ['counting'] as const,
  tasks: () => [...countingKeys.all, 'tasks'] as const,
  session: (id: number) => [...countingKeys.all, 'session', id] as const,
  items: (id: number) => [...countingKeys.all, 'items', id] as const,
};

export function useCountingTasks() {
  return useQuery({
    queryKey: countingKeys.tasks(),
    queryFn: countingApi.getTasks,
  });
}

export function useCountingSession(countingId: number) {
  return useQuery({
    queryKey: countingKeys.session(countingId),
    queryFn: () => countingApi.getSession(countingId),
    enabled: !!countingId,
  });
}

export function useCountingItems(countingId: number, uncountedOnly = false) {
  return useQuery({
    queryKey: [...countingKeys.items(countingId), { uncountedOnly }],
    queryFn: () => countingApi.getItems(countingId, uncountedOnly),
    enabled: !!countingId,
  });
}

export function useCountingItem(countingId: number, itemId: number) {
  const { data: session, isLoading } = useCountingSession(countingId);

  return {
    data: session?.items.find((item) => item.id === itemId),
    isLoading,
  };
}
