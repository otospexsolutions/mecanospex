import { View, StyleSheet, Alert } from 'react-native';
import { Text, Card, Button, Avatar } from 'react-native-paper';
import { useRouter } from 'expo-router';
import { useAuth } from '@/providers/AuthProvider';
import { useCountingStore } from '@/features/counting/store/countingStore';
import { LogOut, CloudOff, User } from 'lucide-react-native';

export default function ProfileScreen() {
  const router = useRouter();
  const { user, logout } = useAuth();
  const pendingCounts = useCountingStore((s) =>
    s.pendingCounts.filter((c) => !c.synced)
  );

  const handleLogout = async () => {
    if (pendingCounts.length > 0) {
      Alert.alert(
        'Pending Counts',
        `You have ${pendingCounts.length} count(s) waiting to sync. Logging out may lose this data. Continue?`,
        [
          { text: 'Cancel', style: 'cancel' },
          {
            text: 'Logout Anyway',
            style: 'destructive',
            onPress: async () => {
              await logout();
              router.replace('/login');
            },
          },
        ]
      );
    } else {
      await logout();
      router.replace('/login');
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.content}>
        <Card style={styles.profileCard}>
          <Card.Content style={styles.profileContent}>
            <Avatar.Icon size={80} icon={() => <User size={48} color="#fff" />} />
            <Text variant="headlineSmall" style={styles.name}>
              {user?.name ?? 'User'}
            </Text>
            <Text variant="bodyMedium" style={styles.email}>
              {user?.email ?? ''}
            </Text>
          </Card.Content>
        </Card>

        {pendingCounts.length > 0 && (
          <Card style={styles.warningCard}>
            <Card.Content style={styles.warningContent}>
              <CloudOff size={24} color="#b45309" />
              <View style={styles.warningText}>
                <Text variant="titleSmall" style={styles.warningTitle}>
                  Pending Sync
                </Text>
                <Text variant="bodySmall" style={styles.warningDesc}>
                  {pendingCounts.length} count(s) waiting to sync
                </Text>
              </View>
            </Card.Content>
          </Card>
        )}

        <Button
          mode="outlined"
          onPress={handleLogout}
          icon={() => <LogOut size={20} color="#dc2626" />}
          textColor="#dc2626"
          style={styles.logoutButton}
          contentStyle={styles.logoutContent}
        >
          Sign Out
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
  profileCard: {
    marginBottom: 16,
  },
  profileContent: {
    alignItems: 'center',
    paddingVertical: 24,
  },
  name: {
    fontWeight: '600',
    marginTop: 16,
  },
  email: {
    color: '#6b7280',
    marginTop: 4,
  },
  warningCard: {
    backgroundColor: '#fef3c7',
    marginBottom: 16,
  },
  warningContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  warningText: {
    flex: 1,
  },
  warningTitle: {
    color: '#b45309',
    fontWeight: '600',
  },
  warningDesc: {
    color: '#92400e',
  },
  logoutButton: {
    borderColor: '#dc2626',
    marginTop: 'auto',
  },
  logoutContent: {
    height: 48,
  },
});
