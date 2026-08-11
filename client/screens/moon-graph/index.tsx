import React, { useState, useCallback, useRef, useEffect } from 'react';
import { View, Text, Dimensions, ScrollView } from 'react-native';
import { Screen } from '@/components/Screen';
import { useAuth } from '@/contexts/AuthContext';
import { useFocusEffect } from 'expo-router';

const API_BASE = process.env.EXPO_PUBLIC_BACKEND_BASE_URL || '';
const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');
const CENTER_X = SCREEN_WIDTH / 2;
const CENTER_Y = SCREEN_HEIGHT * 0.38;
const ORBIT_RADIUS = Math.min(SCREEN_WIDTH, SCREEN_HEIGHT) * 0.3;

const CATEGORY_COLORS: Record<string, string> = {
  close: '#f5576c',
  classmate: '#4facfe',
  roommate: '#43e97b',
  teacher: '#fee140',
};

const AVATAR_COLORS = ['#667eea', '#f5576c', '#4facfe', '#43e97b', '#fa709a', '#38f9d7', '#fee140', '#a8edea'];

export default function MoonGraphScreen() {
  const { token, user } = useAuth();
  const [relations, setRelations] = useState<any[]>([]);
  const [allStudents, setAllStudents] = useState<any[]>([]);

  const fetchRelations = useCallback(async () => {
    if (!token) return;
    try {
      const res = await fetch(`${API_BASE}/api/v1/relations/moon`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const json = await res.json();
      if (json.ok) setRelations(json.data);

      const stuRes = await fetch(`${API_BASE}/api/v1/students`);
      const stuJson = await stuRes.json();
      if (stuJson.ok) setAllStudents(stuJson.data);
    } catch {
      // ignore
    }
  }, [token]);

  useFocusEffect(useCallback(() => { fetchRelations(); }, [fetchRelations]));

  const getStudentById = (id: number) => allStudents.find((s) => s.id === id);

  // Calculate positions for moons
  const moons = relations.map((r, i) => {
    const angle = (2 * Math.PI * i) / Math.max(relations.length, 1);
    const x = CENTER_X + ORBIT_RADIUS * Math.cos(angle);
    const y = CENTER_Y + ORBIT_RADIUS * Math.sin(angle) * 0.6;
    const student = getStudentById(r.targetId);
    return { ...r, x, y, student };
  });

  return (
    <Screen>
      <View style={styles.container}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>月光图谱</Text>
          <Text style={styles.headerSubtitle}>你是我宇宙中的星辰</Text>
        </View>

        <View style={styles.graphContainer}>
          {/* Orbit rings */}
          <View style={[styles.orbitRing, { width: ORBIT_RADIUS * 2, height: ORBIT_RADIUS * 1.2 }]} />
          <View style={[styles.orbitRingSmall, { width: ORBIT_RADIUS * 1.2, height: ORBIT_RADIUS * 0.72 }]} />

          {/* Center - current user */}
          <View style={styles.centerContainer}>
            <View style={styles.centerGlow} />
            <View style={styles.centerMoon}>
              <Text style={styles.centerText}>{user?.username?.charAt(0) || '我'}</Text>
            </View>
            <Text style={styles.centerLabel}>{user?.username || '我'}</Text>
          </View>

          {/* Moons */}
          {moons.map((moon) => {
            const color = CATEGORY_COLORS[moon.category] || '#4facfe';
            const studentName = moon.customName || moon.student?.username || '?';
            return (
              <View
                key={moon.id}
                style={[
                  styles.moonItem,
                  { left: moon.x - 25, top: moon.y - 25 },
                ]}
              >
                <View style={[styles.moonDot, { backgroundColor: color, shadowColor: color }]}>
                  <Text style={styles.moonDotText}>{studentName.charAt(0)}</Text>
                </View>
                <Text style={styles.moonLabel} numberOfLines={1}>{studentName}</Text>
              </View>
            );
          })}

          {relations.length === 0 && (
            <View style={styles.emptyGraph}>
              <Text style={styles.emptyGraphText}>还没有关系数据</Text>
              <Text style={styles.emptyGraphSubtext}>去「编辑分类」添加关系吧</Text>
            </View>
          )}
        </View>

        {/* Legend */}
        <View style={styles.legend}>
          {Object.entries(CATEGORY_COLORS).map(([key, color]) => (
            <View key={key} style={styles.legendItem}>
              <View style={[styles.legendDot, { backgroundColor: color }]} />
              <Text style={styles.legendText}>
                {key === 'close' ? '挚友' : key === 'classmate' ? '同窗' : key === 'roommate' ? '萍水相逢' : '恩师'}
              </Text>
            </View>
          ))}
        </View>
      </View>
    </Screen>
  );
}

const styles = {
  container: { flex: 1, backgroundColor: '#0a0e1a' },
  header: { paddingTop: 16, paddingBottom: 8, paddingHorizontal: 20 },
  headerTitle: { fontSize: 24, fontWeight: '700' as const, color: '#f5e6c8', letterSpacing: 2 },
  headerSubtitle: { fontSize: 12, color: 'rgba(245,230,200,0.5)', marginTop: 4 },
  graphContainer: { flex: 1, position: 'relative' as const },
  orbitRing: {
    position: 'absolute' as const,
    left: CENTER_X - ORBIT_RADIUS,
    top: CENTER_Y - ORBIT_RADIUS * 0.6,
    borderRadius: ORBIT_RADIUS,
    borderWidth: 1,
    borderColor: 'rgba(245,230,200,0.08)',
  },
  orbitRingSmall: {
    position: 'absolute' as const,
    left: CENTER_X - ORBIT_RADIUS * 0.6,
    top: CENTER_Y - ORBIT_RADIUS * 0.36,
    borderRadius: ORBIT_RADIUS * 0.6,
    borderWidth: 1,
    borderColor: 'rgba(245,230,200,0.05)',
  },
  centerContainer: {
    position: 'absolute' as const,
    left: CENTER_X - 35,
    top: CENTER_Y - 35,
    alignItems: 'center' as const,
  },
  centerGlow: {
    position: 'absolute' as const,
    width: 100, height: 100, borderRadius: 50,
    backgroundColor: 'rgba(245,230,200,0.08)',
    left: -15, top: -15,
  },
  centerMoon: {
    width: 70, height: 70, borderRadius: 35,
    backgroundColor: 'rgba(245,230,200,0.2)',
    alignItems: 'center' as const, justifyContent: 'center' as const,
    borderWidth: 2, borderColor: 'rgba(245,230,200,0.4)',
  },
  centerText: { fontSize: 24, fontWeight: '700' as const, color: '#f5e6c8' },
  centerLabel: { marginTop: 6, fontSize: 12, color: '#f5e6c8', fontWeight: '600' as const },
  moonItem: {
    position: 'absolute' as const,
    width: 50, alignItems: 'center' as const,
  },
  moonDot: {
    width: 40, height: 40, borderRadius: 20,
    alignItems: 'center' as const, justifyContent: 'center' as const,
    shadowOffset: { width: 0, height: 0 }, shadowOpacity: 0.5, shadowRadius: 8,
  },
  moonDotText: { color: '#fff', fontSize: 16, fontWeight: '700' as const },
  moonLabel: { marginTop: 4, fontSize: 10, color: 'rgba(245,230,200,0.7)', textAlign: 'center' as const },
  legend: {
    flexDirection: 'row' as const, justifyContent: 'center' as const,
    paddingVertical: 16, gap: 16,
  },
  legendItem: { flexDirection: 'row' as const, alignItems: 'center' as const, gap: 4 },
  legendDot: { width: 8, height: 8, borderRadius: 4 },
  legendText: { fontSize: 11, color: 'rgba(245,230,200,0.6)' },
  emptyGraph: {
    position: 'absolute' as const, left: 0, right: 0,
    top: CENTER_Y + 60, alignItems: 'center' as const,
  },
  emptyGraphText: { color: 'rgba(245,230,200,0.5)', fontSize: 14 },
  emptyGraphSubtext: { color: 'rgba(245,230,200,0.3)', fontSize: 12, marginTop: 4 },
};
