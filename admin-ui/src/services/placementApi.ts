import type { Placement, PlacementListParams, PlacementPayload } from '@/types/placement'
import { getApiClient } from './apiClient'

export interface PaginatedResponse<T> {
  data: T[]
  total: number
  totalPages: number
}

/**
 * Fetches a paginated list of placements.
 */
export async function getPlacements(
  params: PlacementListParams = {}
): Promise<PaginatedResponse<Placement>> {
  const client = getApiClient()

  const response = await client.get<Placement[]>('/placements', {
    params: {
      page: params.page,
      per_page: params.per_page,
      orderby: params.orderby,
      order: params.order,
    },
  })

  const total = parseInt(response.headers['x-wp-total'] || '0', 10)
  const totalPages = parseInt(response.headers['x-wp-totalpages'] || '0', 10)

  return {
    data: response.data,
    total,
    totalPages,
  }
}

/**
 * Fetches a single placement by ID.
 */
export async function getPlacement(id: number): Promise<Placement> {
  const client = getApiClient()
  const response = await client.get<Placement>(`/placements/${id}`)
  return response.data
}

/**
 * Creates a new placement.
 */
export async function createPlacement(payload: PlacementPayload): Promise<Placement> {
  const client = getApiClient()
  const response = await client.post<Placement>('/placements', payload)
  return response.data
}

/**
 * Updates an existing placement.
 */
export async function updatePlacement(
  id: number,
  payload: Partial<PlacementPayload>
): Promise<Placement> {
  const client = getApiClient()
  const response = await client.put<Placement>(`/placements/${id}`, payload)
  return response.data
}

/**
 * Deletes a placement.
 */
export async function deletePlacement(id: number): Promise<void> {
  const client = getApiClient()
  await client.delete(`/placements/${id}`)
}
