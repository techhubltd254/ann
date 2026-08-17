import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import * as SecureStore from 'expo-secure-store';

interface AuthContextType {
  user: any | null;
  token: string | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  isLoading: boolean;
}

const AuthContext = createContext<AuthContextType>({
  user: null, token: null, login: async () => {}, logout: async () => {}, isLoading: true,
});

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<any>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    SecureStore.getItemAsync('token').then(t => {
      if (t) { setToken(t); }
      setIsLoading(false);
    });
  }, []);

  const login = async (email: string, password: string) => {
    const res = await fetch('https://kicctest.org/api/login', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });
    const data = await res.json();
    if (data.token) {
      await SecureStore.setItemAsync('token', data.token);
      setToken(data.token);
      setUser(data.user);
    } else {
      throw new Error(data.message || 'Login failed');
    }
  };

  const logout = async () => {
    await SecureStore.deleteItemAsync('token');
    setToken(null); setUser(null);
  };

  return <AuthContext.Provider value={{ user, token, login, logout, isLoading }}>{children}</AuthContext.Provider>;
}

export const useAuth = () => useContext(AuthContext);
