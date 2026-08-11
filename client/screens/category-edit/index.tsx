import React, { useState, useCallback } from 'react';
import { View, Text, TouchableOpacity, FlatList, Alert } from 'react-native';
import { Screen } from '@/components/Screen';
import { useAuth } from '@/contexts/AuthContext';
import { useFocusEffect } from 'expo-router';

const API_BASE = process.env.EXPO_PUBLIC_BACKEND_BASE_URL || '';

import { FontAwesome6 } from '@expo/vector-icons';

const CATEGORIES = [
  { key: 'close', label: '挚友', iconName: 'heart' as const, color: '#f5576c' },
  { key: 'classmate', label: '同窗', iconName: 'users' as const, color: '#4facfe' },
  { key: 'roommate', label: '萍水相逢', iconName: 'leaf' as const, color: '#43e97b' },
  { key: 'teacher', label: '恩师', iconName: 'book' as const, color: '#fee140' },
];

export default function CategoryEditScreen() {
  const { token } = useAuth();
  const [grouped, setGrouped] = useState<Record<string, any[]>>({});
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);

  const fetchData = useCallback(async () => {
    if (!token) return;
    try {
      const res = await fetch(`${API_BASE}/api/v1/relations/by-category`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const json = await res.json();
      if (json.ok) setGrouped(json.data);
    } catch {
      // ignore
    }
  }, [token]);

  useFocusEffect(useCallback(() => { fetchData(); }, [fetchData]));

  const handleAssign = async (targetId: number, category: string) => {
    try {
      const res = await fetch(`${API_BASE}/api/v1/relations/update`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ targetId, category }),
      });
      const json = await res.json();
      if (json.ok) {
        fetchData();
        setSelectedCategory(null);
      }
    } catch {
      Alert.alert('错误', '操作失败');
    }
  };

  const uncategorized = grouped['uncategorized'] || [];

  const renderCategorySection = (cat: typeof CATEGORIES[0]) => {
    const items = grouped[cat.key] || [];
    return (
      <View style={styles.categorySection}>
        <View style={styles.categoryHeader}>
          <View style={[styles.categoryDot, { backgroundColor: cat.color }]} />
          <Text style={styles.categoryLabel}>
            <FontAwesome6 name={cat.iconName} size={14} color={cat.color} /> {cat.label}
          </Text>
          <Text style={styles.categoryCount}>{items.length}</Text>
        </View>
        <View style={styles.tagRow}>
          {items.map((item: any) => (
            <View key={item.id || item.targetId} style={styles.tag}>
              <Text style={styles.tagText}>{item.username || item.customName || '?'}</Text>
            </View>
          ))}
          {items.length === 0 && <Text style={styles.emptyTag}>暂无</Text>}
        </View>
      </View>
    );
  };

  const renderUncategorizedItem = ({ item }: { item: any }) => (
    <View style={styles.uncatItem}>
      <View style={styles.uncatInfo}>
        <Text style={styles.uncatName}>{item.username}</Text>
        <Text style={styles.uncatSub}>{item.userData?.Public?.Sign || ''}</Text>
      </View>
      <View style={styles.assignBtns}>
        {CATEGORIES.map((cat) => (
          <TouchableOpacity
            key={cat.key}
            style={[styles.assignBtn, { borderColor: cat.color }]}
            onPress={() => handleAssign(item.id, cat.key)}
          >
            <Text style={[styles.assignBtnText, { color: cat.color }]}>{cat.label}</Text>
          </TouchableOpacity>
        ))}
      </View>
    </View>
  );

  return (
    <Screen>
      <View style={styles.container}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>编辑分类</Text>
          <Text style={styles.headerSubtitle}>为每段关系找到归属</Text>
        </View>

        {/* Category sections */}
        <View style={styles.categoriesContainer}>
          {CATEGORIES.map(renderCategorySection)}
        </View>

        {/* Uncategorized */}
        <View style={styles.uncatSection}>
          <Text style={styles.uncatTitle}>未分类 ({uncategorized.length})</Text>
          <FlatList
            data={uncategorized}
            keyExtractor={(item) => String(item.id)}
            renderItem={renderUncategorizedItem}
            scrollEnabled={false}
          />
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
  categoriesContainer: { paddingHorizontal: 20, gap: 12 },
  categorySection: {
    backgroundColor: 'rgba(255,255,255,0.03)', borderRadius: 14, padding: 14,
    borderWidth: 1, borderColor: 'rgba(245,230,200,0.06)',
  },
  categoryHeader: { flexDirection: 'row' as const, alignItems: 'center' as const, gap: 8, marginBottom: 10 },
  categoryDot: { width: 8, height: 8, borderRadius: 4 },
  categoryLabel: { fontSize: 14, fontWeight: '600' as const, color: '#f5e6c8', flex: 1 },
  categoryCount: { fontSize: 12, color: 'rgba(245,230,200,0.4)' },
  tagRow: { flexDirection: 'row' as const, flexWrap: 'wrap' as const, gap: 8 },
  tag: {
    paddingHorizontal: 10, paddingVertical: 5, borderRadius: 8,
    backgroundColor: 'rgba(245,230,200,0.08)',
  },
  tagText: { fontSize: 12, color: 'rgba(245,230,200,0.8)' },
  emptyTag: { fontSize: 12, color: 'rgba(245,230,200,0.3)', fontStyle: 'italic' as const },
  uncatSection: { paddingHorizontal: 20, marginTop: 20, paddingBottom: 20 },
  uncatTitle: { fontSize: 15, fontWeight: '600' as const, color: 'rgba(245,230,200,0.7)', marginBottom: 12 },
  uncatItem: {
    backgroundColor: 'rgba(255,255,255,0.03)', borderRadius: 14, padding: 14,
    marginBottom: 10, borderWidth: 1, borderColor: 'rgba(245,230,200,0.06)',
  },
  uncatInfo: { marginBottom: 10 },
  uncatName: { fontSize: 15, fontWeight: '600' as const, color: '#f5e6c8' },
  uncatSub: { fontSize: 11, color: 'rgba(245,230,200,0.4)', marginTop: 2 },
  assignBtns: { flexDirection: 'row' as const, gap: 8 },
  assignBtn: {
    paddingHorizontal: 12, paddingVertical: 6, borderRadius: 8,
    borderWidth: 1,
  },
  assignBtnText: { fontSize: 12, fontWeight: '500' as const },
};
