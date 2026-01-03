import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import axios from 'axios'
import type { AxiosInstance } from 'axios'
import {
  createApiClient,
  getApiClient,
  setApiClient,
  getBanners,
  getBanner,
  createBanner,
  updateBanner,
  deleteBanner,
} from '../services/bannerApi'
import type { Banner, BannerPayload } from '@/types/banner'

// Mock window.sabAdmin
vi.stubGlobal('sabAdmin', {
  apiUrl: '/wp-json/sab/v1',
  nonce: 'test-nonce-123',
  adminUrl: '/wp-admin/',
})

const mockBanner: Banner = {
  id: 1,
  title: 'Test Banner',
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

describe('bannerApi', () => {
  let mockAxiosInstance: AxiosInstance

  beforeEach(() => {
    // Create a mock axios instance
    mockAxiosInstance = {
      get: vi.fn(),
      post: vi.fn(),
      put: vi.fn(),
      delete: vi.fn(),
      interceptors: {
        request: { use: vi.fn() },
        response: { use: vi.fn() },
      },
    } as unknown as AxiosInstance

    // Reset the API client before each test
    setApiClient(null)
  })

  afterEach(() => {
    vi.clearAllMocks()
    setApiClient(null)
  })

  describe('createApiClient', () => {
    it('creates axios instance with correct baseURL', () => {
      const createSpy = vi.spyOn(axios, 'create').mockReturnValue(mockAxiosInstance)

      createApiClient()

      expect(createSpy).toHaveBeenCalledWith({
        baseURL: '/wp-json/sab/v1',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      createSpy.mockRestore()
    })

    it('uses default baseURL when sabAdmin is not available', () => {
      const originalSabAdmin = window.sabAdmin
      // @ts-expect-error - testing undefined case
      window.sabAdmin = undefined

      const createSpy = vi.spyOn(axios, 'create').mockReturnValue(mockAxiosInstance)

      createApiClient()

      expect(createSpy).toHaveBeenCalledWith({
        baseURL: '/wp-json/sab/v1',
        headers: {
          'Content-Type': 'application/json',
        },
      })

      window.sabAdmin = originalSabAdmin
      createSpy.mockRestore()
    })

    it('sets up request interceptor for nonce', () => {
      const mockInterceptors = {
        request: { use: vi.fn() },
        response: { use: vi.fn() },
      }
      const mockClient = {
        ...mockAxiosInstance,
        interceptors: mockInterceptors,
      } as unknown as AxiosInstance

      vi.spyOn(axios, 'create').mockReturnValue(mockClient)

      createApiClient()

      expect(mockInterceptors.request.use).toHaveBeenCalled()
    })

    it('sets up response interceptor for error handling', () => {
      const mockInterceptors = {
        request: { use: vi.fn() },
        response: { use: vi.fn() },
      }
      const mockClient = {
        ...mockAxiosInstance,
        interceptors: mockInterceptors,
      } as unknown as AxiosInstance

      vi.spyOn(axios, 'create').mockReturnValue(mockClient)

      createApiClient()

      expect(mockInterceptors.response.use).toHaveBeenCalled()
    })
  })

  describe('getApiClient', () => {
    it('creates client on first call', () => {
      const createSpy = vi.spyOn(axios, 'create').mockReturnValue(mockAxiosInstance)

      getApiClient()

      expect(createSpy).toHaveBeenCalledTimes(1)
      createSpy.mockRestore()
    })

    it('returns same client on subsequent calls', () => {
      const createSpy = vi.spyOn(axios, 'create').mockReturnValue(mockAxiosInstance)

      const client1 = getApiClient()
      const client2 = getApiClient()

      expect(client1).toBe(client2)
      expect(createSpy).toHaveBeenCalledTimes(1)
      createSpy.mockRestore()
    })
  })

  describe('setApiClient', () => {
    it('allows setting a custom client', () => {
      setApiClient(mockAxiosInstance)

      const client = getApiClient()

      expect(client).toBe(mockAxiosInstance)
    })

    it('allows resetting the client to null', () => {
      setApiClient(mockAxiosInstance)
      setApiClient(null)

      const createSpy = vi.spyOn(axios, 'create').mockReturnValue(mockAxiosInstance)

      getApiClient()

      expect(createSpy).toHaveBeenCalled()
      createSpy.mockRestore()
    })
  })

  describe('getBanners', () => {
    beforeEach(() => {
      setApiClient(mockAxiosInstance)
    })

    it('fetches banners successfully', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: [mockBanner],
        headers: {
          'x-wp-total': '10',
          'x-wp-totalpages': '2',
        },
      })

      const result = await getBanners()

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/banners', {
        params: {
          page: undefined,
          per_page: undefined,
          status: undefined,
          orderby: undefined,
          order: undefined,
        },
      })
      expect(result.data).toEqual([mockBanner])
      expect(result.total).toBe(10)
      expect(result.totalPages).toBe(2)
    })

    it('passes query parameters correctly', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: [],
        headers: {
          'x-wp-total': '0',
          'x-wp-totalpages': '0',
        },
      })

      await getBanners({
        page: 2,
        per_page: 20,
        status: 'active',
        orderby: 'title',
        order: 'ASC',
      })

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/banners', {
        params: {
          page: 2,
          per_page: 20,
          status: 'active',
          orderby: 'title',
          order: 'ASC',
        },
      })
    })

    it('handles missing pagination headers', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: [mockBanner],
        headers: {},
      })

      const result = await getBanners()

      expect(result.total).toBe(0)
      expect(result.totalPages).toBe(0)
    })
  })

  describe('getBanner', () => {
    beforeEach(() => {
      setApiClient(mockAxiosInstance)
    })

    it('fetches a single banner', async () => {
      vi.mocked(mockAxiosInstance.get).mockResolvedValue({
        data: mockBanner,
      })

      const result = await getBanner(1)

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/banners/1')
      expect(result).toEqual(mockBanner)
    })
  })

  describe('createBanner', () => {
    beforeEach(() => {
      setApiClient(mockAxiosInstance)
    })

    it('creates a new banner', async () => {
      const payload: BannerPayload = {
        title: 'New Banner',
        desktop_url: 'https://example.com',
        mobile_url: null,
        desktop_image_id: null,
        mobile_image_id: null,
        start_date: null,
        end_date: null,
        status: 'active',
        weight: 1,
      }

      vi.mocked(mockAxiosInstance.post).mockResolvedValue({
        data: { ...mockBanner, ...payload },
      })

      const result = await createBanner(payload)

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/banners', payload)
      expect(result.title).toBe('New Banner')
    })
  })

  describe('updateBanner', () => {
    beforeEach(() => {
      setApiClient(mockAxiosInstance)
    })

    it('updates an existing banner', async () => {
      const payload = { title: 'Updated Banner' }

      vi.mocked(mockAxiosInstance.put).mockResolvedValue({
        data: { ...mockBanner, ...payload },
      })

      const result = await updateBanner(1, payload)

      expect(mockAxiosInstance.put).toHaveBeenCalledWith('/banners/1', payload)
      expect(result.title).toBe('Updated Banner')
    })
  })

  describe('deleteBanner', () => {
    beforeEach(() => {
      setApiClient(mockAxiosInstance)
    })

    it('deletes a banner', async () => {
      vi.mocked(mockAxiosInstance.delete).mockResolvedValue({})

      await deleteBanner(1)

      expect(mockAxiosInstance.delete).toHaveBeenCalledWith('/banners/1')
    })
  })
})

describe('bannerApi interceptors', () => {
  it('request interceptor adds nonce header', () => {
    let requestInterceptor: (config: { headers: Record<string, string> }) => { headers: Record<string, string> }

    const mockInterceptors = {
      request: {
        use: vi.fn((fn) => {
          requestInterceptor = fn
        }),
      },
      response: { use: vi.fn() },
    }

    const mockClient = {
      interceptors: mockInterceptors,
    } as unknown as AxiosInstance

    vi.spyOn(axios, 'create').mockReturnValue(mockClient)

    createApiClient()

    // Test the interceptor
    const config = { headers: {} as Record<string, string> }
    // @ts-expect-error - requestInterceptor is assigned in the mock
    const result = requestInterceptor(config)

    expect(result.headers['X-WP-Nonce']).toBe('test-nonce-123')
  })

  it('request interceptor handles missing nonce', () => {
    const originalSabAdmin = window.sabAdmin
    window.sabAdmin = { apiUrl: '/test', nonce: '', adminUrl: '/admin/' }

    let requestInterceptor: (config: { headers: Record<string, string> }) => { headers: Record<string, string> }

    const mockInterceptors = {
      request: {
        use: vi.fn((fn) => {
          requestInterceptor = fn
        }),
      },
      response: { use: vi.fn() },
    }

    const mockClient = {
      interceptors: mockInterceptors,
    } as unknown as AxiosInstance

    vi.spyOn(axios, 'create').mockReturnValue(mockClient)

    createApiClient()

    const config = { headers: {} as Record<string, string> }
    // @ts-expect-error - requestInterceptor is assigned in the mock
    const result = requestInterceptor(config)

    expect(result.headers['X-WP-Nonce']).toBeUndefined()

    window.sabAdmin = originalSabAdmin
  })

  it('response interceptor transforms error with message', async () => {
    let errorInterceptor: (error: { response?: { data?: { message?: string } }; message?: string }) => Promise<never>

    const mockInterceptors = {
      request: { use: vi.fn() },
      response: {
        use: vi.fn((_success, errorFn) => {
          errorInterceptor = errorFn
        }),
      },
    }

    const mockClient = {
      interceptors: mockInterceptors,
    } as unknown as AxiosInstance

    vi.spyOn(axios, 'create').mockReturnValue(mockClient)

    createApiClient()

    const axiosError = {
      response: {
        data: { message: 'Server error message' },
      },
    }

    // @ts-expect-error - errorInterceptor is assigned in the mock
    await expect(errorInterceptor(axiosError)).rejects.toThrow('Server error message')
  })

  it('response interceptor uses axios error message as fallback', async () => {
    let errorInterceptor: (error: { response?: { data?: { message?: string } }; message?: string }) => Promise<never>

    const mockInterceptors = {
      request: { use: vi.fn() },
      response: {
        use: vi.fn((_success, errorFn) => {
          errorInterceptor = errorFn
        }),
      },
    }

    const mockClient = {
      interceptors: mockInterceptors,
    } as unknown as AxiosInstance

    vi.spyOn(axios, 'create').mockReturnValue(mockClient)

    createApiClient()

    const axiosError = {
      message: 'Network Error',
    }

    // @ts-expect-error - errorInterceptor is assigned in the mock
    await expect(errorInterceptor(axiosError)).rejects.toThrow('Network Error')
  })

  it('response interceptor uses default message when no error info available', async () => {
    let errorInterceptor: (error: { response?: { data?: { message?: string } }; message?: string }) => Promise<never>

    const mockInterceptors = {
      request: { use: vi.fn() },
      response: {
        use: vi.fn((_success, errorFn) => {
          errorInterceptor = errorFn
        }),
      },
    }

    const mockClient = {
      interceptors: mockInterceptors,
    } as unknown as AxiosInstance

    vi.spyOn(axios, 'create').mockReturnValue(mockClient)

    createApiClient()

    const axiosError = {}

    // @ts-expect-error - errorInterceptor is assigned in the mock
    await expect(errorInterceptor(axiosError)).rejects.toThrow('An error occurred')
  })
})
