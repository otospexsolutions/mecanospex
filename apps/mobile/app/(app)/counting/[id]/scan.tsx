import { useState } from 'react';
import { View, StyleSheet, Alert, TextInput as RNTextInput } from 'react-native';
import { Text, Button, IconButton, ActivityIndicator } from 'react-native-paper';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { countingApi } from '@/features/counting/api/countingApi';
import { Flashlight, FlashlightOff, X, Keyboard } from 'lucide-react-native';

export default function BarcodeScannerScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const countingId = parseInt(id, 10);
  const [permission, requestPermission] = useCameraPermissions();
  const [torch, setTorch] = useState(false);
  const [scanned, setScanned] = useState(false);
  const [isLooking, setIsLooking] = useState(false);

  const handleBarcodeScan = async ({ data }: { data: string }) => {
    if (scanned || isLooking) return;
    setScanned(true);
    setIsLooking(true);

    try {
      const result = await countingApi.lookupByBarcode(countingId, data);

      if (result.found && result.data) {
        router.push(`/counting/${countingId}/item/${result.data.id}` as const);
      } else {
        Alert.alert('Not Found', 'This product is not part of the current count.', [
          { text: 'Scan Again', onPress: () => setScanned(false) },
        ]);
      }
    } catch (error) {
      Alert.alert('Error', 'Failed to look up barcode', [
        { text: 'Try Again', onPress: () => setScanned(false) },
      ]);
    } finally {
      setIsLooking(false);
    }
  };

  const handleManualEntry = () => {
    Alert.prompt(
      'Enter Barcode',
      'Type the barcode number manually',
      async (barcode) => {
        if (barcode) {
          await handleBarcodeScan({ data: barcode });
        }
      },
      'plain-text'
    );
  };

  if (!permission) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!permission.granted) {
    return (
      <View style={styles.permissionContainer}>
        <Text variant="bodyLarge" style={styles.permissionText}>
          Camera permission is required to scan barcodes
        </Text>
        <Button mode="contained" onPress={requestPermission}>
          Grant Permission
        </Button>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <CameraView
        style={StyleSheet.absoluteFillObject}
        facing="back"
        enableTorch={torch}
        barcodeScannerSettings={{
          barcodeTypes: [
            'ean13',
            'ean8',
            'upc_a',
            'upc_e',
            'code128',
            'code39',
            'qr',
          ],
        }}
        onBarcodeScanned={scanned ? undefined : handleBarcodeScan}
      />

      {/* Scan Frame */}
      <View style={styles.overlay}>
        <View style={styles.scanFrame} />
        <Text style={styles.instructions}>
          {isLooking ? 'Looking up...' : 'Position barcode in frame'}
        </Text>
      </View>

      {/* Top Controls */}
      <View style={styles.topControls}>
        <IconButton
          icon={() => <X size={24} color="white" />}
          onPress={() => router.back()}
          style={styles.iconButton}
        />
        <IconButton
          icon={() =>
            torch ? (
              <FlashlightOff size={24} color="white" />
            ) : (
              <Flashlight size={24} color="white" />
            )
          }
          onPress={() => setTorch(!torch)}
          style={styles.iconButton}
        />
      </View>

      {/* Bottom Controls */}
      <View style={styles.bottomControls}>
        <Button
          mode="contained"
          icon={() => <Keyboard size={20} color="white" />}
          onPress={handleManualEntry}
        >
          Enter Manually
        </Button>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: 'black' },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  permissionContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 16,
  },
  permissionText: { textAlign: 'center', marginBottom: 16 },
  overlay: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: 'center',
    alignItems: 'center',
  },
  scanFrame: {
    width: 288,
    height: 288,
    borderWidth: 2,
    borderColor: 'white',
    borderRadius: 8,
  },
  instructions: { color: 'white', marginTop: 16 },
  topControls: {
    position: 'absolute',
    top: 48,
    left: 0,
    right: 0,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
  },
  iconButton: {
    backgroundColor: 'rgba(0, 0, 0, 0.3)',
  },
  bottomControls: { position: 'absolute', bottom: 48, left: 16, right: 16 },
});
