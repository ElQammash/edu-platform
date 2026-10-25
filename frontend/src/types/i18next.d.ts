import 'i18next'
import type en from '@/locales/en.json'

// Type-checks translation keys: t('app.name') compiles, t('app.typo') does not.
declare module 'i18next' {
  interface CustomTypeOptions {
    resources: {
      translation: typeof en
    }
  }
}
