import React, { useState, useCallback } from 'react';
import { View, Text, TextInput, TouchableOpacity, FlatList, Image, RefreshControl } from 'react-native';
import { Screen } from '@/components/Screen';
import { useAuth } from '@/contexts/AuthContext';
import { useFocusEffect, Link } from 'expo-router';
import { useSafeRouter } from '@/hooks/useSafeRouter';

const API_BASE = process.env.EXPO_PUBLIC_BACKEND_BASE_URL || '';

const CATEGORIES = [
  { key: 'all', label: '全部' },
  { key: 'close', label: '挚友' },
  { key: 'classmate', label: '同窗' },
  { key: 'roommate', label: '萍水相逢' },
  { key: 'teacher', label: '恩师' },
  { key: 'uncategorized', label: '未分类' },
];

const AVATAR_COLORS = ['#667eea', '#f5576c', '#4facfe', '#43e97b', '#fa709a', '#38f9d7', '#fee140', '#a8edea'];

function getAvatarColor(id: number) {
  return AVATAR_COLORS[id % AVATAR_COLORS.length];
}

function getGroupLabel(group: string) {
  switch (group) {
    case 'Admin': return '管理员';
    case 'Monitor': return '班委';
    default: return '同学';
  }
}

export default function HomeScreen() {
  const { token } = useAuth();
  const router = useSafeRouter();
  const [students, setStudents] = useState<any[]>([]);
  const [timeline, setTimeline] = useState<any[]>([]);
  const [keyword, setKeyword] = useState('');
  const [category, setCategory] = useState('all');
  const [refreshing, setRefreshing] = useState(false);
  const [showTimeline, setShowTimeline] = useState(false);

  const fetchStudents = useCallback(async () => {
    try {
      const params = new URLSearchParams();
      if (keyword) params.set('keyword', keyword);
      if (category && category !== 'all') params.set('category', category);
      const res = await fetch(`${API_BASE}/api/v1/students?${params.toString()}`);
      const json = await res.json();
      if (json.ok) setStudents(json.data);
    } catch {
      // ignore
    }
  }, [keyword, category]);

  const fetchTimeline = useCallback(async () => {
    try {
      const params = keyword ? `?keyword=${encodeURIComponent(keyword)}` : '';
      const res = await fetch(`${API_BASE}/api/v1/students/timeline/images${params}`);
      const json = await res.json();
      if (json.ok) setTimeline(json.data);
    } catch {
      // ignore
    }
  }, [keyword]);

  useFocusEffect(
    useCallback(() => {
      fetchStudents();
      fetchTimeline();
    }, [fetchStudents, fetchTimeline])
  );

  const onRefresh = async () => {
    setRefreshing(true);
    await Promise.all([fetchStudents(), fetchTimeline()]);
    setRefreshing(false);
  };

  const renderStudentItem = ({ item }: { item: any }) => {
    const ud = item.userData || {};
    const sign = ud.Public?.Sign || '';
    const motto = ud.MyInfo?.Motto || '';
    const birthday = ud.MyInfo?.Birthday || '';

    return (
      <TouchableOpacity
        style={styles.studentCard}
        onPress={() => router.push('/student-detail', { id: item.id })}
      >
        <View style={styles.avatarContainer}>
          {item.headUrl ? (
            <Image source={{ uri: item.headUrl }} style={styles.avatar} />
          ) : (
            <View style={[styles.avatarPlaceholder, { backgroundColor: getAvatarColor(item.id) }]}>
              <Text style={styles.avatarText}>{item.username?.charAt(0) || '?'}</Text>
            </View>
          )}
        </View>
        <View style={styles.studentInfo}>
          <View style={styles.nameRow}>
            <Text style={styles.studentName}>{item.username}</Text>
            <View style={styles.groupBadge}>
              <Text style={styles.groupBadgeText}>{getGroupLabel(item.userGroup)}</Text>
            </View>
          </View>
          {sign ? <Text style={styles.signText} numberOfLines={1}>&ldquo;{sign}&rdquo;</Text> : null}
          {motto ? <Text style={styles.mottoText} numberOfLines={1}>座右铭: {motto}</Text> : null}
          {birthday ? <Text style={styles.birthdayText}>生日: {birthday}</Text> : null}
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <Screen>
      <View style={styles.container}>
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>同学录</Text>
          <Text style={styles.headerSubtitle}>月光如纸 记录年华</Text>
        </View>

        {/* Search */}
        <View style={styles.searchContainer}>
          <TextInput
            style={styles.searchInput}
            value={keyword}
            onChangeText={setKeyword}
            placeholder="搜索同学..."
            placeholderTextColor="#666"
            onSubmitEditing={() => { fetchStudents(); fetchTimeline(); }}
          />
        </View>

        {/* Categories */}
        <View style={styles.categoryRow}>
          {CATEGORIES.map((cat) => (
            <TouchableOpacity
              key={cat.key}
              style={[styles.categoryChip, category === cat.key && styles.categoryChipActive]}
              onPress={() => { setCategory(cat.key); }}
            >
              <Text style={[styles.categoryText, category === cat.key && styles.categoryTextActive]}>
                {cat.label}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Toggle */}
        <View style={styles.toggleRow}>
          <TouchableOpacity
            style={[styles.toggleBtn, !showTimeline && styles.toggleBtnActive]}
            onPress={() => setShowTimeline(false)}
          >
            <Text style={[styles.toggleText, !showTimeline && styles.toggleTextActive]}>同学列表</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.toggleBtn, showTimeline && styles.toggleBtnActive]}
            onPress={() => setShowTimeline(true)}
          >
            <Text style={[styles.toggleText, showTimeline && styles.toggleTextActive]}>时间轴</Text>
          </TouchableOpacity>
        </View>

        {/* Content */}
        {showTimeline ? (
          <FlatList
            data={timeline}
            keyExtractor={(item) => String(item.id)}
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#f5e6c8" />}
            contentContainerStyle={styles.listContent}
            renderItem={({ item }) => (
              <View style={styles.timelineItem}>
                <View style={styles.timelineDot} />
                <Text style={styles.timelineDate}>{item.dateLabel}</Text>
                <Text style={styles.timelineTitle}>{item.title}</Text>
                {item.description ? <Text style={styles.timelineDesc}>{item.description}</Text> : null}
                {item.imageUrl ? (
                  <Image source={{ uri: item.imageUrl }} style={styles.timelineImage} resizeMode="cover" />
                ) : null}
              </View>
            )}
          />
        ) : (
          <FlatList
            data={students}
            keyExtractor={(item) => String(item.id)}
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#f5e6c8" />}
            contentContainerStyle={styles.listContent}
            renderItem={renderStudentItem}
            ListEmptyComponent={
              <View style={styles.emptyContainer}>
                <Text style={styles.emptyText}>暂无数据</Text>
              </View>
            }
          />
        )}
      </View>
    </Screen>
  );
}

const styles = {
  container: { flex: 1, backgroundColor: '#0a0e1a' },
  header: { paddingTop: 16, paddingBottom: 8, paddingHorizontal: 20 },
  headerTitle: { fontSize: 24, fontWeight: '700' as const, color: '#f5e6c8', letterSpacing: 2 },
  headerSubtitle: { fontSize: 12, color: 'rgba(245,230,200,0.5)', marginTop: 4, letterSpacing: 1 },
  searchContainer: { paddingHorizontal: 20, paddingVertical: 12 },
  searchInput: {
    height: 40, backgroundColor: 'rgba(255,255,255,0.06)', borderRadius: 10,
    paddingHorizontal: 14, color: '#f5e6c8', fontSize: 14,
    borderWidth: 1, borderColor: 'rgba(245,230,200,0.1)',
  },
  categoryRow: {
    flexDirection: 'row' as const, flexWrap: 'wrap' as const,
    paddingHorizontal: 20, gap: 8, marginBottom: 12,
  },
  categoryChip: {
    paddingHorizontal: 14, paddingVertical: 6, borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.06)', borderWidth: 1, borderColor: 'rgba(245,230,200,0.1)',
  },
  categoryChipActive: { backgroundColor: 'rgba(245,230,200,0.15)', borderColor: 'rgba(245,230,200,0.3)' },
  categoryText: { fontSize: 12, color: 'rgba(245,230,200,0.6)' },
  categoryTextActive: { color: '#f5e6c8', fontWeight: '600' as const },
  toggleRow: {
    flexDirection: 'row' as const, paddingHorizontal: 20, marginBottom: 8, gap: 12,
  },
  toggleBtn: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 8 },
  toggleBtnActive: { backgroundColor: 'rgba(245,230,200,0.1)' },
  toggleText: { fontSize: 13, color: 'rgba(245,230,200,0.5)' },
  toggleTextActive: { color: '#f5e6c8', fontWeight: '600' as const },
  listContent: { paddingHorizontal: 20, paddingBottom: 20, gap: 12 },
  studentCard: {
    flexDirection: 'row' as const, backgroundColor: 'rgba(255,255,255,0.04)',
    borderRadius: 16, padding: 16, borderWidth: 1, borderColor: 'rgba(245,230,200,0.08)',
  },
  avatarContainer: { marginRight: 14 },
  avatar: { width: 50, height: 50, borderRadius: 25 },
  avatarPlaceholder: {
    width: 50, height: 50, borderRadius: 25,
    alignItems: 'center' as const, justifyContent: 'center' as const,
  },
  avatarText: { color: '#fff', fontSize: 20, fontWeight: '700' as const },
  studentInfo: { flex: 1, gap: 4 },
  nameRow: { flexDirection: 'row' as const, alignItems: 'center' as const, gap: 8 },
  studentName: { fontSize: 16, fontWeight: '600' as const, color: '#f5e6c8' },
  groupBadge: {
    paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4,
    backgroundColor: 'rgba(245,230,200,0.1)',
  },
  groupBadgeText: { fontSize: 10, color: 'rgba(245,230,200,0.7)' },
  signText: { fontSize: 12, color: 'rgba(245,230,200,0.5)', fontStyle: 'italic' as const },
  mottoText: { fontSize: 11, color: 'rgba(245,230,200,0.4)' },
  birthdayText: { fontSize: 11, color: 'rgba(245,230,200,0.4)' },
  timelineItem: {
    backgroundColor: 'rgba(255,255,255,0.04)', borderRadius: 16, padding: 16,
    borderLeftWidth: 3, borderLeftColor: 'rgba(245,230,200,0.3)',
  },
  timelineDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: '#f5e6c8', marginBottom: 8 },
  timelineDate: { fontSize: 12, color: 'rgba(245,230,200,0.6)', marginBottom: 4 },
  timelineTitle: { fontSize: 15, fontWeight: '600' as const, color: '#f5e6c8', marginBottom: 4 },
  timelineDesc: { fontSize: 12, color: 'rgba(245,230,200,0.5)', marginBottom: 8 },
  timelineImage: { width: '100%' as const, height: 160, borderRadius: 12 },
  emptyContainer: { alignItems: 'center' as const, paddingTop: 60 },
  emptyText: { color: 'rgba(245,230,200,0.4)', fontSize: 14 },
};
