import React, { useState, useCallback } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, Modal, Dimensions } from 'react-native';
import { Screen } from '@/components/Screen';
import { useAuth } from '@/contexts/AuthContext';
import { useSafeSearchParams } from '@/hooks/useSafeRouter';
import { useFocusEffect } from 'expo-router';

const API_BASE = process.env.EXPO_PUBLIC_BACKEND_BASE_URL || '';
const { width: SCREEN_WIDTH } = Dimensions.get('window');

export default function AlbumDetailScreen() {
  const { token } = useAuth();
  const { id } = useSafeSearchParams<{ id: number }>();
  const [album, setAlbum] = useState<any>(null);
  const [photos, setPhotos] = useState<any[]>([]);
  const [lightboxVisible, setLightboxVisible] = useState(false);
  const [selectedPhoto, setSelectedPhoto] = useState<any>(null);

  useFocusEffect(
    useCallback(() => {
      if (!id || !token) return;
      (async () => {
        try {
          const res = await fetch(`${API_BASE}/api/v1/photos/albums/${id}`, {
            headers: { Authorization: `Bearer ${token}` },
          });
          const json = await res.json();
          if (json.ok) {
            setAlbum(json.data.album);
            setPhotos(json.data.photos);
          }
        } catch {
          // ignore
        }
      })();
    }, [id, token])
  );

  const openLightbox = (photo: any) => {
    setSelectedPhoto(photo);
    setLightboxVisible(true);
  };

  const renderPhotoItem = ({ item }: { item: any }) => (
    <TouchableOpacity
      style={styles.photoItem}
      onPress={() => openLightbox(item)}
      activeOpacity={0.8}
    >
      <Image source={{ uri: item.url }} style={styles.photoImage} resizeMode="cover" />
      {item.title ? (
        <View style={styles.photoOverlay}>
          <Text style={styles.photoTitle} numberOfLines={1}>{item.title}</Text>
        </View>
      ) : null}
    </TouchableOpacity>
  );

  return (
    <Screen>
      <View style={styles.container}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>{album?.name || '相册'}</Text>
          {album?.description ? <Text style={styles.headerDesc}>{album.description}</Text> : null}
          <Text style={styles.headerCount}>{photos.length} 张照片</Text>
        </View>

        <FlatList
          data={photos}
          keyExtractor={(item) => String(item.id)}
          numColumns={3}
          contentContainerStyle={styles.grid}
          renderItem={renderPhotoItem}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Text style={styles.emptyText}>相册是空的</Text>
            </View>
          }
        />

        {/* Lightbox */}
        <Modal visible={lightboxVisible} transparent animationType="fade">
          <TouchableOpacity
            style={styles.lightbox}
            activeOpacity={1}
            onPress={() => setLightboxVisible(false)}
          >
            {selectedPhoto?.url ? (
              <Image source={{ uri: selectedPhoto.url }} style={styles.lightboxImage} resizeMode="contain" />
            ) : null}
            {selectedPhoto?.title ? (
              <Text style={styles.lightboxTitle}>{selectedPhoto.title}</Text>
            ) : null}
          </TouchableOpacity>
        </Modal>
      </View>
    </Screen>
  );
}

const styles = {
  container: { flex: 1, backgroundColor: '#0a0e1a' },
  header: { paddingTop: 16, paddingBottom: 16, paddingHorizontal: 20 },
  headerTitle: { fontSize: 22, fontWeight: '700' as const, color: '#f5e6c8', letterSpacing: 2 },
  headerDesc: { fontSize: 12, color: 'rgba(245,230,200,0.5)', marginTop: 4 },
  headerCount: { fontSize: 11, color: 'rgba(245,230,200,0.4)', marginTop: 4 },
  grid: { paddingHorizontal: 16, gap: 4 },
  photoItem: {
    width: (SCREEN_WIDTH - 44) / 3,
    height: (SCREEN_WIDTH - 44) / 3,
    margin: 2,
    borderRadius: 8,
    overflow: 'hidden' as const,
  },
  photoImage: { width: '100%' as const, height: '100%' as const },
  photoOverlay: {
    position: 'absolute' as const, bottom: 0, left: 0, right: 0,
    backgroundColor: 'rgba(0,0,0,0.5)', padding: 4,
  },
  photoTitle: { fontSize: 10, color: '#fff' },
  emptyContainer: { alignItems: 'center' as const, paddingTop: 60 },
  emptyText: { color: 'rgba(245,230,200,0.4)', fontSize: 14 },
  lightbox: {
    flex: 1, backgroundColor: 'rgba(0,0,0,0.95)',
    alignItems: 'center' as const, justifyContent: 'center' as const,
  },
  lightboxImage: { width: SCREEN_WIDTH - 40, height: SCREEN_WIDTH - 40 },
  lightboxTitle: { color: '#f5e6c8', fontSize: 14, marginTop: 16 },
};
