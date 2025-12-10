import { Stack } from 'expo-router';

export default function CountingLayout() {
  return (
    <Stack>
      <Stack.Screen
        name="[id]/index"
        options={{
          title: 'Counting Session',
        }}
      />
      <Stack.Screen
        name="[id]/scan"
        options={{
          title: 'Scan Barcode',
          headerShown: false,
        }}
      />
      <Stack.Screen
        name="[id]/item/[itemId]"
        options={{
          title: 'Count Item',
        }}
      />
    </Stack>
  );
}
