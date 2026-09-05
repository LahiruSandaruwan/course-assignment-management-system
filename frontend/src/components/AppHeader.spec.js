import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import { createTestingPinia } from '@pinia/testing';
import AppHeader from './AppHeader.vue';

vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: vi.fn(),
  }),
}));

const mountAsRole = (role) =>
  mount(AppHeader, {
    global: {
      plugins: [
        createTestingPinia({
          createSpy: vi.fn,
          initialState: {
            auth: { user: { id: 1, name: 'Test User', role } },
          },
        }),
      ],
      stubs: {
        RouterLink: {
          template: '<a :href="to"><slot /></a>',
          props: ['to'],
        },
      },
    },
  });

describe('AppHeader.vue', () => {
  it.each(['student', 'instructor', 'admin'])(
    'shows the Courses link for a %s',
    (role) => {
      const wrapper = mountAsRole(role);

      const coursesLink = wrapper.find('a[href="/courses"]');
      expect(coursesLink.exists()).toBe(true);
      expect(coursesLink.text()).toBe('Courses');
    }
  );

  it.each(['student', 'instructor'])(
    'hides the Users link for a %s',
    (role) => {
      const wrapper = mountAsRole(role);

      expect(wrapper.find('a[href="/users"]').exists()).toBe(false);
    }
  );

  it('shows the Users link for an admin, pointing at /users', () => {
    const wrapper = mountAsRole('admin');

    const usersLink = wrapper.find('a[href="/users"]');
    expect(usersLink.exists()).toBe(true);
    expect(usersLink.text()).toBe('Users');
  });
});
