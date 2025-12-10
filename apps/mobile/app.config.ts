import { ExpoConfig, ConfigContext } from 'expo/config';

export default ({ config }: ConfigContext): ExpoConfig => ({
  ...config,
  name: 'AutoERP',
  slug: 'autoerp-mobile',
  version: '1.0.0',
  orientation: 'portrait',
  icon: './assets/images/icon.png',
  scheme: 'autoerp',
  userInterfaceStyle: 'automatic',
  newArchEnabled: true,
  splash: {
    image: './assets/images/splash-icon.png',
    resizeMode: 'contain',
    backgroundColor: '#ffffff',
  },
  assetBundlePatterns: ['**/*'],
  ios: {
    supportsTablet: true,
    bundleIdentifier: 'com.autoerp.mobile',
    infoPlist: {
      NSCameraUsageDescription:
        'Camera is used to scan product barcodes during inventory counting',
    },
  },
  android: {
    adaptiveIcon: {
      foregroundImage: './assets/images/adaptive-icon.png',
      backgroundColor: '#ffffff',
    },
    package: 'com.autoerp.mobile',
    permissions: ['CAMERA'],
    edgeToEdgeEnabled: true,
  },
  web: {
    bundler: 'metro',
    output: 'static',
    favicon: './assets/images/favicon.png',
  },
  plugins: [
    'expo-router',
    'expo-secure-store',
    [
      'expo-camera',
      {
        cameraPermission:
          'Allow AutoERP to access your camera for barcode scanning.',
      },
    ],
    [
      'expo-notifications',
      {
        icon: './assets/images/icon.png',
        color: '#ffffff',
      },
    ],
  ],
  experiments: {
    typedRoutes: true,
  },
});
