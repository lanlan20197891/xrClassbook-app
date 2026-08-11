import React, { useState, useCallback } from 'react';
import { View, Text, ScrollView, Image } from 'react-native';
import { Screen } from '@/components/Screen';
import { useSafeSearchParams } from '@/hooks/useSafeRouter';
import { useFocusEffect } from 'expo-router';

const API_BASE = process.env.EXPO_PUBLIC_BACKEND_BASE_URL || '';

const AVATAR_COLORS = ['#667eea', '#f5576c', '#4facfe', '#43e97b', '#fa709a', '#38f9d7', '#fee140', '#a8edea'];
const CONSTELLATIONS: Record<string, string> = {
  '0': '白羊座', '1': '金牛座', '2': '双子座', '3': '巨蟹座',
  '4': '狮子座', '5': '处女座', '6': '天秤座', '7': '天蝎座',
  '8': '射手座', '9': '摩羯座', '10': '水瓶座', '11': '双鱼座',
};

export default function StudentDetailScreen() {
  const { id } = useSafeSearchParams<{ id: number }>();
  const [student, setStudent] = useState<any>(null);

  useFocusEffect(
    useCallback(() => {
      if (!id) return;
      (async () => {
        try {
          const res = await fetch(`${API_BASE}/api/v1/students/${id}`);
          const json = await res.json();
          if (json.ok) setStudent(json.data);
        } catch {
          // ignore
        }
      })();
    }, [id])
  );

  if (!student) {
    return (
      <Screen>
        <View style={{ flex: 1, backgroundColor: '#0a0e1a', alignItems: 'center', justifyContent: 'center' }}>
          <Text style={{ color: 'rgba(245,230,200,0.5)' }}>加载中...</Text>
        </View>
      </Screen>
    );
  }

  const ud = student.userData || {};
  const pub = ud.Public || {};
  const info = ud.MyInfo || {};
  const loc = ud.Location || {};
  const contact = ud.ContactMe || {};
  const likes = ud.LikeAndDislike || {};

  const constellation = info.Constellation ? CONSTELLATIONS[info.Constellation] || '' : '';
  const gender = info.Gender === '0' ? '女' : info.Gender === '1' ? '男' : '保密';

  return (
    <Screen>
      <ScrollView style={styles.container} contentContainerStyle={styles.scrollContent}>
        {/* Profile header */}
        <View style={styles.profileHeader}>
          <View style={styles.avatarContainer}>
            {student.headUrl ? (
              <Image source={{ uri: student.headUrl }} style={styles.avatar} />
            ) : (
              <View style={[styles.avatarPlaceholder, { backgroundColor: AVATAR_COLORS[student.id % AVATAR_COLORS.length] }]}>
                <Text style={styles.avatarText}>{student.username?.charAt(0) || '?'}</Text>
              </View>
            )}
          </View>
          <Text style={styles.name}>{student.username}</Text>
          {pub.Sign ? <Text style={styles.sign}>&ldquo;{pub.Sign}&rdquo;</Text> : null}
        </View>

        {/* Info sections */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>个人信息</Text>
          {info.Motto ? <InfoRow label="座右铭" value={info.Motto} /> : null}
          <InfoRow label="性别" value={gender} />
          {info.Birthday ? <InfoRow label="生日" value={info.Birthday} /> : null}
          {constellation ? <InfoRow label="星座" value={constellation} /> : null}
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>位置信息</Text>
          {loc.Hometown ? <InfoRow label="家乡" value={loc.Hometown} /> : null}
          {loc.NowLive ? <InfoRow label="现居" value={loc.NowLive} /> : null}
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>联系方式</Text>
          {contact.QQ ? <InfoRow label="QQ" value={contact.QQ} /> : null}
          {contact.WeChat ? <InfoRow label="微信" value={contact.WeChat} /> : null}
          {contact.Email ? <InfoRow label="邮箱" value={contact.Email} /> : null}
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>喜好</Text>
          {likes.MyLikeThing ? <InfoRow label="喜欢的" value={likes.MyLikeThing} /> : null}
          {likes.BeGoodAt ? <InfoRow label="擅长" value={likes.BeGoodAt} /> : null}
        </View>

        <View style={{ height: 40 }} />
      </ScrollView>
    </Screen>
  );
}

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
  );
}

const styles = {
  container: { flex: 1, backgroundColor: '#0a0e1a' },
  scrollContent: { paddingBottom: 40 },
  profileHeader: { alignItems: 'center' as const, paddingTop: 32, paddingBottom: 24 },
  avatarContainer: { marginBottom: 16 },
  avatar: { width: 80, height: 80, borderRadius: 40 },
  avatarPlaceholder: {
    width: 80, height: 80, borderRadius: 40,
    alignItems: 'center' as const, justifyContent: 'center' as const,
  },
  avatarText: { color: '#fff', fontSize: 32, fontWeight: '700' as const },
  name: { fontSize: 22, fontWeight: '700' as const, color: '#f5e6c8', letterSpacing: 2 },
  sign: { fontSize: 13, color: 'rgba(245,230,200,0.5)', marginTop: 8, fontStyle: 'italic' as const },
  section: {
    marginHorizontal: 20, marginTop: 16, padding: 16,
    backgroundColor: 'rgba(255,255,255,0.03)', borderRadius: 16,
    borderWidth: 1, borderColor: 'rgba(245,230,200,0.06)',
  },
  sectionTitle: {
    fontSize: 14, fontWeight: '600' as const, color: 'rgba(245,230,200,0.8)',
    marginBottom: 12, letterSpacing: 1,
  },
  infoRow: {
    flexDirection: 'row' as const, justifyContent: 'space-between' as const,
    paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: 'rgba(245,230,200,0.04)',
  },
  infoLabel: { fontSize: 13, color: 'rgba(245,230,200,0.5)' },
  infoValue: { fontSize: 13, color: '#f5e6c8' },
};
