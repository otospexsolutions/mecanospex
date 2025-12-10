import {
  createContext,
  useContext,
  useState,
  useEffect,
  PropsWithChildren,
} from 'react';
import * as SecureStore from 'expo-secure-store';
import { api } from '@/lib/api';

interface User {
  id: number;
  name: string;
  email: string;
  current_company_id: number;
}

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  selectCompany: (companyId: number) => Promise<void>;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({ children }: PropsWithChildren) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const token = await SecureStore.getItemAsync('auth_token');
      if (token) {
        const response = await api.get('/user');
        setUser(response.data.data);
      }
    } catch {
      await SecureStore.deleteItemAsync('auth_token');
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (email: string, password: string) => {
    const response = await api.post('/auth/login', { email, password });
    const { token, user: userData } = response.data.data;

    await SecureStore.setItemAsync('auth_token', token);
    setUser(userData);
  };

  const logout = async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      // Ignore errors
    }
    await SecureStore.deleteItemAsync('auth_token');
    setUser(null);
  };

  const selectCompany = async (companyId: number) => {
    await api.post('/user/switch-company', { company_id: companyId });
    const response = await api.get('/user');
    setUser(response.data.data);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading,
        isAuthenticated: !!user,
        login,
        logout,
        selectCompany,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}
