import React, { useState, useCallback } from 'react';
import { View, Text, TouchableOpacity, TextInput, ScrollView, Alert } from 'react-native';
import { Screen } from '@/components/Screen';
import { useAuth } from '@/contexts/AuthContext';
import { useFocusEffect } from 'expo-router';

const API_BASE = process.env.EXPO_PUBLIC_BACKEND_BASE_URL || '';

const CONSTELLATIONS = ['白羊座', '金牛座', '双子座', '巨蟹座', '狮子座', '处女座', '天秤座', '天蝎座', '射手座', '摩羯座', '水瓶座', '双鱼座'];
const GENDERS = ['女', '男', '保密'];

interface ProfileData {
  public: Record<string, string>;
  myInfo: Record<string, string>;
  location: Record<string, string>;
  contactMe: Record<string, string>;
  likeAndDislike: Record<string, string>;
}

export default function ProfileScreen() {
  const { token, refreshUser } = useAuth();
  const [profile, setProfile] = useState<ProfileData | null>(null);
  const [username, setUsername] = useState('');

  const fetchProfile = useCallback(async () => {
    if (!token) return;
    try {
      const res = await fetch(`${API_BASE}/api/v1/profile`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const json = await res.json();
      if (json.ok) {
        setProfile(json.data);
        setUsername(json.data.username || '');
      }
    } catch {
      // ignore
    }
  }, [token]);

  useFocusEffect(useCallback(() => { fetchProfile(); }, [fetchProfile]));

  const handleSave = async (field: string, value: string) => {
    try {
      const res = await fetch(`${API_BASE}/api/v1/profile`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ field, value }),
      });
      const json = await res.json();
      if (json.ok) {
        refreshUser();
      }
    } catch {
      // ignore
    }
  };

  const updateProfileField = (group: keyof ProfileData, key: string, value: string) => {
    if (!profile) return;
    setProfile({
      ...profile,
      [group]: { ...profile[group], [key]: value },
    });
    handleSave(`${group}.${key}`, value);
  };

  if (!profile) {
    return (
      <Screen>
        <View style={styles.container}>
          <Text style={styles.loadingText}>加载中...</Text>
        </View>
      </Screen>
    );
  }

  const renderField = (label: string, group: keyof ProfileData, key: string, value?: string) => (
    <View style={styles.fieldRow}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        style={styles.fieldInput}
        value={value || ''}
        onChangeText={(v) => updateProfileField(group, key, v)}
        placeholderTextColor="#555"
        placeholder={`输入${label}`}
      />
    </View>
  );

  const renderSection = (title: string, fields: { label: string; group: keyof ProfileData; key: string }[]) => (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {fields.map((f) => {
        const data = profile[f.group] || {};
        return (
          <View key={f.key}>
            {f.label === '个性签名' ? renderField(f.label, f.group, f.key, data.Sign) : null}
            {f.label === '座右铭' ? renderField(f.label, f.group, f.key, data.Motto) : null}
            {f.label === '性别' ? renderField(f.label, f.group, f.key, data.Gender) : null}
            {f.label === '生日' ? renderField(f.label, f.group, f.key, data.Birthday) : null}
            {f.label === '星座' ? renderField(f.label, f.group, f.key, data.Constellation) : null}
            {f.label === '家乡' ? renderField(f.label, f.group, f.key, data.Hometown) : null}
            {f.label === '现居' ? renderField(f.label, f.group, f.key, data.NowLive) : null}
            {f.label === 'QQ' ? renderField(f.label, f.group, f.key, data.QQ) : null}
            {f.label === '微信' ? renderField(f.label, f.group, f.key, data.WeChat) : null}
            {f.label === '邮箱' ? renderField(f.label, f.group, f.key, data.Email) : null}
            {f.label === '喜欢的' ? renderField(f.label, f.group, f.key, data.MyLikeThing) : null}
            {f.label === '擅长' ? renderField(f.label, f.group, f.key, data.BeGoodAt) : null}
          </View>
        );
      })}
    </View>
  );

  return (
    <Screen>
      <ScrollView style={styles.container} contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>编辑信息</Text>
          <Text style={styles.headerSubtitle}>记录最真实的自己</Text>
        </View>

        {/* Username */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>基本信息</Text>
          <View style={styles.fieldRow}>
            <Text style={styles.fieldLabel}>用户名</Text>
            <TextInput
              style={styles.fieldInput}
              value={username}
              onChangeText={setUsername}
              placeholderTextColor="#555"
            />
          </View>
        </View>

        {renderSection('个人签名', [{ label: '个性签名', group: 'public', key: 'Sign' }])}

        {renderSection('个人信息', [
          { label: '座右铭', group: 'myInfo', key: 'Motto' },
          { label: '性别', group: 'myInfo', key: 'Gender' },
          { label: '生日', group: 'myInfo', key: 'Birthday' },
          { label: '星座', group: 'myInfo', key: 'Constellation' },
        ])}

        {renderSection('位置信息', [
          { label: '家乡', group: 'location', key: 'Hometown' },
          { label: '现居', group: 'location', key: 'NowLive' },
        ])}

        {renderSection('联系方式', [
          { label: 'QQ', group: 'contactMe', key: 'QQ' },
          { label: '微信', group: 'contactMe', key: 'WeChat' },
          { label: '邮箱', group: 'contactMe', key: 'Email' },
        ])}

        {renderSection('喜好', [
          { label: '喜欢的', group: 'likeAndDislike', key: 'MyLikeThing' },
          { label: '擅长', group: 'likeAndDislike', key: 'BeGoodAt' },
        ])}

        <View style={{ height: 40 }} />
      </ScrollView>
    </Screen>
  );
}

const styles = {
  container: { flex: 1, backgroundColor: '#0a0e1a' },
  scrollContent: { paddingBottom: 40 },
  loadingText: { color: 'rgba(245,230,200,0.5)', textAlign: 'center' as const, marginTop: 100 },
  header: { paddingTop: 16, paddingBottom: 8, paddingHorizontal: 20 },
  headerTitle: { fontSize: 24, fontWeight: '700' as const, color: '#f5e6c8', letterSpacing: 2 },
  headerSubtitle: { fontSize: 12, color: 'rgba(245,230,200,0.5)', marginTop: 4 },
  section: {
    marginHorizontal: 20, marginTop: 16, padding: 16,
    backgroundColor: 'rgba(255,255,255,0.03)', borderRadius: 16,
    borderWidth: 1, borderColor: 'rgba(245,230,200,0.06)',
  },
  sectionTitle: {
    fontSize: 14, fontWeight: '600' as const, color: 'rgba(245,230,200,0.8)',
    marginBottom: 12, letterSpacing: 1,
  },
  fieldRow: {
    flexDirection: 'row' as const, alignItems: 'center' as const,
    paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: 'rgba(245,230,200,0.05)',
  },
  fieldLabel: { width: 70, fontSize: 13, color: 'rgba(245,230,200,0.6)' },
  fieldInput: {
    flex: 1, fontSize: 14, color: '#f5e6c8',
    paddingVertical: 4, textAlign: 'right' as const,
  },
};
