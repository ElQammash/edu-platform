import i18n from 'i18next'
import LanguageDetector from 'i18next-browser-languagedetector'
import { initReactI18next } from 'react-i18next'
import ar from '@/locales/ar.json'
import en from '@/locales/en.json'

export const SUPPORTED_LANGUAGES = ['ar', 'en'] as const
export type Language = (typeof SUPPORTED_LANGUAGES)[number]

export const LANGUAGE_STORAGE_KEY = 'locale'

// Keep <html lang/dir> in sync so the browser, Tailwind's rtl:/ltr: variants
// and the Arabic font rule in index.css all follow the active language.
i18n.on('languageChanged', (language) => {
  document.documentElement.lang = language
  document.documentElement.dir = i18n.dir(language)
})

void i18n
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    resources: {
      ar: { translation: ar },
      en: { translation: en },
    },
    supportedLngs: SUPPORTED_LANGUAGES,
    // Arabic first: the platform targets Egypt.
    fallbackLng: 'ar',
    load: 'languageOnly',
    detection: {
      order: ['localStorage', 'navigator'],
      lookupLocalStorage: LANGUAGE_STORAGE_KEY,
      caches: ['localStorage'],
      // "ar-EG" -> "ar", "en-US" -> "en", so <html lang> and storage hold our codes.
      convertDetectedLanguage: (language: string) => language.split('-')[0],
    },
    interpolation: {
      // React already escapes rendered values.
      escapeValue: false,
    },
  })

export default i18n
