import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createTestingPinia } from '@pinia/testing';
import Courses from './Courses.vue';
import { courseService } from '../services/courseService';

vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: vi.fn(),
  }),
}));

vi.mock('../services/courseService', () => ({
  courseService: {
    getCourses: vi.fn(),
  }
}));

describe('Courses.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders loading state initially', () => {
    // mock a promise that doesn't resolve immediately
    courseService.getCourses.mockReturnValue(new Promise(() => {}));
    
    const wrapper = mount(Courses, {
      global: {
        plugins: [createTestingPinia({
          createSpy: vi.fn,
          initialState: {
            auth: { user: { id: 1, name: 'Student' } }
          }
        })],
      }
    });

    expect(wrapper.text()).toContain('Loading courses...');
  });

  it('renders empty state when API returns no courses', async () => {
    courseService.getCourses.mockResolvedValueOnce({
      data: {
        data: [],
        meta: { current_page: 1, last_page: 1 }
      }
    });
    
    const wrapper = mount(Courses, {
      global: {
        plugins: [createTestingPinia({ createSpy: vi.fn })],
      }
    });

    // Wait for the onMounted fetch to complete and DOM to update
    await flushPromises();

    expect(courseService.getCourses).toHaveBeenCalledTimes(1);
    expect(wrapper.text()).toContain("You don't have any courses yet.");
    expect(wrapper.find('.course-grid').exists()).toBe(false);
  });
});
