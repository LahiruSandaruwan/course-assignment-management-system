import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import ConfirmModal from './ConfirmModal.vue';

const baseProps = {
  show: true,
  title: 'Remove Student',
  message: 'Remove Alice from this course?',
  confirmText: 'Remove',
};

describe('ConfirmModal.vue', () => {
  it('renders nothing when show is false', () => {
    const wrapper = mount(ConfirmModal, { props: { ...baseProps, show: false } });
    expect(wrapper.find('.confirm-overlay').exists()).toBe(false);
  });

  it('renders the title, message, and confirm text when shown', () => {
    const wrapper = mount(ConfirmModal, { props: baseProps });
    expect(wrapper.text()).toContain('Remove Student');
    expect(wrapper.text()).toContain('Remove Alice from this course?');
    expect(wrapper.find('.confirm-btn').text()).toContain('Remove');
  });

  it('applies the destructive style to the confirm button when isDestructive is true', () => {
    const wrapper = mount(ConfirmModal, { props: { ...baseProps, isDestructive: true } });
    expect(wrapper.find('.confirm-btn').classes()).toContain('destructive');
  });

  it('emits confirm when the confirm button is clicked', async () => {
    const wrapper = mount(ConfirmModal, { props: baseProps });
    await wrapper.find('.confirm-btn').trigger('click');
    expect(wrapper.emitted('confirm')).toHaveLength(1);
  });

  it('emits cancel when the cancel button is clicked', async () => {
    const wrapper = mount(ConfirmModal, { props: baseProps });
    await wrapper.find('.cancel-btn').trigger('click');
    expect(wrapper.emitted('cancel')).toHaveLength(1);
  });

  it('emits cancel on a backdrop click', async () => {
    const wrapper = mount(ConfirmModal, { props: baseProps });
    await wrapper.find('.confirm-overlay').trigger('click');
    expect(wrapper.emitted('cancel')).toHaveLength(1);
  });

  it('does not emit cancel when clicking inside the card', async () => {
    const wrapper = mount(ConfirmModal, { props: baseProps });
    await wrapper.find('.confirm-card').trigger('click');
    expect(wrapper.emitted('cancel')).toBeUndefined();
  });

  it('disables both buttons and shows a spinner while isLoading', () => {
    const wrapper = mount(ConfirmModal, { props: { ...baseProps, isLoading: true } });
    expect(wrapper.find('.confirm-btn').attributes('disabled')).toBeDefined();
    expect(wrapper.find('.cancel-btn').attributes('disabled')).toBeDefined();
    expect(wrapper.find('.spinner').exists()).toBe(true);
  });

  it('ignores confirm/cancel clicks while isLoading', async () => {
    const wrapper = mount(ConfirmModal, { props: { ...baseProps, isLoading: true } });
    await wrapper.find('.confirm-btn').trigger('click');
    await wrapper.find('.cancel-btn').trigger('click');
    expect(wrapper.emitted('confirm')).toBeUndefined();
    expect(wrapper.emitted('cancel')).toBeUndefined();
  });

  it('emits cancel when the Escape key is pressed while shown', async () => {
    const wrapper = mount(ConfirmModal, { props: baseProps, attachTo: document.body });
    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    await Promise.resolve();
    expect(wrapper.emitted('cancel')).toHaveLength(1);
  });

  it('ignores Escape while isLoading', async () => {
    const wrapper = mount(ConfirmModal, {
      props: { ...baseProps, isLoading: true },
      attachTo: document.body,
    });
    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    await Promise.resolve();
    expect(wrapper.emitted('cancel')).toBeUndefined();
  });
});
