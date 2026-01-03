import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import PrimeVue from 'primevue/config'
import Button from 'primevue/button'
import ImagePicker from '../components/ImagePicker.vue'

// Mock wp.media
const mockMediaFrame = {
  on: vi.fn().mockReturnThis(),
  off: vi.fn().mockReturnThis(),
  open: vi.fn().mockReturnThis(),
  close: vi.fn().mockReturnThis(),
  state: vi.fn(),
}

const mockWpMedia = vi.fn(() => mockMediaFrame)

// Store the select callback for testing
let selectCallback: (() => void) | null = null

describe('ImagePicker', () => {
  let wrapper: VueWrapper

  const createWrapper = (props = {}) => {
    return mount(ImagePicker, {
      props: {
        modelValue: null,
        ...props,
      },
      global: {
        plugins: [PrimeVue],
        components: { Button },
      },
    })
  }

  beforeEach(() => {
    // Reset mocks
    vi.clearAllMocks()
    selectCallback = null

    // Capture the select callback when on() is called
    mockMediaFrame.on.mockImplementation((event: string, callback: () => void) => {
      if (event === 'select') {
        selectCallback = callback
      }
      return mockMediaFrame
    })

    // Setup window.wp.media mock
    vi.stubGlobal('wp', {
      media: mockWpMedia,
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
    vi.unstubAllGlobals()
  })

  describe('rendering', () => {
    it('renders without label when not provided', () => {
      wrapper = createWrapper()

      expect(wrapper.find('label').exists()).toBe(false)
    })

    it('renders with label when provided', () => {
      wrapper = createWrapper({ label: 'Desktop Image' })

      expect(wrapper.find('label').text()).toBe('Desktop Image')
    })

    it('shows Select Image button when no image selected', () => {
      wrapper = createWrapper()

      const button = wrapper.findComponent(Button)
      expect(button.props('label')).toBe('Select Image')
    })

    it('shows Change Image button when image is selected', () => {
      wrapper = createWrapper({ imageUrl: 'https://example.com/image.jpg' })

      const button = wrapper.findAllComponents(Button).find((b) => b.props('label'))
      expect(button?.props('label')).toBe('Change Image')
    })

    it('displays image preview when imageUrl is provided', () => {
      wrapper = createWrapper({ imageUrl: 'https://example.com/image.jpg' })

      const img = wrapper.find('img')
      expect(img.exists()).toBe(true)
      expect(img.attributes('src')).toBe('https://example.com/image.jpg')
    })

    it('does not display image preview when no imageUrl', () => {
      wrapper = createWrapper()

      expect(wrapper.find('img').exists()).toBe(false)
    })

    it('uses label as alt text for image', () => {
      wrapper = createWrapper({
        label: 'Banner Image',
        imageUrl: 'https://example.com/image.jpg',
      })

      const img = wrapper.find('img')
      expect(img.attributes('alt')).toBe('Banner Image')
    })

    it('uses default alt text when no label', () => {
      wrapper = createWrapper({ imageUrl: 'https://example.com/image.jpg' })

      const img = wrapper.find('img')
      expect(img.attributes('alt')).toBe('Selected image')
    })
  })

  describe('media library interaction', () => {
    it('opens media library with correct options when Select Image clicked', async () => {
      wrapper = createWrapper({ label: 'Desktop Image' })

      const button = wrapper.findComponent(Button)
      await button.trigger('click')

      expect(mockWpMedia).toHaveBeenCalledWith({
        title: 'Desktop Image',
        button: { text: 'Use this image' },
        library: { type: 'image' },
        multiple: false,
      })
      expect(mockMediaFrame.open).toHaveBeenCalled()
    })

    it('uses default title when no label provided', async () => {
      wrapper = createWrapper()

      const button = wrapper.findComponent(Button)
      await button.trigger('click')

      expect(mockWpMedia).toHaveBeenCalledWith(
        expect.objectContaining({
          title: 'Select Image',
        })
      )
    })

    it('reuses existing frame on subsequent clicks', async () => {
      wrapper = createWrapper()

      const button = wrapper.findComponent(Button)
      await button.trigger('click')
      await button.trigger('click')

      expect(mockWpMedia).toHaveBeenCalledTimes(1)
      expect(mockMediaFrame.open).toHaveBeenCalledTimes(2)
    })

    it('emits update events when image is selected', async () => {
      const mockAttachment = {
        id: 123,
        url: 'https://example.com/selected.jpg',
        title: 'Test Image',
        alt: 'Test Alt',
        filename: 'selected.jpg',
        type: 'image',
        subtype: 'jpeg',
      }

      mockMediaFrame.state.mockReturnValue({
        get: () => ({
          first: () => ({
            toJSON: () => mockAttachment,
          }),
        }),
      })

      wrapper = createWrapper()

      const button = wrapper.findComponent(Button)
      await button.trigger('click')

      // Simulate the select event
      expect(selectCallback).not.toBeNull()
      selectCallback!()

      expect(wrapper.emitted('update:modelValue')).toEqual([[123]])
      expect(wrapper.emitted('update:imageUrl')).toEqual([['https://example.com/selected.jpg']])
    })

    it('logs error when wp.media is not available', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
      vi.stubGlobal('wp', undefined)

      wrapper = createWrapper()

      const button = wrapper.findComponent(Button)
      await button.trigger('click')

      expect(consoleSpy).toHaveBeenCalledWith('WordPress Media Library not available')
      consoleSpy.mockRestore()
    })
  })

  describe('clearing image', () => {
    it('shows remove button when image is selected', () => {
      wrapper = createWrapper({ imageUrl: 'https://example.com/image.jpg' })

      const removeButton = wrapper.findAllComponents(Button).find((b) => b.props('icon') === 'pi pi-times')
      expect(removeButton).toBeDefined()
    })

    it('emits null values when remove button clicked', async () => {
      wrapper = createWrapper({
        modelValue: 123,
        imageUrl: 'https://example.com/image.jpg',
      })

      const removeButton = wrapper.findAllComponents(Button).find((b) => b.props('icon') === 'pi pi-times')
      await removeButton!.trigger('click')

      expect(wrapper.emitted('update:modelValue')).toEqual([[null]])
      expect(wrapper.emitted('update:imageUrl')).toEqual([[null]])
    })

    it('hides image preview after clearing', async () => {
      wrapper = createWrapper({
        modelValue: 123,
        imageUrl: 'https://example.com/image.jpg',
      })

      expect(wrapper.find('img').exists()).toBe(true)

      const removeButton = wrapper.findAllComponents(Button).find((b) => b.props('icon') === 'pi pi-times')
      await removeButton!.trigger('click')

      expect(wrapper.find('img').exists()).toBe(false)
    })
  })

  describe('prop reactivity', () => {
    it('updates preview when imageUrl prop changes', async () => {
      wrapper = createWrapper({ imageUrl: 'https://example.com/image1.jpg' })

      expect(wrapper.find('img').attributes('src')).toBe('https://example.com/image1.jpg')

      await wrapper.setProps({ imageUrl: 'https://example.com/image2.jpg' })

      expect(wrapper.find('img').attributes('src')).toBe('https://example.com/image2.jpg')
    })

    it('handles null imageUrl prop', async () => {
      wrapper = createWrapper({ imageUrl: 'https://example.com/image.jpg' })

      expect(wrapper.find('img').exists()).toBe(true)

      await wrapper.setProps({ imageUrl: null })

      expect(wrapper.find('img').exists()).toBe(false)
    })
  })

  describe('cleanup', () => {
    it('cleans up media frame on unmount', async () => {
      wrapper = createWrapper()

      // Open the media library to create the frame
      const button = wrapper.findComponent(Button)
      await button.trigger('click')

      // Unmount
      wrapper.unmount()

      expect(mockMediaFrame.off).toHaveBeenCalledWith('select')
    })
  })
})
