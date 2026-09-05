import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createTestingPinia } from '@pinia/testing';
import AssignmentDetail from './AssignmentDetail.vue';
import { assignmentService } from '../services/assignmentService';
import { submissionService } from '../services/submissionService';

vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { id: 1 }
  }),
  useRouter: () => ({
    back: vi.fn(),
  }),
}));

vi.mock('../services/assignmentService', () => ({
  assignmentService: {
    getAssignment: vi.fn(),
  }
}));

vi.mock('../services/submissionService', () => ({
  submissionService: {
    getMySubmission: vi.fn(),
    submitAssignment: vi.fn(),
  }
}));

describe('AssignmentDetail.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const getWrapper = (role = 'student', assignmentStatus = 'published', hasSubmission = false) => {
    assignmentService.getAssignment.mockResolvedValueOnce({
      data: {
        data: {
          id: 1,
          title: 'Test Assignment',
          course_id: 1,
          due_date: '2026-10-01T00:00:00Z',
          max_score: 100,
          status: assignmentStatus
        }
      }
    });

    if (role === 'student' && assignmentStatus === 'published') {
      if (hasSubmission) {
        submissionService.getMySubmission.mockResolvedValueOnce({
          data: {
            data: {
              id: 1,
              submission_text: 'Existing answer',
              status: 'submitted'
            }
          }
        });
      } else {
        submissionService.getMySubmission.mockRejectedValueOnce({
          response: { status: 404 }
        });
      }
    }

    return mount(AssignmentDetail, {
      global: {
        plugins: [createTestingPinia({
          createSpy: vi.fn,
          initialState: {
            auth: { user: { id: 1, role: role } }
          }
        })],
      }
    });
  };

  it('renders submission form for students if no submission exists', async () => {
    const wrapper = getWrapper('student', 'published', false);
    await flushPromises();

    expect(wrapper.find('.submission-form').exists()).toBe(true);
    expect(wrapper.find('textarea').element.value).toBe('');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Submit Assignment');
  });

  it('renders pre-filled submission form if un-graded submission exists', async () => {
    const wrapper = getWrapper('student', 'published', true);
    await flushPromises();

    expect(wrapper.find('.submission-form').exists()).toBe(true);
    expect(wrapper.find('textarea').element.value).toBe('Existing answer');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Update Submission');
  });

  it('submits successfully and shows success message', async () => {
    const wrapper = getWrapper('student', 'published', false);
    await flushPromises();

    submissionService.submitAssignment.mockResolvedValueOnce({
      data: {
        data: {
          id: 2,
          submission_text: 'My new answer',
          status: 'submitted'
        }
      }
    });

    await wrapper.find('textarea').setValue('My new answer');
    await wrapper.find('form').trigger('submit.prevent');

    // Button should show loading state
    expect(wrapper.find('button[type="submit"]').text()).toBe('Submitting...');
    
    await flushPromises();

    expect(submissionService.submitAssignment).toHaveBeenCalledWith(1, 'My new answer');
    expect(wrapper.find('.success-alert').text()).toBe('Assignment submitted successfully!');
    expect(wrapper.find('button[type="submit"]').text()).toBe('Update Submission');
  });

  it('shows error state when submission fails validation', async () => {
    const wrapper = getWrapper('student', 'published', false);
    await flushPromises();

    submissionService.submitAssignment.mockRejectedValueOnce({
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            submission_text: ['The submission text is too short.']
          }
        }
      }
    });

    await wrapper.find('textarea').setValue('short');
    await wrapper.find('form').trigger('submit.prevent');
    
    await flushPromises();

    expect(wrapper.find('.error-alert').text()).toBe('The submission text is too short.');
  });
  
  it('does not render submission section for instructors', async () => {
    const wrapper = getWrapper('instructor');
    await flushPromises();

    expect(wrapper.find('.submission-section').exists()).toBe(false);
  });
});
