import { useMutation, useQueryClient } from '@tanstack/react-query';
import NetInfo from '@react-native-community/netinfo';
import { useCountingStore } from '../store/countingStore';
import { countingApi } from '../api/countingApi';
import { countingKeys } from '../api/queries';
import { AxiosError } from 'axios';

interface SubmitCountParams {
  countingId: number;
  itemId: number;
  quantity: number;
  notes?: string;
}

export function useSubmitCount() {
  const queryClient = useQueryClient();
  const { addPendingCount } = useCountingStore();

  return useMutation({
    mutationFn: async (params: SubmitCountParams) => {
      const netState = await NetInfo.fetch();

      // If offline, save to local queue
      if (!netState.isConnected) {
        const pendingId = addPendingCount(params);
        return { offline: true, pendingId };
      }

      // Try to submit online
      try {
        await countingApi.submitCount(
          params.countingId,
          params.itemId,
          params.quantity,
          params.notes
        );
        return { offline: false };
      } catch (error) {
        // Network error - save offline
        const axiosError = error as AxiosError;
        if (
          axiosError.code === 'ERR_NETWORK' ||
          axiosError.message?.includes('Network')
        ) {
          const pendingId = addPendingCount(params);
          return { offline: true, pendingId };
        }
        throw error;
      }
    },

    onSuccess: (_, params) => {
      queryClient.invalidateQueries({
        queryKey: countingKeys.session(params.countingId),
      });
      queryClient.invalidateQueries({
        queryKey: countingKeys.tasks(),
      });
    },
  });
}
