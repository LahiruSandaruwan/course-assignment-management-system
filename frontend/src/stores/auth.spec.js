import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from './auth';
import { authService } from '../services/authService';

// Mock the authService
vi.mock('../services/authService', () => ({
  authService: {
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
  }
}));

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    // Clear localStorage before each test
    localStorage.clear();
    vi.clearAllMocks();
  });

  it('initializes with null user and token if localStorage is empty', () => {
    const store = useAuthStore();
    expect(store.user).toBeNull();
    expect(store.token).toBeNull();
    expect(store.isAuthenticated).toBe(false);
  });

  it('loads token from localStorage on initialization', () => {
    localStorage.setItem('auth_token', 'test-token-123');
    const store = useAuthStore();
    
    expect(store.token).toBe('test-token-123');
    expect(store.isAuthenticated).toBe(true);
  });

  it('logs in successfully and sets token in state and localStorage', async () => {
    const store = useAuthStore();
    const mockResponse = {
      data: {
        access_token: 'new-token',
        user: { id: 1, name: 'Test User' }
      }
    };
    
    authService.login.mockResolvedValueOnce(mockResponse);

    const result = await store.login({ email: 'test@example.com', password: 'password' });

    expect(authService.login).toHaveBeenCalledWith({ email: 'test@example.com', password: 'password' });
    expect(store.token).toBe('new-token');
    expect(store.user).toEqual({ id: 1, name: 'Test User' });
    expect(store.isAuthenticated).toBe(true);
    expect(localStorage.getItem('auth_token')).toBe('new-token');
    expect(result).toEqual(mockResponse.data);
  });

  it('clears auth state on login failure', async () => {
    const store = useAuthStore();
    store.token = 'old-token';
    localStorage.setItem('auth_token', 'old-token');
    
    const mockError = new Error('Invalid credentials');
    authService.login.mockRejectedValueOnce(mockError);

    await expect(store.login({ email: 'test@example.com', password: 'wrong' })).rejects.toThrow('Invalid credentials');

    expect(store.token).toBeNull();
    expect(store.user).toBeNull();
    expect(store.isAuthenticated).toBe(false);
    expect(localStorage.getItem('auth_token')).toBeNull();
  });

  it('logs out successfully and clears state', async () => {
    const store = useAuthStore();
    store.token = 'existing-token';
    store.user = { id: 1 };
    localStorage.setItem('auth_token', 'existing-token');
    
    authService.logout.mockResolvedValueOnce({});

    await store.logout();

    expect(authService.logout).toHaveBeenCalled();
    expect(store.token).toBeNull();
    expect(store.user).toBeNull();
    expect(store.isAuthenticated).toBe(false);
    expect(localStorage.getItem('auth_token')).toBeNull();
  });
});
