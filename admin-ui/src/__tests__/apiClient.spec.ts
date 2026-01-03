import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import axios from 'axios'
import type { AxiosInstance } from 'axios'
import { createApiClient, getApiClient, setApiClient } from '../services/apiClient'

vi.stubGlobal('sabAdmin', {
  apiUrl: '/wp-json/sab/v1',
  nonce: 'test-nonce-123',
  adminUrl: '/wp-admin/',
})

describe('apiClient', () => {
  let mockAxiosInstance: AxiosInstance

  beforeEach(() => {
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
})

describe('apiClient interceptors', () => {
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
