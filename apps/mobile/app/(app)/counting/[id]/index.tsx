import { View, FlatList, RefreshControl, StyleSheet } from 'react-native';
import {
  Text,
  Card,
  ProgressBar,
  ActivityIndicator,
  Button,
  FAB,
  Chip,
} from 'react-native-paper';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useCountingSession } from '@/features/counting/api/queries';
import { OfflineIndicator } from '@/components/OfflineIndicator';
import { ScanLine, Check, Clock } from 'lucide-react-native';

export default function CountingSessionScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const countingId = parseInt(id, 10);

  const {
    data: session,
    isLoading,
    refetch,
    isRefetching,
  } = useCountingSession(countingId);

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!session) {
    return (
      <View style={styles.centered}>
        <Text>Session not found</Text>
      </View>
    );
  }

  const progress =
    session.progress.total > 0
      ? session.progress.counted / session.progress.total
      : 0;

  return (
    <View style={styles.container}>
      <OfflineIndicator />

      {/* Header */}
      <View style={styles.header}>
        <View style={styles.headerInfo}>
          <Text variant="titleMedium">Session #{session.counting.uuid.slice(0, 8)}</Text>
          <Chip compact style={styles.countChip}>
            Count #{session.my_count_number}
          </Chip>
        </View>

        <View style={styles.progressSection}>
          <ProgressBar progress={progress} style={styles.progressBar} />
          <Text variant="bodySmall" style={styles.progressText}>
            {session.progress.counted} / {session.progress.total} items counted
          </Text>
        </View>

        {session.counting.instructions && (
          <Card style={styles.instructionsCard}>
            <Card.Content>
              <Text variant="labelSmall" style={styles.instructionsLabel}>
                Instructions
              </Text>
              <Text variant="bodySmall">{session.counting.instructions}</Text>
            </Card.Content>
          </Card>
        )}
      </View>

      {/* Items List */}
      <FlatList
        data={session.items}
        keyExtractor={(item) => item.id.toString()}
        renderItem={({ item }) => (
          <Card
            style={[styles.itemCard, item.is_counted && styles.countedCard]}
            onPress={() =>
              router.push(`/counting/${countingId}/item/${item.id}` as const)
            }
          >
            <Card.Content style={styles.itemContent}>
              <View style={styles.itemInfo}>
                <Text variant="titleSmall" numberOfLines={1}>
                  {item.product.name}
                </Text>
                <Text variant="bodySmall" style={styles.sku}>
                  {item.product.sku}
                </Text>
                <Text variant="bodySmall" style={styles.location}>
                  {item.location.code} - {item.warehouse.name}
                </Text>
              </View>

              <View style={styles.itemStatus}>
                {item.is_counted ? (
                  <View style={styles.countedBadge}>
                    <Check size={16} color="#16a34a" />
                    <Text style={styles.countedQty}>{item.my_count}</Text>
                  </View>
                ) : (
                  <Clock size={20} color="#9ca3af" />
                )}
              </View>
            </Card.Content>
          </Card>
        )}
        refreshControl={
          <RefreshControl refreshing={isRefetching} onRefresh={refetch} />
        }
        contentContainerStyle={styles.list}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text variant="bodyLarge" style={styles.emptyText}>
              No items to count
            </Text>
          </View>
        }
      />

      {/* Scan FAB */}
      <FAB
        icon={() => <ScanLine size={24} color="#fff" />}
        label="Scan"
        style={styles.fab}
        onPress={() => router.push(`/counting/${countingId}/scan` as const)}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f3f4f6' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { padding: 16, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#e5e7eb' },
  headerInfo: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  countChip: { backgroundColor: '#dbeafe' },
  progressSection: { marginTop: 16 },
  progressBar: { height: 8, borderRadius: 4 },
  progressText: { color: '#6b7280', marginTop: 4 },
  instructionsCard: { marginTop: 12, backgroundColor: '#f0fdf4' },
  instructionsLabel: { color: '#16a34a', marginBottom: 4 },
  list: { padding: 16, paddingBottom: 100 },
  itemCard: { marginBottom: 8 },
  countedCard: { backgroundColor: '#f0fdf4', borderColor: '#86efac', borderWidth: 1 },
  itemContent: { flexDirection: 'row', alignItems: 'center' },
  itemInfo: { flex: 1 },
  sku: { color: '#6b7280', marginTop: 2 },
  location: { color: '#9ca3af', marginTop: 2 },
  itemStatus: { marginLeft: 12 },
  countedBadge: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  countedQty: { color: '#16a34a', fontWeight: '600', fontSize: 16 },
  empty: { paddingVertical: 48, alignItems: 'center' },
  emptyText: { color: '#6b7280' },
  fab: { position: 'absolute', right: 16, bottom: 16, backgroundColor: '#2563eb' },
});
