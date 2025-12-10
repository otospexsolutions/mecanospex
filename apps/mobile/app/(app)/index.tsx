import { View, StyleSheet } from 'react-native';
import { Text, Card, Button } from 'react-native-paper';
import { useRouter } from 'expo-router';
import { useAuth } from '@/providers/AuthProvider';
import { useCountingTasks } from '@/features/counting/api/queries';
import { OfflineIndicator } from '@/components/OfflineIndicator';
import { ClipboardList, ScanLine } from 'lucide-react-native';

export default function HomeScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const { data: tasks } = useCountingTasks();

  const activeTasks = tasks?.filter((t) => t.status === 'in_progress') ?? [];
  const pendingTasks = tasks?.filter((t) => t.status === 'pending') ?? [];

  return (
    <View style={styles.container}>
      <OfflineIndicator />

      <View style={styles.content}>
        <Text variant="headlineMedium" style={styles.greeting}>
          Welcome, {user?.name ?? 'Counter'}
        </Text>

        <View style={styles.stats}>
          <Card style={styles.statCard}>
            <Card.Content style={styles.statContent}>
              <ClipboardList size={32} color="#2563eb" />
              <Text variant="displaySmall" style={styles.statNumber}>
                {activeTasks.length}
              </Text>
              <Text variant="bodyMedium" style={styles.statLabel}>
                Active Tasks
              </Text>
            </Card.Content>
          </Card>

          <Card style={styles.statCard}>
            <Card.Content style={styles.statContent}>
              <ScanLine size={32} color="#64748b" />
              <Text variant="displaySmall" style={styles.statNumber}>
                {pendingTasks.length}
              </Text>
              <Text variant="bodyMedium" style={styles.statLabel}>
                Pending
              </Text>
            </Card.Content>
          </Card>
        </View>

        {activeTasks.length > 0 && (
          <Card style={styles.quickAction}>
            <Card.Content>
              <Text variant="titleMedium">Continue Counting</Text>
              <Text variant="bodySmall" style={styles.taskInfo}>
                {activeTasks[0].scope_type.replace('_', ' ')} - #
                {activeTasks[0].uuid.slice(0, 8)}
              </Text>
              <Text variant="bodySmall" style={styles.progress}>
                {activeTasks[0].progress.counted} / {activeTasks[0].progress.total}{' '}
                items counted
              </Text>
            </Card.Content>
            <Card.Actions>
              <Button
                mode="contained"
                onPress={() =>
                  router.push(`/counting/${activeTasks[0].id}` as const)
                }
              >
                Continue
              </Button>
            </Card.Actions>
          </Card>
        )}

        <Button
          mode="outlined"
          onPress={() => router.push('/tasks')}
          style={styles.viewAllButton}
          contentStyle={styles.viewAllContent}
        >
          View All Tasks
        </Button>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f3f4f6',
  },
  content: {
    flex: 1,
    padding: 16,
  },
  greeting: {
    fontWeight: '600',
    marginBottom: 24,
  },
  stats: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 24,
  },
  statCard: {
    flex: 1,
  },
  statContent: {
    alignItems: 'center',
    gap: 8,
  },
  statNumber: {
    fontWeight: '700',
    color: '#1f2937',
  },
  statLabel: {
    color: '#6b7280',
  },
  quickAction: {
    marginBottom: 16,
  },
  taskInfo: {
    color: '#6b7280',
    marginTop: 4,
  },
  progress: {
    color: '#2563eb',
    marginTop: 8,
  },
  viewAllButton: {
    marginTop: 8,
  },
  viewAllContent: {
    height: 48,
  },
});
