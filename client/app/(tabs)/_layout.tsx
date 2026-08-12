import { Tabs } from 'expo-router';
import { Platform } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { FontAwesome6 } from '@expo/vector-icons';
import { useCSSVariable } from 'uniwind';

export default function TabLayout() {
  const insets = useSafeAreaInsets();
  const [background, muted, accent, border] = useCSSVariable([
    '--color-background',
    '--color-muted',
    '--color-accent',
    '--color-border',
  ]) as string[];

  const tabBarStyle: Record<string, any> = {
    backgroundColor: '#0d1117',
    borderTopWidth: 1,
    borderTopColor: 'rgba(245,230,200,0.08)',
    paddingBottom: insets.bottom > 0 ? insets.bottom - 8 : 8,
  };

  if (Platform.OS === 'web') {
    tabBarStyle.height = 'auto';
  }

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle,
        tabBarActiveTintColor: '#f5e6c8',
        tabBarInactiveTintColor: 'rgba(245,230,200,0.35)',
        tabBarLabelStyle: {
          fontSize: 10,
          fontWeight: '500',
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: '同学录',
          tabBarIcon: ({ color }) => (
            <FontAwesome6 name="book-open" size={18} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="moon-graph"
        options={{
          title: '月光图谱',
          tabBarIcon: ({ color }) => (
            <FontAwesome6 name="moon" size={18} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="category-edit"
        options={{
          title: '编辑分类',
          tabBarIcon: ({ color }) => (
            <FontAwesome6 name="tags" size={18} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="upload"
        options={{
          title: '上传',
          tabBarIcon: ({ color }) => (
            <FontAwesome6 name="camera" size={18} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="profile"
        options={{
          title: '我的',
          tabBarIcon: ({ color }) => (
            <FontAwesome6 name="user" size={18} color={color} />
          ),
        }}
      />
    </Tabs>
  );
}
