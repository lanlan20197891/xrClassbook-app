import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { LogBox } from 'react-native';
import Toast from 'react-native-toast-message';
import { Provider } from '@/components/Provider';
import { useAuth } from '@/contexts/AuthContext';
import { useEffect } from 'react';
import { useSegments, useRootNavigationState } from 'expo-router';
import { useSafeRouter } from '@/hooks/useSafeRouter';

import '../global.css';

LogBox.ignoreLogs([
  "TurboModuleRegistry.getEnforcing(...): 'RNMapsAirModule' could not be found",
]);

function AuthRedirect() {
  const rootState = useRootNavigationState();
  const segments = useSegments();
  const { isAuthenticated, isLoading } = useAuth();
  const router = useSafeRouter();

  useEffect(() => {
    if (!rootState?.key || isLoading) return;
    const inLoginRoute = segments.includes('login');
    if (!isAuthenticated && !inLoginRoute) {
      router.replace('/login');
    }
    if (isAuthenticated && inLoginRoute) {
      router.replace('/');
    }
  }, [rootState?.key, isAuthenticated, isLoading, segments]);

  return null;
}

export default function RootLayout() {
  return (
    <Provider>
      <AuthRedirect />
      <StatusBar style="light" />
      <Stack
        screenOptions={{
          animation: 'slide_from_right',
          gestureEnabled: true,
          gestureDirection: 'horizontal',
          headerShown: false,
          contentStyle: { backgroundColor: '#0a0e1a' },
        }}
      >
        <Stack.Screen name="(tabs)" />
        <Stack.Screen name="login" />
        <Stack.Screen name="student-detail" />
        <Stack.Screen name="category-edit" />
        <Stack.Screen name="album-detail" />
      </Stack>
      <Toast />
    </Provider>
  );
}
