import { fileURLToPath } from 'node:url'
import { mergeConfig, defineConfig, configDefaults } from 'vitest/config'
import viteConfig from './vite.config'

export default mergeConfig(
  viteConfig,
  defineConfig({
    test: {
      environment: 'jsdom',
      exclude: [...configDefaults.exclude, 'e2e/**'],
      root: fileURLToPath(new URL('./', import.meta.url)),
      coverage: {
        exclude: [
          'src/main.ts',
          'src/types/**',
          '**/*.config.ts',
          '**/*.config.cjs',
          '**/.eslintrc.cjs',
          '**/__tests__/**',
          '**/*.d.ts',
        ],
      },
    },
  }),
)
