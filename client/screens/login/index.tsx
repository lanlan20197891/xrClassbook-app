import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, KeyboardAvoidingView, Platform, Alert } from 'react-native';
import { Screen } from '@/components/Screen';
import { useAuth } from '@/contexts/AuthContext';
import { useSafeRouter } from '@/hooks/useSafeRouter';

export default function LoginScreen() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();
  const router = useSafeRouter();

  const [stars] = useState(() => {
    return Array.from({ length: 20 }, () => ({
      left: `${Math.random() * 100}%`,
      top: `${Math.random() * 60}%`,
      width: Math.random() * 3 + 1,
      height: Math.random() * 3 + 1,
      opacity: Math.random() * 0.7 + 0.3,
    }));
  });

  const handleLogin = async () => {
    if (!username.trim() || !password.trim()) {
      Alert.alert('提示', '请输入用户名和密码');
      return;
    }
    setLoading(true);
    const result = await login(username.trim(), password);
    setLoading(false);
    if (result.ok) {
      router.replace('/');
    } else {
      Alert.alert('登录失败', result.msg || '未知错误');
    }
  };

  return (
    <Screen>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <View style={styles.container}>
          {/* Stars decoration */}
          <View style={styles.starsContainer}>
            {stars.map((s, i) => (
              <View
                key={i}
                style={[
                  styles.star,
                  {
                    left: s.left,
                    top: s.top,
                    width: s.width,
                    height: s.height,
                    opacity: s.opacity,
                  },
                ]}
              />
            ))}
          </View>

          {/* Moon */}
          <View style={styles.moonContainer}>
            <View style={styles.moon} />
            <View style={styles.moonGlow} />
          </View>

          {/* Title */}
          <Text style={styles.title}>小若同学录</Text>
          <Text style={styles.subtitle}>愿此去前程似锦 再相逢依旧如故</Text>

          {/* Login form */}
          <View style={styles.formContainer}>
            <View style={styles.inputWrapper}>
              <Text style={styles.inputLabel}>用户名</Text>
              <TextInput
                style={styles.input}
                value={username}
                onChangeText={setUsername}
                placeholder="请输入用户名"
                placeholderTextColor="#666"
                autoCapitalize="none"
              />
            </View>

            <View style={styles.inputWrapper}>
              <Text style={styles.inputLabel}>密码</Text>
              <TextInput
                style={styles.input}
                value={password}
                onChangeText={setPassword}
                placeholder="请输入密码"
                placeholderTextColor="#666"
                secureTextEntry
              />
            </View>

            <TouchableOpacity
              style={[styles.loginButton, loading && styles.loginButtonDisabled]}
              onPress={handleLogin}
              disabled={loading}
            >
              <Text style={styles.loginButtonText}>{loading ? '登录中...' : '进入同学录'}</Text>
            </TouchableOpacity>
          </View>

          <Text style={styles.footerText}>月光所照 皆是故乡</Text>
        </View>
      </KeyboardAvoidingView>
    </Screen>
  );
}

const styles = {
  container: {
    flex: 1,
    backgroundColor: '#0a0e1a',
    alignItems: 'center' as const,
    justifyContent: 'center' as const,
    padding: 24,
  },
  starsContainer: {
    position: 'absolute' as const,
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
  },
  star: {
    position: 'absolute' as const,
    backgroundColor: '#fff',
    borderRadius: 100,
  },
  moonContainer: {
    width: 80,
    height: 80,
    alignItems: 'center' as const,
    justifyContent: 'center' as const,
    marginBottom: 24,
  },
  moon: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: '#f5e6c8',
    shadowColor: '#f5e6c8',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.6,
    shadowRadius: 20,
  },
  moonGlow: {
    position: 'absolute' as const,
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: 'rgba(245,230,200,0.1)',
  },
  title: {
    fontSize: 28,
    fontWeight: '700' as const,
    color: '#f5e6c8',
    marginBottom: 8,
    letterSpacing: 4,
  },
  subtitle: {
    fontSize: 13,
    color: 'rgba(245,230,200,0.6)',
    marginBottom: 40,
    letterSpacing: 2,
  },
  formContainer: {
    width: '100%' as const,
    maxWidth: 340,
    gap: 16,
  },
  inputWrapper: {
    gap: 6,
  },
  inputLabel: {
    fontSize: 12,
    color: 'rgba(245,230,200,0.7)',
    paddingLeft: 4,
  },
  input: {
    height: 48,
    backgroundColor: 'rgba(255,255,255,0.06)',
    borderRadius: 12,
    paddingHorizontal: 16,
    color: '#f5e6c8',
    fontSize: 15,
    borderWidth: 1,
    borderColor: 'rgba(245,230,200,0.15)',
  },
  loginButton: {
    height: 48,
    backgroundColor: 'rgba(245,230,200,0.15)',
    borderRadius: 12,
    alignItems: 'center' as const,
    justifyContent: 'center' as const,
    marginTop: 8,
    borderWidth: 1,
    borderColor: 'rgba(245,230,200,0.3)',
  },
  loginButtonDisabled: {
    opacity: 0.5,
  },
  loginButtonText: {
    color: '#f5e6c8',
    fontSize: 16,
    fontWeight: '600' as const,
    letterSpacing: 2,
  },
  footerText: {
    marginTop: 40,
    fontSize: 11,
    color: 'rgba(245,230,200,0.3)',
    letterSpacing: 2,
  },
};
