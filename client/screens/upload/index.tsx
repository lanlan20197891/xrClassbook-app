import React, { useState, useCallback } from 'react';
import { View, Text, TouchableOpacity, FlatList, Image, Alert, Modal, TextInput, ScrollView } from 'react-native';
import { Screen } from '@/components/Screen';
import { useAuth } from '@/contexts/AuthContext';
import { useFocusEffect } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import { createFormDataFile } from '@/utils';
import { FontAwesome6 } from '@expo/vector-icons';

const API_BASE = process.env.EXPO_PUBLIC_BACKEND_BASE_URL || '';

export default function UploadScreen() {
  const { token } = useAuth();
  const [albums, setAlbums] = useState<any[]>([]);
  const [modalVisible, setModalVisible] = useState(false);
  const [newAlbumName, setNewAlbumName] = useState('');
  const [uploading, setUploading] = useState(false);
  const [selectedAlbum, setSelectedAlbum] = useState<number | null>(null);

  const fetchAlbums = useCallback(async () => {
    if (!token) return;
    try {
      const res = await fetch(`${API_BASE}/api/v1/photos/albums`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const json = await res.json();
      if (json.ok) setAlbums(json.data);
    } catch {
      // ignore
    }
  }, [token]);

  useFocusEffect(useCallback(() => { fetchAlbums(); }, [fetchAlbums]));

  const handleCreateAlbum = async () => {
    if (!newAlbumName.trim()) {
      Alert.alert('提示', '请输入相册名称');
      return;
    }
    try {
      const res = await fetch(`${API_BASE}/api/v1/photos/albums`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ name: newAlbumName.trim() }),
      });
      const json = await res.json();
      if (json.ok) {
        setNewAlbumName('');
        setModalVisible(false);
        fetchAlbums();
      }
    } catch {
      Alert.alert('错误', '创建失败');
    }
  };

  const handlePickImage = async (albumId: number) => {
    try {
      const permissionResult = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (!permissionResult.granted) {
        Alert.alert('提示', '需要相册访问权限');
        return;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        allowsEditing: false,
        quality: 0.8,
      });

      if (result.canceled || !result.assets?.[0]) return;

      const asset = result.assets[0];
      setUploading(true);

      const fileData = await createFormDataFile(
        asset.uri,
        asset.fileName || 'photo.jpg',
        asset.mimeType || 'image/jpeg'
      );
      const formData = new FormData();
      formData.append('file', fileData as any);
      formData.append('albumId', String(albumId));

      const res = await fetch(`${API_BASE}/api/v1/photos/upload`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
        body: formData,
      });
      const json = await res.json();
      setUploading(false);

      if (json.ok) {
        Alert.alert('成功', '图片已上传');
      } else {
        Alert.alert('失败', json.msg || '上传失败');
      }
    } catch {
      setUploading(false);
      Alert.alert('错误', '上传失败');
    }
  };

  const renderAlbumItem = ({ item }: { item: any }) => (
    <View style={styles.albumCard}>
      <View style={styles.albumIcon}>
        <FontAwesome6 name="camera" size={20} color="#C9A96E" />
      </View>
      <View style={styles.albumInfo}>
        <Text style={styles.albumName}>{item.name}</Text>
        <Text style={styles.albumDate}>{item.createdAt?.split('T')[0] || ''}</Text>
      </View>
      <TouchableOpacity
        style={styles.uploadBtn}
        onPress={() => handlePickImage(item.id)}
        disabled={uploading}
      >
        <Text style={styles.uploadBtnText}>{uploading ? '上传中...' : '上传'}</Text>
      </TouchableOpacity>
    </View>
  );

  return (
    <Screen>
      <View style={styles.container}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>上传图片</Text>
          <Text style={styles.headerSubtitle}>定格每一帧珍贵时光</Text>
        </View>

        <TouchableOpacity style={styles.createBtn} onPress={() => setModalVisible(true)}>
          <Text style={styles.createBtnText}>+ 新建相册</Text>
        </TouchableOpacity>

        <FlatList
          data={albums}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.listContent}
          renderItem={renderAlbumItem}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Text style={styles.emptyText}>还没有相册</Text>
              <Text style={styles.emptySubtext}>点击上方按钮创建第一个相册</Text>
            </View>
          }
        />

        {/* Create Album Modal */}
        <Modal visible={modalVisible} transparent animationType="slide">
          <View style={styles.modalOverlay}>
            <View style={styles.modalContent}>
              <Text style={styles.modalTitle}>新建相册</Text>
              <TextInput
                style={styles.modalInput}
                value={newAlbumName}
                onChangeText={setNewAlbumName}
                placeholder="相册名称"
                placeholderTextColor="#666"
              />
              <View style={styles.modalActions}>
                <TouchableOpacity style={styles.modalCancelBtn} onPress={() => setModalVisible(false)}>
                  <Text style={styles.modalCancelText}>取消</Text>
                </TouchableOpacity>
                <TouchableOpacity style={styles.modalSaveBtn} onPress={handleCreateAlbum}>
                  <Text style={styles.modalSaveText}>创建</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>
      </View>
    </Screen>
  );
}

const styles = {
  container: { flex: 1, backgroundColor: '#0a0e1a' },
  header: { paddingTop: 16, paddingBottom: 8, paddingHorizontal: 20 },
  headerTitle: { fontSize: 24, fontWeight: '700' as const, color: '#f5e6c8', letterSpacing: 2 },
  headerSubtitle: { fontSize: 12, color: 'rgba(245,230,200,0.5)', marginTop: 4 },
  createBtn: {
    marginHorizontal: 20, marginVertical: 12, paddingVertical: 12,
    borderRadius: 12, backgroundColor: 'rgba(245,230,200,0.1)',
    alignItems: 'center' as const, borderWidth: 1, borderColor: 'rgba(245,230,200,0.2)',
  },
  createBtnText: { color: '#f5e6c8', fontSize: 14, fontWeight: '600' as const },
  listContent: { paddingHorizontal: 20, gap: 12, paddingBottom: 20 },
  albumCard: {
    flexDirection: 'row' as const, alignItems: 'center' as const,
    backgroundColor: 'rgba(255,255,255,0.04)', borderRadius: 16, padding: 16,
    borderWidth: 1, borderColor: 'rgba(245,230,200,0.08)',
  },
  albumIcon: {
    width: 44, height: 44, borderRadius: 12,
    backgroundColor: 'rgba(245,230,200,0.08)',
    alignItems: 'center' as const, justifyContent: 'center' as const,
    marginRight: 12,
  },
  albumIconText: { fontSize: 20 },
  albumInfo: { flex: 1 },
  albumName: { fontSize: 15, fontWeight: '600' as const, color: '#f5e6c8' },
  albumDate: { fontSize: 11, color: 'rgba(245,230,200,0.4)', marginTop: 2 },
  uploadBtn: {
    paddingHorizontal: 16, paddingVertical: 8, borderRadius: 8,
    backgroundColor: 'rgba(245,230,200,0.12)',
  },
  uploadBtnText: { color: '#f5e6c8', fontSize: 13, fontWeight: '500' as const },
  emptyContainer: { alignItems: 'center' as const, paddingTop: 60 },
  emptyText: { color: 'rgba(245,230,200,0.5)', fontSize: 14 },
  emptySubtext: { color: 'rgba(245,230,200,0.3)', fontSize: 12, marginTop: 4 },
  modalOverlay: {
    flex: 1, backgroundColor: 'rgba(0,0,0,0.7)',
    justifyContent: 'center' as const, alignItems: 'center' as const,
  },
  modalContent: {
    width: '80%' as const, backgroundColor: '#1a1e2e', borderRadius: 20, padding: 24,
    borderWidth: 1, borderColor: 'rgba(245,230,200,0.1)',
  },
  modalTitle: { fontSize: 18, fontWeight: '600' as const, color: '#f5e6c8', marginBottom: 16 },
  modalInput: {
    height: 44, backgroundColor: 'rgba(255,255,255,0.06)', borderRadius: 10,
    paddingHorizontal: 14, color: '#f5e6c8', fontSize: 14,
    borderWidth: 1, borderColor: 'rgba(245,230,200,0.1)',
  },
  modalActions: { flexDirection: 'row' as const, gap: 12, marginTop: 20 },
  modalCancelBtn: {
    flex: 1, paddingVertical: 10, borderRadius: 10,
    backgroundColor: 'rgba(255,255,255,0.06)', alignItems: 'center' as const,
  },
  modalCancelText: { color: 'rgba(245,230,200,0.7)', fontSize: 14 },
  modalSaveBtn: {
    flex: 1, paddingVertical: 10, borderRadius: 10,
    backgroundColor: 'rgba(245,230,200,0.15)', alignItems: 'center' as const,
  },
  modalSaveText: { color: '#f5e6c8', fontSize: 14, fontWeight: '600' as const },
};
