import axios, { type AxiosInstance, type AxiosError } from 'axios'

let apiClient: AxiosInstance | null = null

/**
 * Creates and configures the axios instance with WordPress nonce.
 */
export function createApiClient(): AxiosInstance {
  const client = axios.create({
    baseURL: window.sabAdmin?.apiUrl || '/wp-json/sab/v1',
    headers: {
      'Content-Type': 'application/json',
    },
  })

  client.interceptors.request.use((config) => {
    const nonce = window.sabAdmin?.nonce || ''
    if (nonce) {
      config.headers['X-WP-Nonce'] = nonce
    }
    return config
  })

  client.interceptors.response.use(
    (response) => response,
    (error: AxiosError<{ message?: string }>) => {
      const message = error.response?.data?.message || error.message || 'An error occurred'
      return Promise.reject(new Error(message))
    },
  )

  return client
}

/**
 * Gets or creates the singleton API client instance.
 */
export function getApiClient(): AxiosInstance {
  if (!apiClient) {
    apiClient = createApiClient()
  }
  return apiClient
}

/**
 * Sets a custom API client (useful for testing).
 */
export function setApiClient(client: AxiosInstance | null): void {
  apiClient = client
}
