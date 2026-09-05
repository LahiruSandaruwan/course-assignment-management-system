import { describe, it, expect, vi, beforeEach } from 'vitest';
import api from './api';
import { useAuthStore } from '../stores/auth';
import { createPinia, setActivePinia } from 'pinia';
import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';

describe('API Interceptor', () => {
  let mock;

  beforeEach(() => {
    setActivePinia(createPinia());
    mock = new MockAdapter(api);
    delete window.location;
    window.location = { href: '' };
  });

  it('attaches Authorization header if token exists', async () => {
    const authStore = useAuthStore();
    authStore.token = 'test-token';

    mock.onGet('/test').reply(200, {});

    const response = await api.get('/test');
    expect(response.config.headers.Authorization).toBe('Bearer test-token');
  });

  it('handles 401 by clearing auth and redirecting', async () => {
    const authStore = useAuthStore();
    authStore.clearAuth = vi.fn();

    mock.onGet('/protected').reply(401, {});

    try {
      await api.get('/protected');
    } catch (error) {
      expect(error.response.status).toBe(401);
    }

    expect(authStore.clearAuth).toHaveBeenCalled();
    expect(window.location.href).toBe('/login');
  });

  it('passes through 422 validation errors', async () => {
    mock.onPost('/submit').reply(422, { message: 'Invalid data' });

    try {
      await api.post('/submit');
    } catch (error) {
      expect(error.response.status).toBe(422);
      expect(error.response.data.message).toBe('Invalid data');
    }
  });
});
