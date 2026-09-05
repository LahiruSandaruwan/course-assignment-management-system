import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createTestingPinia } from '@pinia/testing';
import CourseDetail from './CourseDetail.vue';
import { courseService } from '../services/courseService';
import { studentService } from '../services/studentService';

vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { id: 1 }
  }),
}));

vi.mock('../services/courseService', () => ({
  courseService: {
    getCourse: vi.fn(),
    getAssignments: vi.fn(),
    getEnrolledStudents: vi.fn(),
    updateCourse: vi.fn(),
    enrollStudent: vi.fn(),
    removeStudent: vi.fn(),
  }
}));

vi.mock('../services/studentService', () => ({
  studentService: {
    search: vi.fn(),
  }
}));

const emptyPaginated = { data: [], meta: { current_page: 1, last_page: 1 } };

const mountAsAdmin = () => {
  courseService.getCourse.mockResolvedValue({
    data: {
      data: {
        id: 1,
        name: 'Test Course',
        description: 'A course',
        status: 'active',
        instructor_id: 2,
        instructor_name: 'Some Instructor',
        students_count: 0,
        assignments_count: 0,
        start_date: '2026-01-01',
        end_date: '2026-02-01',
      }
    }
  });
  courseService.getAssignments.mockResolvedValue({ data: { data: emptyPaginated } });
  courseService.getEnrolledStudents.mockResolvedValue({ data: { data: emptyPaginated } });

  return mount(CourseDetail, {
    global: {
      plugins: [createTestingPinia({
        createSpy: vi.fn,
        initialState: {
          auth: { user: { id: 1, name: 'Admin User', role: 'admin' } }
        }
      })],
      stubs: {
        LoadingState: true,
        ErrorState: true,
      }
    }
  });
};

const openEnrollForm = async (wrapper) => {
  const toggleButtons = wrapper.findAll('.enroll-toggle-btn');
  const enrollToggle = toggleButtons.find((b) => b.text().includes('Enroll'));
  await enrollToggle.trigger('click');
};

describe('CourseDetail.vue — student enrollment search', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('searches students after debounce and renders matches', async () => {
    const wrapper = mountAsAdmin();
    await flushPromises();
    await openEnrollForm(wrapper);

    studentService.search.mockResolvedValueOnce({
      data: {
        data: [
          { id: 3, name: 'Alice Johnson', email: 'alice@example.com', role: 'student' }
        ]
      }
    });

    await wrapper.find('#enroll-search').setValue('Alice');
    vi.advanceTimersByTime(300);
    await flushPromises();

    expect(studentService.search).toHaveBeenCalledWith('Alice');
    expect(wrapper.text()).toContain('[3] Alice Johnson (alice@example.com)');
  });

  it('shows a "no matching students" hint when the search returns nothing', async () => {
    const wrapper = mountAsAdmin();
    await flushPromises();
    await openEnrollForm(wrapper);

    studentService.search.mockResolvedValueOnce({ data: { data: [] } });

    await wrapper.find('#enroll-search').setValue('Nobody');
    vi.advanceTimersByTime(300);
    await flushPromises();

    expect(wrapper.text()).toContain('No matching students found.');
  });

  it('disables Enroll until a search result is selected, then enables it', async () => {
    const wrapper = mountAsAdmin();
    await flushPromises();
    await openEnrollForm(wrapper);

    studentService.search.mockResolvedValueOnce({
      data: {
        data: [
          { id: 3, name: 'Alice Johnson', email: 'alice@example.com', role: 'student' }
        ]
      }
    });

    const enrollSubmitBtn = wrapper.find('.enroll-form button[type="submit"]');
    expect(enrollSubmitBtn.attributes('disabled')).toBeDefined();

    await wrapper.find('#enroll-search').setValue('Alice');
    vi.advanceTimersByTime(300);
    await flushPromises();

    await wrapper.find('.search-result-item').trigger('click');

    expect(wrapper.find('.enroll-form button[type="submit"]').attributes('disabled')).toBeUndefined();
  });

  it('enrolls the selected student with the correct user_id and refreshes the list', async () => {
    const wrapper = mountAsAdmin();
    await flushPromises();
    await openEnrollForm(wrapper);

    studentService.search.mockResolvedValueOnce({
      data: {
        data: [
          { id: 3, name: 'Alice Johnson', email: 'alice@example.com', role: 'student' }
        ]
      }
    });
    courseService.enrollStudent.mockResolvedValueOnce({ data: { message: 'Student enrolled successfully', data: null } });

    await wrapper.find('#enroll-search').setValue('Alice');
    vi.advanceTimersByTime(300);
    await flushPromises();
    await wrapper.find('.search-result-item').trigger('click');

    await wrapper.find('.enroll-form').trigger('submit.prevent');
    await flushPromises();

    expect(courseService.enrollStudent).toHaveBeenCalledWith(1, 3);
    // Refresh calls after a successful enroll
    expect(courseService.getCourse).toHaveBeenCalledTimes(2);
    expect(courseService.getEnrolledStudents).toHaveBeenCalledTimes(2);
  });

  it('renders an inline error when enrollment fails (e.g. already enrolled)', async () => {
    const wrapper = mountAsAdmin();
    await flushPromises();
    await openEnrollForm(wrapper);

    studentService.search.mockResolvedValueOnce({
      data: {
        data: [
          { id: 3, name: 'Alice Johnson', email: 'alice@example.com', role: 'student' }
        ]
      }
    });
    courseService.enrollStudent.mockRejectedValueOnce({
      response: { status: 409, data: { message: 'Student is already enrolled in this course.' } }
    });

    await wrapper.find('#enroll-search').setValue('Alice');
    vi.advanceTimersByTime(300);
    await flushPromises();
    await wrapper.find('.search-result-item').trigger('click');

    await wrapper.find('.enroll-form').trigger('submit.prevent');
    await flushPromises();

    expect(wrapper.text()).toContain('Student is already enrolled in this course.');
  });
});

describe('CourseDetail.vue — assignment list navigation', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const mountWithAssignment = (assignment) => {
    courseService.getCourse.mockResolvedValue({
      data: {
        data: {
          id: 1,
          name: 'Test Course',
          status: 'active',
          instructor_id: 2,
          instructor_name: 'Some Instructor',
          students_count: 0,
          assignments_count: 1,
          start_date: '2026-01-01',
          end_date: '2026-02-01',
        }
      }
    });
    courseService.getAssignments.mockResolvedValue({
      data: { data: { data: [assignment], meta: { current_page: 1, last_page: 1 } } }
    });
    courseService.getEnrolledStudents.mockResolvedValue({
      data: { data: { data: [], meta: { current_page: 1, last_page: 1 } } }
    });

    const push = vi.fn();

    const wrapper = mount(CourseDetail, {
      global: {
        plugins: [createTestingPinia({
          createSpy: vi.fn,
          initialState: { auth: { user: { id: 1, name: 'Admin User', role: 'admin' } } }
        })],
        stubs: { LoadingState: true, ErrorState: true },
        mocks: { $router: { push } }
      }
    });

    return { wrapper, push };
  };

  it('navigates to the named AssignmentDetail route with the assignment id when clicked', async () => {
    const { wrapper, push } = mountWithAssignment({
      id: 42,
      title: 'Build a Fullstack App',
      due_date: '2026-05-01T00:00:00Z',
    });
    await flushPromises();

    await wrapper.find('.list-item.clickable').trigger('click');

    expect(push).toHaveBeenCalledWith({ name: 'AssignmentDetail', params: { id: 42 } });
  });

  it('does not navigate when the assignment has no id', async () => {
    const { wrapper, push } = mountWithAssignment({
      id: null,
      title: 'Broken row',
      due_date: '2026-05-01T00:00:00Z',
    });
    await flushPromises();

    await wrapper.find('.list-item.clickable').trigger('click');

    expect(push).not.toHaveBeenCalled();
  });

  it('shows a Submissions quick link for admins/instructors that navigates to the Submissions route without triggering the row click', async () => {
    const { wrapper, push } = mountWithAssignment({
      id: 42,
      title: 'Build a Fullstack App',
      due_date: '2026-05-01T00:00:00Z',
    });
    await flushPromises();

    const link = wrapper.find('.view-submissions-link');
    expect(link.exists()).toBe(true);

    await link.trigger('click');

    expect(push).toHaveBeenCalledTimes(1);
    expect(push).toHaveBeenCalledWith({ name: 'Submissions', params: { id: 1, assignmentId: 42 } });
  });
});
