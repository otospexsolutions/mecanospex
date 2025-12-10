import { View, FlatList, RefreshControl, StyleSheet } from 'react-native';
import { Text, Card, ProgressBar, ActivityIndicator } from 'react-native-paper';
import { useRouter } from 'expo-router';
import { useCountingTasks } from '@/features/counting/api/queries';
import { OfflineIndicator } from '@/components/OfflineIndicator';
import { format, isPast } from 'date-fns';
import { AlertTriangle } from 'lucide-react-native';

export default function TasksScreen() {
  const router = useRouter();
  const { data: tasks, isLoading, refetch, isRefetching } = useCountingTasks();

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <OfflineIndicator />

      <FlatList
        data={tasks}
        keyExtractor={(item) => item.id.toString()}
        renderItem={({ item }) => {
          const progress =
            item.progress.total > 0
              ? item.progress.counted / item.progress.total
              : 0;
          const isOverdue =
            item.scheduled_end && isPast(new Date(item.scheduled_end));

          return (
            <Card
              style={[styles.card, isOverdue && styles.overdueCard]}
              onPress={() => router.push(`/counting/${item.id}` as const)}
            >
              <Card.Content>
                <View style={styles.cardHeader}>
                  <Text variant="titleMedium">
                    {item.scope_type.replace('_', ' ')} Count
                  </Text>
                  {isOverdue && (
                    <View style={styles.overdueBadge}>
                      <AlertTriangle size={14} color="#dc2626" />
                      <Text style={styles.overdueText}>OVERDUE</Text>
                    </View>
                  )}
                </View>

                <Text variant="bodySmall" style={styles.uuid}>
                  #{item.uuid.slice(0, 8)}
                </Text>

                <View style={styles.progressContainer}>
                  <ProgressBar progress={progress} style={styles.progressBar} />
                  <Text variant="bodySmall" style={styles.progressText}>
                    {item.progress.counted} / {item.progress.total} items
                  </Text>
                </View>

                {item.scheduled_end && (
                  <Text variant="bodySmall" style={styles.deadline}>
                    Deadline:{' '}
                    {format(new Date(item.scheduled_end), 'MMM d, h:mm a')}
                  </Text>
                )}
              </Card.Content>
            </Card>
          );
        }}
        refreshControl={
          <RefreshControl refreshing={isRefetching} onRefresh={refetch} />
        }
        contentContainerStyle={styles.list}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text variant="bodyLarge" style={styles.emptyText}>
              No counting tasks assigned
            </Text>
          </View>
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f3f4f6' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  list: { padding: 16, gap: 12 },
  card: { marginBottom: 12 },
  overdueCard: {
    borderColor: '#fca5a5',
    borderWidth: 1,
    backgroundColor: '#fef2f2',
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  overdueBadge: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  overdueText: { color: '#dc2626', fontSize: 12, fontWeight: '600' },
  uuid: { color: '#6b7280', marginTop: 2 },
  progressContainer: { marginTop: 12 },
  progressBar: { height: 8, borderRadius: 4 },
  progressText: { color: '#6b7280', marginTop: 4 },
  deadline: { color: '#6b7280', marginTop: 8 },
  empty: { paddingVertical: 48, alignItems: 'center' },
  emptyText: { color: '#6b7280' },
});
