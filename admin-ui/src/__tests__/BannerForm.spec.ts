import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import BannerForm from '../components/BannerForm.vue'
import type { Banner } from '@/types/banner'

// Mock the bannerApi module
const mockCreateBanner = vi.fn()
const mockUpdateBanner = vi.fn()

vi.mock('@/services/bannerApi', () => ({
  getBanners: vi.fn().mockResolvedValue({ data: [], total: 0, totalPages: 0 }),
  getBanner: vi.fn(),
  createBanner: (...args: unknown[]) => mockCreateBanner(...args),
  updateBanner: (...args: unknown[]) => mockUpdateBanner(...args),
  deleteBanner: vi.fn(),
}))

// Mock window.sabAdmin
vi.stubGlobal('sabAdmin', {
  apiUrl: '/wp-json/sab/v1',
  nonce: 'test-nonce',
  adminUrl: '/wp-admin/',
})

const mockBanner: Banner = {
  id: 1,
  title: 'Test Banner',
  desktop_image_id: 100,
  mobile_image_id: 200,
  desktop_url: 'https://example.com',
  mobile_url: 'https://mobile.example.com',
  start_date: '2024-01-01T00:00:00',
  end_date: '2024-12-31T23:59:59',
  status: 'active',
  weight: 5,
  created_at: '2024-01-01T00:00:00',
  updated_at: '2024-01-01T00:00:00',
}

describe('BannerForm', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  const mountBannerForm = (props: { visible: boolean; banner: Banner | null }) => {
    return mount(BannerForm, {
      props,
      global: {
        plugins: [createPinia(), PrimeVue, ToastService],
        stubs: {
          Dialog: {
            template: `
              <div v-if="visible" class="dialog-stub">
                <div class="dialog-header">{{ header }}</div>
                <slot />
                <div class="dialog-footer"><slot name="footer" /></div>
              </div>
            `,
            props: ['visible', 'header', 'modal', 'style', 'closable', 'closeOnEscape'],
            emits: ['update:visible', 'hide'],
          },
          InputText: {
            template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" :class="{ invalid: invalid }" />',
            props: ['modelValue', 'invalid', 'placeholder'],
            emits: ['update:modelValue'],
          },
          InputNumber: {
            template: '<input type="number" :value="modelValue" @input="$emit(\'update:modelValue\', parseInt($event.target.value))" />',
            props: ['modelValue', 'min', 'max'],
            emits: ['update:modelValue'],
          },
          Select: {
            template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option></select>',
            props: ['modelValue', 'options', 'optionLabel', 'optionValue', 'placeholder'],
            emits: ['update:modelValue'],
          },
          DatePicker: {
            template: '<input type="date" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
            props: ['modelValue', 'dateFormat', 'showTime', 'hourFormat', 'placeholder'],
            emits: ['update:modelValue'],
          },
          Button: {
            template: '<button :disabled="disabled" @click="$emit(\'click\')">{{ label }}</button>',
            props: ['label', 'severity', 'disabled', 'loading'],
            emits: ['click'],
          },
        },
      },
    })
  }

  it('renders create dialog when banner is null', () => {
    const wrapper = mountBannerForm({ visible: true, banner: null })
    expect(wrapper.find('.dialog-header').text()).toBe('Create Banner')
  })

  it('renders edit dialog when banner is provided', () => {
    const wrapper = mountBannerForm({ visible: true, banner: mockBanner })
    expect(wrapper.find('.dialog-header').text()).toBe('Edit Banner')
  })

  it('does not render when not visible', () => {
    const wrapper = mountBannerForm({ visible: false, banner: null })
    expect(wrapper.find('.dialog-stub').exists()).toBe(false)
  })

  it('populates form fields when editing', () => {
    const wrapper = mountBannerForm({ visible: true, banner: mockBanner })
    const inputs = wrapper.findAll('input')
    const titleInput = inputs[0]
    expect(titleInput.element.value).toBe('Test Banner')
  })

  it('shows Create button for new banner', () => {
    const wrapper = mountBannerForm({ visible: true, banner: null })
    expect(wrapper.text()).toContain('Create')
  })

  it('shows Update button when editing', () => {
    const wrapper = mountBannerForm({ visible: true, banner: mockBanner })
    expect(wrapper.text()).toContain('Update')
  })

  it('shows Cancel button', () => {
    const wrapper = mountBannerForm({ visible: true, banner: null })
    expect(wrapper.text()).toContain('Cancel')
  })

  it('emits update:visible when cancel is clicked', async () => {
    const wrapper = mountBannerForm({ visible: true, banner: null })
    const cancelButton = wrapper.findAll('button').find((b) => b.text() === 'Cancel')
    await cancelButton?.trigger('click')
    expect(wrapper.emitted('update:visible')).toBeTruthy()
  })

  it('validates title is required', async () => {
    const wrapper = mountBannerForm({ visible: true, banner: null })

    // Set desktop URL to pass that validation
    const inputs = wrapper.findAll('input')
    await inputs[1].setValue('https://example.com')

    // Submit form
    const submitButton = wrapper.findAll('button').find((b) => b.text() === 'Create')
    await submitButton?.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Title is required')
  })

  it('validates desktop URL is required', async () => {
    const wrapper = mountBannerForm({ visible: true, banner: null })

    // Set title to pass that validation
    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Test Banner')

    // Submit form
    const submitButton = wrapper.findAll('button').find((b) => b.text() === 'Create')
    await submitButton?.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Desktop URL is required')
  })

  it('validates desktop URL format', async () => {
    const wrapper = mountBannerForm({ visible: true, banner: null })

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Test Banner')
    await inputs[1].setValue('not-a-url')

    const submitButton = wrapper.findAll('button').find((b) => b.text() === 'Create')
    await submitButton?.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Please enter a valid URL')
  })

  it('validates mobile URL format when provided', async () => {
    const wrapper = mountBannerForm({ visible: true, banner: null })

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Test Banner')
    await inputs[1].setValue('https://example.com')
    await inputs[2].setValue('invalid-url')

    const submitButton = wrapper.findAll('button').find((b) => b.text() === 'Create')
    await submitButton?.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Please enter a valid URL')
  })

  it('creates banner successfully', async () => {
    const newBanner: Banner = {
      id: 2,
      title: 'New Banner',
      desktop_image_id: null,
      mobile_image_id: null,
      desktop_url: 'https://example.com',
      mobile_url: null,
      start_date: null,
      end_date: null,
      status: 'active',
      weight: 1,
      created_at: '2024-01-01T00:00:00',
      updated_at: '2024-01-01T00:00:00',
    }

    mockCreateBanner.mockResolvedValue(newBanner)

    const wrapper = mountBannerForm({ visible: true, banner: null })

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('New Banner')
    await inputs[1].setValue('https://example.com')

    const submitButton = wrapper.findAll('button').find((b) => b.text() === 'Create')
    await submitButton?.trigger('click')
    await flushPromises()

    expect(wrapper.emitted('saved')).toBeTruthy()
    expect(wrapper.emitted('update:visible')?.[0]).toEqual([false])
  })

  it('updates banner successfully', async () => {
    const updatedBanner: Banner = {
      ...mockBanner,
      title: 'Updated Banner',
    }

    mockUpdateBanner.mockResolvedValue(updatedBanner)

    const wrapper = mountBannerForm({ visible: true, banner: mockBanner })

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Updated Banner')

    const submitButton = wrapper.findAll('button').find((b) => b.text() === 'Update')
    await submitButton?.trigger('click')
    await flushPromises()

    expect(wrapper.emitted('saved')).toBeTruthy()
  })

  it('handles create error', async () => {
    mockCreateBanner.mockRejectedValue(new Error('Create failed'))

    const wrapper = mountBannerForm({ visible: true, banner: null })

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('New Banner')
    await inputs[1].setValue('https://example.com')

    const submitButton = wrapper.findAll('button').find((b) => b.text() === 'Create')
    await submitButton?.trigger('click')
    await flushPromises()

    // The error is handled with a toast
    expect(wrapper.emitted('saved')).toBeFalsy()
  })

  it('handles non-Error create failure', async () => {
    mockCreateBanner.mockRejectedValue('String error')

    const wrapper = mountBannerForm({ visible: true, banner: null })

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('New Banner')
    await inputs[1].setValue('https://example.com')

    const submitButton = wrapper.findAll('button').find((b) => b.text() === 'Create')
    await submitButton?.trigger('click')
    await flushPromises()

    expect(wrapper.emitted('saved')).toBeFalsy()
  })

  it('resets form when banner prop changes to null', async () => {
    const wrapper = mountBannerForm({ visible: true, banner: mockBanner })

    await wrapper.setProps({ banner: null })
    await flushPromises()

    const inputs = wrapper.findAll('input')
    expect(inputs[0].element.value).toBe('')
  })

  it('resets form on dialog hide', async () => {
    const wrapper = mountBannerForm({ visible: true, banner: mockBanner })

    // Simulate dialog hide
    await wrapper.setProps({ visible: false })
    await wrapper.setProps({ visible: true, banner: null })
    await flushPromises()

    const inputs = wrapper.findAll('input')
    expect(inputs[0].element.value).toBe('')
  })
})

describe('BannerForm validation edge cases', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  const mountBannerForm = (props: { visible: boolean; banner: Banner | null }) => {
    return mount(BannerForm, {
      props,
      global: {
        plugins: [createPinia(), PrimeVue, ToastService],
        stubs: {
          Dialog: {
            template: `
              <div v-if="visible" class="dialog-stub">
                <slot />
                <div class="dialog-footer"><slot name="footer" /></div>
              </div>
            `,
            props: ['visible', 'header', 'modal', 'style', 'closable', 'closeOnEscape'],
            emits: ['update:visible', 'hide'],
          },
          InputText: {
            template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
            props: ['modelValue', 'invalid', 'placeholder'],
            emits: ['update:modelValue'],
          },
          InputNumber: {
            template: '<input type="number" :value="modelValue" @input="$emit(\'update:modelValue\', parseInt($event.target.value))" />',
            props: ['modelValue', 'min', 'max'],
            emits: ['update:modelValue'],
          },
          Select: {
            template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"></select>',
            props: ['modelValue', 'options', 'optionLabel', 'optionValue', 'placeholder'],
            emits: ['update:modelValue'],
          },
          DatePicker: {
            template: '<input type="date" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
            props: ['modelValue', 'dateFormat', 'showTime', 'hourFormat', 'placeholder'],
            emits: ['update:modelValue'],
          },
          Button: {
            template: '<button @click="$emit(\'click\')">{{ label }}</button>',
            props: ['label', 'severity', 'disabled', 'loading'],
            emits: ['click'],
          },
        },
      },
    })
  }

  it('passes validation with valid mobile URL', async () => {
    mockCreateBanner.mockResolvedValue({
      id: 1,
      title: 'Test',
      desktop_url: 'https://example.com',
      mobile_url: 'https://mobile.example.com',
      desktop_image_id: null,
      mobile_image_id: null,
      start_date: null,
      end_date: null,
      status: 'active',
      weight: 1,
      created_at: '2024-01-01T00:00:00',
      updated_at: '2024-01-01T00:00:00',
    })

    const wrapper = mountBannerForm({ visible: true, banner: null })

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Test')
    await inputs[1].setValue('https://example.com')
    await inputs[2].setValue('https://mobile.example.com')

    const submitButton = wrapper.findAll('button').find((b) => b.text() === 'Create')
    await submitButton?.trigger('click')
    await flushPromises()

    expect(wrapper.emitted('saved')).toBeTruthy()
  })

  it('allows empty mobile URL', async () => {
    mockCreateBanner.mockResolvedValue({
      id: 1,
      title: 'Test',
      desktop_url: 'https://example.com',
      mobile_url: null,
      desktop_image_id: null,
      mobile_image_id: null,
      start_date: null,
      end_date: null,
      status: 'active',
      weight: 1,
      created_at: '2024-01-01T00:00:00',
      updated_at: '2024-01-01T00:00:00',
    })

    const wrapper = mountBannerForm({ visible: true, banner: null })

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Test')
    await inputs[1].setValue('https://example.com')
    // Leave mobile URL empty

    const submitButton = wrapper.findAll('button').find((b) => b.text() === 'Create')
    await submitButton?.trigger('click')
    await flushPromises()

    expect(wrapper.emitted('saved')).toBeTruthy()
  })
})
