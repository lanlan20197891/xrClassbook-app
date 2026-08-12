import React, { useState, useCallback } from 'react';
import { View, Text, TouchableOpacity, TextInput, ScrollView, Alert } from 'react-native';
import { Screen } from '@/components/Screen';
import { useAuth } from '@/contexts/AuthContext';
import { useFocusEffect } from 'expo-router';

const API_BASE = process.env.EXPO_PUBLIC_BACKEND_BASE_URL || '';

const CONSTELLATIONS = ['白羊座', '金牛座', '双子座', '巨蟹座', '狮子座', '处女座', '天秤座', '天蝎座', '射手座', '摩羯座', '水瓶座', '双鱼座'];
const GENDERS = ['女', '男', '保密'];

export default function ProfileScreen() {
  const { token, user, loadStoredAuth } = useAuth();
  const [formData, setFormData] = useState({
    sign: '',
    motto: '',
    gender: '',
    birthday: '',
    constellation: '',
    hometown: '',
    nowLive: '',
    qq: '',
    wechat: '',
    email: '',
    phone: '',
    myLikeThing: '',
    beGoodAt: '',
  });
  const [loaded, setLoaded] = useState(false);

  const fetchProfile = useCallback(async () => {
    if (!token) return;
    try {
      const res = await fetch(`${API_BASE}/api/v1/profile`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const json = await res.json();
      if (json.ok) {
        const d = json.data;
        setFormData({
          sign: d.public?.Sign || '',
          motto: d.myInfo?.Motto || '',
          gender: d.myInfo?.Gender || '',
          birthday: d.myInfo?.Birthday || '',
          constellation: d.myInfo?.Constellation || '',
          hometown: d.location?.Hometown || '',
          nowLive: d.location?.NowLive || '',
          qq: d.socialAccount?.QQ || '',
          wechat: d.socialAccount?.WeChat || '',
          email: d.contactMe?.Email || '',
          phone: d.contactMe?.Phone || '',
          myLikeThing: d.likeAndDislike?.MyLikeThing || '',
          beGoodAt: d.likeAndDislike?.BeGoodAt || '',
        });
        setLoaded(true);
      }
    } catch {
      // ignore
    }
  }, [token]);

  useFocusEffect(useCallback(() => { fetchProfile(); }, [fetchProfile]));

  const handleSave = async () => {
    try {
      const res = await fetch(`${API_BASE}/api/v1/profile`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(formData),
      });
      const json = await res.json();
      if (json.ok) {
        Alert.alert('保存成功', '个人资料已更新');
        loadStoredAuth();
      } else {
        Alert.alert('保存失败', json.msg || '请重试');
      }
    } catch {
      Alert.alert('网络错误', '请检查网络连接');
    }
  };

  const updateField = (key: string, value: string) => {
    setFormData(prev => ({ ...prev, [key]: value }));
  };

  if (!loaded) {
    return (
      <Screen>
        <View style={styles.container}>
          <Text style={styles.loadingText}>加载中...</Text>
        </View>
      </Screen>
    );
  }

  const genderLabel = formData.gender === '0' ? '女' : formData.gender === '1' ? '男' : formData.gender === '2' ? '保密' : '';
  const constellationLabel = formData.constellation ? (CONSTELLATIONS[parseInt(formData.constellation)] || '') : '';

  const renderField = (label: string, value: string, onChange: (v: string) => void) => (
    <View style={styles.fieldRow}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        style={styles.fieldInput}
        value={value}
        onChangeText={onChange}
        placeholderTextColor="#555"
        placeholder={`输入${label}`}
      />
    </View>
  );

  return (
    <Screen>
      <ScrollView style={styles.container} contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>编辑信息</Text>
          <Text style={styles.headerSubtitle}>{user?.username || ''} - 记录最真实的自己</Text>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>个人签名</Text>
          {renderField('个性签名', formData.sign, (v) => updateField('sign', v))}
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>个人信息</Text>
          {renderField('座右铭', formData.motto, (v) => updateField('motto', v))}
          <View style={styles.fieldRow}>
            <Text style={styles.fieldLabel}>性别</Text>
            <View style={{ flex: 1, flexDirection: 'row', justifyContent: 'flex-end', gap: 8 }}>
              {GENDERS.map((g, i) => (
                <TouchableOpacity
                  key={g}
                  style={[styles.genderBtn, genderLabel === g && styles.genderBtnActive]}
                  onPress={() => updateField('gender', String(i))}
                >
                  <Text style={[styles.genderBtnText, genderLabel === g && styles.genderBtnTextActive]}>{g}</Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>
          {renderField('生日', formData.birthday, (v) => updateField('birthday', v))}
          <View style={styles.fieldRow}>
            <Text style={styles.fieldLabel}>星座</Text>
            <Text style={styles.fieldValue}>{constellationLabel || '未设置'}</Text>
          </View>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>位置信息</Text>
          {renderField('家乡', formData.hometown, (v) => updateField('hometown', v))}
          {renderField('现居', formData.nowLive, (v) => updateField('nowLive', v))}
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>联系方式</Text>
          {renderField('QQ', formData.qq, (v) => updateField('qq', v))}
          {renderField('微信', formData.wechat, (v) => updateField('wechat', v))}
          {renderField('邮箱', formData.email, (v) => updateField('email', v))}
          {renderField('手机', formData.phone, (v) => updateField('phone', v))}
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>喜好</Text>
          {renderField('喜欢的', formData.myLikeThing, (v) => updateField('myLikeThing', v))}
          {renderField('擅长', formData.beGoodAt, (v) => updateField('beGoodAt', v))}
        </View>

        <TouchableOpacity style={styles.saveBtn} onPress={handleSave}>
          <Text style={styles.saveBtnText}>保存修改</Text>
        </TouchableOpacity>

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
  fieldValue: { flex: 1, fontSize: 14, color: 'rgba(245,230,200,0.4)', textAlign: 'right' as const },
  genderBtn: {
    paddingHorizontal: 12, paddingVertical: 4, borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.05)',
  },
  genderBtnActive: { backgroundColor: 'rgba(245,200,140,0.2)' },
  genderBtnText: { fontSize: 12, color: 'rgba(245,230,200,0.5)' },
  genderBtnTextActive: { color: '#f5c88c' },
  saveBtn: {
    marginHorizontal: 20, marginTop: 24, paddingVertical: 14,
    backgroundColor: 'rgba(245,200,140,0.15)', borderRadius: 16,
    borderWidth: 1, borderColor: 'rgba(245,200,140,0.3)',
    alignItems: 'center' as const,
  },
  saveBtnText: { fontSize: 15, fontWeight: '600' as const, color: '#f5c88c', letterSpacing: 2 },
};
