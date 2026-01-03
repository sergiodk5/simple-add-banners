import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PrimeVue from 'primevue/config'
import App from '../App.vue'

const globalConfig = {
  global: {
    plugins: [PrimeVue],
  },
}

describe('App', () => {
  it('renders the title', () => {
    const wrapper = mount(App, globalConfig)
    expect(wrapper.text()).toContain('Simple Add Banners')
  })

  it('displays the hello world message', () => {
    const wrapper = mount(App, globalConfig)
    expect(wrapper.text()).toContain('Hello World from Vue 3 + TypeScript!')
  })

  it('displays initial count of 0', () => {
    const wrapper = mount(App, globalConfig)
    expect(wrapper.text()).toContain('Count: 0')
  })

  it('increments count when button is clicked', async () => {
    const wrapper = mount(App, globalConfig)

    expect(wrapper.text()).toContain('Count: 0')

    await wrapper.find('button').trigger('click')
    expect(wrapper.text()).toContain('Count: 1')

    await wrapper.find('button').trigger('click')
    expect(wrapper.text()).toContain('Count: 2')
  })

  it('has an increment button', () => {
    const wrapper = mount(App, globalConfig)
    const button = wrapper.find('button')

    expect(button.exists()).toBe(true)
    expect(button.text()).toContain('Increment')
  })

  it('has a search input', () => {
    const wrapper = mount(App, globalConfig)
    const input = wrapper.find('input')

    expect(input.exists()).toBe(true)
  })

  it('has a Card component', () => {
    const wrapper = mount(App, globalConfig)
    expect(wrapper.findComponent({ name: 'Card' }).exists()).toBe(true)
  })
})
