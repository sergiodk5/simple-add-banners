import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import App from '../App.vue'

describe('App', () => {
  it('renders the title', () => {
    const wrapper = mount(App)
    expect(wrapper.find('h1').text()).toBe('Simple Add Banners')
  })

  it('displays the hello world message', () => {
    const wrapper = mount(App)
    expect(wrapper.text()).toContain('Hello World from Vue 3 + TypeScript!')
  })

  it('displays initial count of 0', () => {
    const wrapper = mount(App)
    expect(wrapper.text()).toContain('Count: 0')
  })

  it('increments count when button is clicked', async () => {
    const wrapper = mount(App)

    expect(wrapper.text()).toContain('Count: 0')

    await wrapper.find('button').trigger('click')
    expect(wrapper.text()).toContain('Count: 1')

    await wrapper.find('button').trigger('click')
    expect(wrapper.text()).toContain('Count: 2')
  })

  it('has an increment button with correct text', () => {
    const wrapper = mount(App)
    const button = wrapper.find('button')

    expect(button.exists()).toBe(true)
    expect(button.text()).toBe('Increment')
  })

  it('has the correct wrapper class', () => {
    const wrapper = mount(App)
    expect(wrapper.find('.sab-admin-wrapper').exists()).toBe(true)
  })

  it('has the counter section', () => {
    const wrapper = mount(App)
    expect(wrapper.find('.sab-counter').exists()).toBe(true)
  })
})
