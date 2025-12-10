import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import AsyncStorage from '@react-native-async-storage/async-storage';

interface PendingCount {
  id: string;
  countingId: number;
  itemId: number;
  quantity: number;
  notes?: string;
  countedAt: string;
  synced: boolean;
  syncError?: string;
}

interface CountingState {
  pendingCounts: PendingCount[];
  activeTaskId: number | null;

  addPendingCount: (
    count: Omit<PendingCount, 'id' | 'synced' | 'countedAt'>
  ) => string;
  markSynced: (id: string) => void;
  markSyncError: (id: string, error: string) => void;
  removePendingCount: (id: string) => void;
  setActiveTask: (id: number | null) => void;
  getPendingForItem: (
    countingId: number,
    itemId: number
  ) => PendingCount | undefined;
}

export const useCountingStore = create<CountingState>()(
  persist(
    (set, get) => ({
      pendingCounts: [],
      activeTaskId: null,

      addPendingCount: (count) => {
        const id = `pending_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        const pendingCount: PendingCount = {
          ...count,
          id,
          synced: false,
          countedAt: new Date().toISOString(),
        };

        set((state) => ({
          pendingCounts: [...state.pendingCounts, pendingCount],
        }));

        return id;
      },

      markSynced: (id) => {
        set((state) => ({
          pendingCounts: state.pendingCounts.filter((c) => c.id !== id),
        }));
      },

      markSyncError: (id, error) => {
        set((state) => ({
          pendingCounts: state.pendingCounts.map((c) =>
            c.id === id ? { ...c, syncError: error } : c
          ),
        }));
      },

      removePendingCount: (id) => {
        set((state) => ({
          pendingCounts: state.pendingCounts.filter((c) => c.id !== id),
        }));
      },

      setActiveTask: (id) => set({ activeTaskId: id }),

      getPendingForItem: (countingId, itemId) => {
        return get().pendingCounts.find(
          (c) => c.countingId === countingId && c.itemId === itemId && !c.synced
        );
      },
    }),
    {
      name: 'counting-offline-storage',
      storage: createJSONStorage(() => AsyncStorage),
    }
  )
);
