/**
 * Rotation strategy type.
 */
export type RotationStrategy = 'random' | 'weighted' | 'ordered'

/**
 * Placement entity interface.
 */
export interface Placement {
  id: number
  slug: string
  name: string
  rotation_strategy: RotationStrategy
  created_at: string
  updated_at: string
}

/**
 * Placement create/update payload.
 */
export interface PlacementPayload {
  slug: string
  name: string
  rotation_strategy?: RotationStrategy
}

/**
 * Placement list query parameters.
 */
export interface PlacementListParams {
  page?: number
  per_page?: number
  orderby?: string
  order?: 'ASC' | 'DESC'
}
