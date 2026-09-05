import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Submissions from './Submissions.vue';
import { assignmentService } from '../services/assignmentService';
import { submissionService } from '../services/submissionService';

vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { assignmentId: 1 }
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
    listSubmissions: vi.fn(),
    gradeSubmission: vi.fn(),
  }
}));

describe('Submissions.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const getWrapper = () => {
    assignmentService.getAssignment.mockResolvedValueOnce({
      data: {
        data: {
          id: 1,
          title: 'Test Assignment',
          max_score: 100,
        }
      }
    });

    // A paginated list is itself { data, links, meta } and the backend's
    // ApiResponses trait wraps that again as { message, data: {...} } —
    // so the rows live at response.data.data.data, not response.data.data.
    submissionService.listSubmissions.mockResolvedValueOnce({
      data: {
        data: {
          data: [
            {
              id: 1,
              student: { name: 'Alice', email: 'alice@example.com' },
              submission_text: 'Alice answer',
              status: 'submitted',
              submitted_at: '2026-10-01T12:00:00Z',
            }
          ],
          meta: { last_page: 1 }
        }
      }
    });

    return mount(Submissions, {
      global: {
        stubs: {
          LoadingState: true,
          ErrorState: true,
        }
      }
    });
  };

  it('rejects score outside bounds client-side and disables submit button', async () => {
    const wrapper = getWrapper();
    await flushPromises();

    // The single submission card should be rendered
    expect(wrapper.find('.submission-card').exists()).toBe(true);

    const scoreInput = wrapper.find('input[type="number"]');
    const submitBtn = wrapper.find('.submit-btn');

    // Set score over max_score
    await scoreInput.setValue(150);
    
    expect(wrapper.find('.error-alert').exists()).toBe(true);
    expect(wrapper.find('.error-alert').text()).toContain('Score must be between 0 and 100');
    expect(submitBtn.attributes('disabled')).toBeDefined();
    
    // Set score under 0
    await scoreInput.setValue(-10);
    
    expect(wrapper.find('.error-alert').text()).toContain('Score must be between 0 and 100');
    expect(submitBtn.attributes('disabled')).toBeDefined();
    
    // Set valid score
    await scoreInput.setValue(95);
    
    expect(wrapper.find('.error-alert').exists()).toBe(false);
    // Button is not disabled anymore
    expect(submitBtn.attributes('disabled')).toBeUndefined();
  });

  it('disables submit button and ignores repeated clicks while saving', async () => {
    const wrapper = getWrapper();
    await flushPromises();

    // Mock an in-flight promise that doesn't resolve immediately
    let resolvePromise;
    const promise = new Promise((resolve) => {
      resolvePromise = resolve;
    });
    submissionService.gradeSubmission.mockReturnValue(promise);

    await wrapper.find('input[type="number"]').setValue(90);
    await wrapper.find('form.grading-form').trigger('submit.prevent');
    
    // Check that button is disabled immediately
    const submitBtn = wrapper.find('.submit-btn');
    expect(submitBtn.attributes('disabled')).toBeDefined();
    expect(submitBtn.text()).toBe('Saving...');

    // Trigger submit again to verify duplicate ignoring
    await wrapper.find('form.grading-form').trigger('submit.prevent');
    expect(submissionService.gradeSubmission).toHaveBeenCalledTimes(1);

    // Resolve the promise
    resolvePromise({
      data: {
        data: {
          id: 1,
          student: { name: 'Alice', email: 'alice@example.com' },
          submission_text: 'Alice answer',
          status: 'graded',
          score: 90,
          submitted_at: '2026-10-01T12:00:00Z',
        }
      }
    });

    await flushPromises();

    // Now it should be rendered as graded view
    expect(wrapper.find('.graded-view').exists()).toBe(true);
    expect(wrapper.find('.score-value').text()).toContain('90 / 100');
  });
});
