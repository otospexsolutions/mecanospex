import { View, StyleSheet } from 'react-native';
import { Text, ActivityIndicator } from 'react-native-paper';
import { useNetInfo } from '@react-native-community/netinfo';
import { useCountingStore } from '@/features/counting/store/countingStore';
import { WifiOff, CloudUpload } from 'lucide-react-native';

export function OfflineIndicator() {
  const netInfo = useNetInfo();
  const pendingCounts = useCountingStore((s) =>
    s.pendingCounts.filter((c) => !c.synced)
  );

  // Online and no pending - show nothing
  if (netInfo.isConnected && pendingCounts.length === 0) {
    return null;
  }

  // Offline
  if (!netInfo.isConnected) {
    return (
      <View style={[styles.banner, styles.offlineBanner]}>
        <WifiOff size={20} color="#b45309" />
        <Text style={styles.offlineText}>
          You're offline. Counts will sync when connected.
        </Text>
      </View>
    );
  }

  // Online with pending counts
  if (pendingCounts.length > 0) {
    return (
      <View style={[styles.banner, styles.syncingBanner]}>
        <CloudUpload size={20} color="#2563eb" />
        <ActivityIndicator size="small" color="#2563eb" />
        <Text style={styles.syncingText}>
          Syncing {pendingCounts.length} pending count
          {pendingCounts.length > 1 ? 's' : ''}...
        </Text>
      </View>
    );
  }

  return null;
}

const styles = StyleSheet.create({
  banner: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    gap: 8,
  },
  offlineBanner: { backgroundColor: '#fef3c7' },
  offlineText: { color: '#b45309', flex: 1 },
  syncingBanner: { backgroundColor: '#dbeafe' },
  syncingText: { color: '#2563eb', flex: 1 },
});
