/**
 * CRITICAL SECURITY: This screen must NEVER display:
 * - theoretical_qty / expected quantity
 * - Other counters' results (count_1_qty, count_2_qty, count_3_qty)
 * - Any hints about what the count "should" be
 *
 * This is BLIND COUNTING - the counter must count without bias.
 */

import { useState, useEffect } from 'react';
import {
  View,
  Image,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
  StyleSheet,
} from 'react-native';
import { Text, Button, TextInput, Card, IconButton } from 'react-native-paper';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useCountingItem } from '@/features/counting/api/queries';
import { useSubmitCount } from '@/features/counting/hooks/useSubmitCount';
import { Minus, Plus } from 'lucide-react-native';

export default function ItemCountScreen() {
  const { id, itemId } = useLocalSearchParams<{ id: string; itemId: string }>();
  const router = useRouter();

  const countingId = parseInt(id, 10);
  const itemIdNum = parseInt(itemId, 10);

  const { data: item, isLoading } = useCountingItem(countingId, itemIdNum);
  const submitCount = useSubmitCount();

  const [quantity, setQuantity] = useState<string>('');
  const [notes, setNotes] = useState('');

  // Set initial quantity from existing count if available
  useEffect(() => {
    if (item?.my_count !== null && item?.my_count !== undefined) {
      setQuantity(item.my_count.toString());
    }
  }, [item?.my_count]);

  if (isLoading || !item) {
    return (
      <View style={styles.centered}>
        <Text>Loading...</Text>
      </View>
    );
  }

  const handleIncrement = () => {
    const current = parseInt(quantity, 10) || 0;
    setQuantity((current + 1).toString());
  };

  const handleDecrement = () => {
    const current = parseInt(quantity, 10) || 0;
    if (current > 0) {
      setQuantity((current - 1).toString());
    }
  };

  const handleSubmit = async (scanNext: boolean = false) => {
    const qty = parseFloat(quantity);
    if (isNaN(qty) || qty < 0) return;

    await submitCount.mutateAsync({
      countingId,
      itemId: itemIdNum,
      quantity: qty,
      notes: notes || undefined,
    });

    if (scanNext) {
      router.replace(`/counting/${countingId}/scan` as const);
    } else {
      router.back();
    }
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={styles.container}
    >
      <ScrollView style={styles.scroll}>
        {/* Product Image */}
        {item.product.image_url && (
          <Image
            source={{ uri: item.product.image_url }}
            style={styles.image}
            resizeMode="contain"
          />
        )}

        <View style={styles.content}>
          {/* Product Info */}
          <Card style={styles.card}>
            <Card.Content>
              <Text variant="titleLarge">{item.product.name}</Text>
              {item.variant && (
                <Text variant="bodyMedium" style={styles.variant}>
                  {item.variant.name}
                </Text>
              )}
              <View style={styles.infoRow}>
                <View>
                  <Text variant="labelSmall" style={styles.label}>
                    SKU
                  </Text>
                  <Text variant="bodyMedium">{item.product.sku}</Text>
                </View>
                {item.product.barcode && (
                  <View>
                    <Text variant="labelSmall" style={styles.label}>
                      Barcode
                    </Text>
                    <Text variant="bodyMedium">{item.product.barcode}</Text>
                  </View>
                )}
              </View>
            </Card.Content>
          </Card>

          {/* Location */}
          <Card style={styles.card}>
            <Card.Content>
              <Text variant="labelSmall" style={styles.label}>
                Location
              </Text>
              <Text variant="titleMedium">{item.location.code}</Text>
              <Text variant="bodySmall" style={styles.locationDetail}>
                {item.location.name} - {item.warehouse.name}
              </Text>
            </Card.Content>
          </Card>

          {/* Quantity Input */}
          <Card style={styles.card}>
            <Card.Content>
              <Text variant="titleMedium" style={styles.quantityTitle}>
                Enter Quantity Counted
              </Text>

              <View style={styles.quantityRow}>
                <IconButton
                  icon={() => <Minus size={24} />}
                  mode="outlined"
                  size={32}
                  onPress={handleDecrement}
                />

                <TextInput
                  value={quantity}
                  onChangeText={setQuantity}
                  keyboardType="decimal-pad"
                  mode="outlined"
                  style={styles.quantityInput}
                  placeholder="0"
                />

                <IconButton
                  icon={() => <Plus size={24} />}
                  mode="outlined"
                  size={32}
                  onPress={handleIncrement}
                />
              </View>

              <Text variant="bodySmall" style={styles.unit}>
                Unit: {item.unit_of_measure}
              </Text>
            </Card.Content>
          </Card>

          {/* Notes */}
          <TextInput
            label="Notes (optional)"
            value={notes}
            onChangeText={setNotes}
            mode="outlined"
            multiline
            numberOfLines={3}
            style={styles.notes}
            placeholder="Add any observations..."
          />

          {/* Submit Buttons */}
          <View style={styles.buttons}>
            <Button
              mode="contained"
              onPress={() => handleSubmit(false)}
              loading={submitCount.isPending}
              disabled={!quantity || submitCount.isPending}
              contentStyle={styles.buttonContent}
            >
              Save Count
            </Button>

            <Button
              mode="outlined"
              onPress={() => handleSubmit(true)}
              loading={submitCount.isPending}
              disabled={!quantity || submitCount.isPending}
              contentStyle={styles.buttonContent}
            >
              Save & Scan Next
            </Button>
          </View>

          {/*
            SECURITY NOTE:
            We intentionally do NOT display theoretical_qty here.
            This is blind counting - the counter must not know the expected value.
          */}
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  scroll: { flex: 1, backgroundColor: '#f3f4f6' },
  image: { width: '100%', height: 192, backgroundColor: '#e5e7eb' },
  content: { padding: 16 },
  card: { marginBottom: 16 },
  variant: { color: '#6b7280' },
  infoRow: { flexDirection: 'row', gap: 24, marginTop: 8 },
  label: { color: '#9ca3af' },
  locationDetail: { color: '#6b7280' },
  quantityTitle: { textAlign: 'center', marginBottom: 16 },
  quantityRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 16,
  },
  quantityInput: { width: 120, textAlign: 'center', fontSize: 24 },
  unit: { textAlign: 'center', marginTop: 8, color: '#6b7280' },
  notes: { marginBottom: 16 },
  buttons: { gap: 12 },
  buttonContent: { height: 48 },
});
