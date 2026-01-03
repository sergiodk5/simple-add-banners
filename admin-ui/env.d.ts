/// <reference types="vite/client" />

/**
 * WordPress Media Library attachment object.
 */
interface WPMediaAttachment {
  id: number
  url: string
  title: string
  alt: string
  filename: string
  type: string
  subtype: string
  width?: number
  height?: number
  sizes?: {
    [key: string]: {
      url: string
      width: number
      height: number
    }
  }
}

/**
 * WordPress Media Library selection object.
 */
interface WPMediaSelection {
  first(): { toJSON(): WPMediaAttachment }
  toArray(): Array<{ toJSON(): WPMediaAttachment }>
}

/**
 * WordPress Media Library state object.
 */
interface WPMediaState {
  get(key: 'selection'): WPMediaSelection
}

/**
 * WordPress Media Library frame instance.
 */
interface WPMediaFrame {
  on(event: 'select' | 'open' | 'close', callback: () => void): WPMediaFrame
  off(event: string, callback?: () => void): WPMediaFrame
  open(): WPMediaFrame
  close(): WPMediaFrame
  state(): WPMediaState
}

/**
 * WordPress Media Library frame options.
 */
interface WPMediaFrameOptions {
  title?: string
  button?: {
    text?: string
  }
  library?: {
    type?: string | string[]
  }
  multiple?: boolean
}

/**
 * WordPress wp.media API.
 */
interface WPMedia {
  (options: WPMediaFrameOptions): WPMediaFrame
}

/**
 * WordPress global object.
 */
interface WP {
  media: WPMedia
}

/**
 * Plugin admin configuration injected by wp_localize_script.
 */
interface SabAdmin {
  apiUrl: string
  nonce: string
  adminUrl: string
}

declare global {
  interface Window {
    wp: WP
    sabAdmin: SabAdmin
  }
}

export {}
