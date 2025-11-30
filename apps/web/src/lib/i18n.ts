import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import LanguageDetector from 'i18next-browser-languagedetector'

// Import translation files
import enCommon from '../locales/en/common.json'
import enAuth from '../locales/en/auth.json'
import enSales from '../locales/en/sales.json'
import enInventory from '../locales/en/inventory.json'
import enTreasury from '../locales/en/treasury.json'
import enValidation from '../locales/en/validation.json'

import frCommon from '../locales/fr/common.json'
import frAuth from '../locales/fr/auth.json'
import frSales from '../locales/fr/sales.json'
import frInventory from '../locales/fr/inventory.json'
import frTreasury from '../locales/fr/treasury.json'
import frValidation from '../locales/fr/validation.json'

export const languages = [
  { code: 'en', name: 'English', dir: 'ltr' },
  { code: 'fr', name: 'Français', dir: 'ltr' },
  { code: 'ar', name: 'العربية', dir: 'rtl' },
] as const

export type LanguageCode = (typeof languages)[number]['code']

const resources = {
  en: {
    common: enCommon,
    auth: enAuth,
    sales: enSales,
    inventory: enInventory,
    treasury: enTreasury,
    validation: enValidation,
  },
  fr: {
    common: frCommon,
    auth: frAuth,
    sales: frSales,
    inventory: frInventory,
    treasury: frTreasury,
    validation: frValidation,
  },
  ar: {
    // Arabic falls back to English - translations to be added later
    common: enCommon,
    auth: enAuth,
    sales: enSales,
    inventory: enInventory,
    treasury: enTreasury,
    validation: enValidation,
  },
}

void i18n
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    resources,
    fallbackLng: 'en',
    defaultNS: 'common',
    ns: ['common', 'auth', 'sales', 'inventory', 'treasury', 'validation'],

    detection: {
      order: ['querystring', 'localStorage', 'navigator'],
      lookupQuerystring: 'lang',
      lookupLocalStorage: 'autoerp-language',
      caches: ['localStorage'],
    },

    interpolation: {
      escapeValue: false, // React already escapes values
    },

    react: {
      useSuspense: false,
    },
  })

export default i18n
