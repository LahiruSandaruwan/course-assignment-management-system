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
    // A paginated list is itself { data, links, meta } and the backend's
    // ApiResponses trait wraps that again as { message, data: {...} } —
    // so the rows live at response.data.data.data, not response.data.data.
    courseService.getCourses.mockResolvedValueOnce({
      data: {
        data: {
          data: [],
          meta: { current_page: 1, last_page: 1 }
        }
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

  it('renders course cards and pagination from a real paginated API response', async () => {
    courseService.getCourses.mockResolvedValueOnce({
      data: {
        data: {
          data: [
            {
              id: 1,
              name: 'Advanced Vue.js and Laravel',
              status: 'active',
              instructor_name: 'Instructor User',
              students_count: 3,
              assignments_count: 2,
            },
            {
              id: 2,
              name: 'Introduction to Database Design',
              status: 'active',
              instructor_name: 'Instructor User',
              students_count: 3,
              assignments_count: 3,
            },
          ],
          meta: { current_page: 1, last_page: 2 },
        },
      },
    });

    const wrapper = mount(Courses, {
      global: {
        plugins: [createTestingPinia({ createSpy: vi.fn })],
      },
    });

    await flushPromises();

    const cards = wrapper.findAll('.course-card');
    expect(cards).toHaveLength(2);
    expect(wrapper.text()).toContain('Advanced Vue.js and Laravel');
    expect(wrapper.text()).toContain('Instructor User');
    expect(wrapper.text()).toContain('3');

    // Page 1 of 2: Previous disabled, Next enabled
    const [prevBtn, nextBtn] = wrapper.findAll('.pagination button');
    expect(prevBtn.attributes('disabled')).toBeDefined();
    expect(nextBtn.attributes('disabled')).toBeUndefined();
  });
});
