import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, ArrowRight, Check, Building2, MapPin, Globe, Phone, Mail, Loader2 } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { createCompany, type CreateCompanyInput } from './api'
import { useInvalidateCompanies } from './CompanyProvider'
import { getErrorMessage } from '../../lib/api'
import { useCompanyStore } from '../../stores/companyStore'
import { cn } from '@/lib/utils'

const STEPS = ['country', 'company', 'contact', 'review'] as const
type Step = (typeof STEPS)[number]

interface CountryConfig {
  name: string
  code: string
  currency: string
  locale: string
  timezone: string
}

const COUNTRIES: CountryConfig[] = [
  { name: 'France', code: 'FR', currency: 'EUR', locale: 'fr_FR', timezone: 'Europe/Paris' },
  { name: 'Tunisia', code: 'TN', currency: 'TND', locale: 'ar_TN', timezone: 'Africa/Tunis' },
  { name: 'United Kingdom', code: 'GB', currency: 'GBP', locale: 'en_GB', timezone: 'Europe/London' },
  { name: 'Italy', code: 'IT', currency: 'EUR', locale: 'it_IT', timezone: 'Europe/Rome' },
  { name: 'Morocco', code: 'MA', currency: 'MAD', locale: 'ar_MA', timezone: 'Africa/Casablanca' },
  { name: 'Algeria', code: 'DZ', currency: 'DZD', locale: 'ar_DZ', timezone: 'Africa/Algiers' },
  { name: 'United States', code: 'US', currency: 'USD', locale: 'en_US', timezone: 'America/New_York' },
]

export function CompanyOnboardingPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const invalidateCompanies = useInvalidateCompanies()
  const setCurrentCompany = useCompanyStore((state) => state.setCurrentCompany)

  const [currentStep, setCurrentStep] = useState<Step>('country')
  const [formData, setFormData] = useState({
    countryCode: 'FR',
    name: '',
    legalName: '',
    taxId: '',
    email: '',
    phone: '',
    addressStreet: '',
    addressCity: '',
    addressPostalCode: '',
  })
  const [error, setError] = useState<string | null>(null)

  const stepIndex = STEPS.indexOf(currentStep)
  const selectedCountry = COUNTRIES.find((c) => c.code === formData.countryCode) ?? COUNTRIES[0]

  const mutation = useMutation({
    mutationFn: async (input: CreateCompanyInput) => {
      return createCompany(input)
    },
    onSuccess: (data) => {
      void invalidateCompanies()
      setCurrentCompany(data.id)
      navigate('/dashboard')
    },
    onError: (err: unknown) => {
      setError(getErrorMessage(err))
    },
  })

  const canProceed = () => {
    switch (currentStep) {
      case 'country':
        return !!formData.countryCode
      case 'company':
        return !!formData.name.trim()
      case 'contact':
        return true // Optional fields
      case 'review':
        return true
      default:
        return false
    }
  }

  const nextStep = () => {
    setError(null)
    const nextIndex = stepIndex + 1
    if (nextIndex < STEPS.length) {
      setCurrentStep(STEPS[nextIndex])
    }
  }

  const prevStep = () => {
    setError(null)
    const prevIndex = stepIndex - 1
    if (prevIndex >= 0) {
      setCurrentStep(STEPS[prevIndex])
    }
  }

  const handleSubmit = () => {
    if (!formData.name.trim()) {
      setError('Company name is required')
      return
    }

    mutation.mutate({
      name: formData.name,
      legalName: formData.legalName || undefined,
      countryCode: formData.countryCode,
      currency: selectedCountry.currency,
      locale: selectedCountry.locale,
      timezone: selectedCountry.timezone,
      taxId: formData.taxId || undefined,
      email: formData.email || undefined,
      phone: formData.phone || undefined,
      addressStreet: formData.addressStreet || undefined,
      addressCity: formData.addressCity || undefined,
      addressPostalCode: formData.addressPostalCode || undefined,
    })
  }

  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-3xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <Link
            to="/dashboard"
            className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
          >
            <ArrowLeft className="w-4 h-4 me-1" />
            {t('back')}
          </Link>
          <div className="flex items-center gap-3 mb-2">
            <Building2 className="w-8 h-8 text-blue-600" />
            <h1 className="text-3xl font-bold">{t('Add New Company')}</h1>
          </div>
          <p className="text-gray-500">{t('Set up your company profile in a few simple steps')}</p>
        </div>

        {/* Progress Steps */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            {STEPS.map((step, index) => {
              const isActive = index === stepIndex
              const isCompleted = index < stepIndex
              return (
                <div key={step} className="flex items-center flex-1">
                  <div
                    className={cn(
                      'w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium',
                      isActive && 'bg-blue-600 text-white',
                      isCompleted && 'bg-green-600 text-white',
                      !isActive && !isCompleted && 'bg-gray-200 text-gray-600'
                    )}
                  >
                    {isCompleted ? <Check className="w-5 h-5" /> : index + 1}
                  </div>
                  <span
                    className={cn(
                      'ms-2 text-sm font-medium hidden sm:inline',
                      isActive && 'text-blue-600',
                      isCompleted && 'text-green-600',
                      !isActive && !isCompleted && 'text-gray-500'
                    )}
                  >
                    {step.charAt(0).toUpperCase() + step.slice(1)}
                  </span>
                  {index < STEPS.length - 1 && (
                    <div
                      className={cn(
                        'flex-1 h-0.5 mx-4',
                        isCompleted ? 'bg-green-600' : 'bg-gray-200'
                      )}
                    />
                  )}
                </div>
              )
            })}
          </div>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700 border border-red-200">
            {error}
          </div>
        )}

        {/* Step Content */}
        <div className="bg-white rounded-lg border border-gray-200 shadow-sm p-8 mb-6">
          {/* Step 1: Country Selection */}
          {currentStep === 'country' && (
            <div className="space-y-6">
              <div>
                <h2 className="text-xl font-semibold mb-2 flex items-center gap-2">
                  <Globe className="w-6 h-6 text-blue-600" />
                  {t('Select Your Country')}
                </h2>
                <p className="text-gray-500 text-sm">
                  {t('This will determine your default currency, timezone, and regional settings')}
                </p>
              </div>

              <div className="space-y-3">
                {COUNTRIES.map((country) => (
                  <button
                    key={country.code}
                    type="button"
                    onClick={() => { setFormData({ ...formData, countryCode: country.code }); }}
                    className={cn(
                      'w-full text-start p-4 rounded-lg border-2 transition-all',
                      formData.countryCode === country.code
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300'
                    )}
                  >
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="font-medium text-gray-900">{country.name}</p>
                        <p className="text-sm text-gray-500">
                          {country.currency} • {country.timezone}
                        </p>
                      </div>
                      {formData.countryCode === country.code && (
                        <Check className="w-5 h-5 text-blue-600" />
                      )}
                    </div>
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Step 2: Company Information */}
          {currentStep === 'company' && (
            <div className="space-y-6">
              <div>
                <h2 className="text-xl font-semibold mb-2 flex items-center gap-2">
                  <Building2 className="w-6 h-6 text-blue-600" />
                  {t('Company Information')}
                </h2>
                <p className="text-gray-500 text-sm">
                  {t('Enter your company details')}
                </p>
              </div>

              <div className="space-y-4">
                <div>
                  <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                    {t('Company Name')} <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    id="name"
                    value={formData.name}
                    onChange={(e) => { setFormData({ ...formData, name: e.target.value }); }}
                    placeholder={t('My Company')}
                    className="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    autoFocus
                  />
                </div>

                <div>
                  <label htmlFor="legalName" className="block text-sm font-medium text-gray-700 mb-1">
                    {t('Legal Name')}
                  </label>
                  <input
                    type="text"
                    id="legalName"
                    value={formData.legalName}
                    onChange={(e) => { setFormData({ ...formData, legalName: e.target.value }); }}
                    placeholder={t('My Company SARL')}
                    className="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <p className="mt-1 text-xs text-gray-500">
                    {t('If different from company name')}
                  </p>
                </div>

                <div>
                  <label htmlFor="taxId" className="block text-sm font-medium text-gray-700 mb-1">
                    {t('Tax ID / VAT Number')}
                  </label>
                  <input
                    type="text"
                    id="taxId"
                    value={formData.taxId}
                    onChange={(e) => { setFormData({ ...formData, taxId: e.target.value }); }}
                    placeholder="FR12345678901"
                    className="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>
          )}

          {/* Step 3: Contact Information */}
          {currentStep === 'contact' && (
            <div className="space-y-6">
              <div>
                <h2 className="text-xl font-semibold mb-2 flex items-center gap-2">
                  <Mail className="w-6 h-6 text-blue-600" />
                  {t('Contact Information')}
                </h2>
                <p className="text-gray-500 text-sm">
                  {t('Add your contact details and address (optional)')}
                </p>
              </div>

              <div className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1">
                      <Mail className="w-4 h-4" />
                      {t('Email')}
                    </label>
                    <input
                      type="email"
                      id="email"
                      value={formData.email}
                      onChange={(e) => { setFormData({ ...formData, email: e.target.value }); }}
                      placeholder="contact@company.com"
                      className="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1">
                      <Phone className="w-4 h-4" />
                      {t('Phone')}
                    </label>
                    <input
                      type="tel"
                      id="phone"
                      value={formData.phone}
                      onChange={(e) => { setFormData({ ...formData, phone: e.target.value }); }}
                      placeholder="+33 1 23 45 67 89"
                      className="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>

                <div>
                  <label htmlFor="addressStreet" className="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1">
                    <MapPin className="w-4 h-4" />
                    {t('Street Address')}
                  </label>
                  <input
                    type="text"
                    id="addressStreet"
                    value={formData.addressStreet}
                    onChange={(e) => { setFormData({ ...formData, addressStreet: e.target.value }); }}
                    placeholder="123 Main Street"
                    className="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label htmlFor="addressCity" className="block text-sm font-medium text-gray-700 mb-1">
                      {t('City')}
                    </label>
                    <input
                      type="text"
                      id="addressCity"
                      value={formData.addressCity}
                      onChange={(e) => { setFormData({ ...formData, addressCity: e.target.value }); }}
                      placeholder="Paris"
                      className="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label htmlFor="addressPostalCode" className="block text-sm font-medium text-gray-700 mb-1">
                      {t('Postal Code')}
                    </label>
                    <input
                      type="text"
                      id="addressPostalCode"
                      value={formData.addressPostalCode}
                      onChange={(e) => { setFormData({ ...formData, addressPostalCode: e.target.value }); }}
                      placeholder="75001"
                      className="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Step 4: Review */}
          {currentStep === 'review' && (
            <div className="space-y-6">
              <div>
                <h2 className="text-xl font-semibold mb-2 flex items-center gap-2">
                  <Check className="w-6 h-6 text-blue-600" />
                  {t('Review & Confirm')}
                </h2>
                <p className="text-gray-500 text-sm">
                  {t('Please review your information before creating the company')}
                </p>
              </div>

              <div className="space-y-4">
                <div className="bg-gray-50 rounded-lg p-4">
                  <h3 className="font-semibold text-gray-900 mb-3">{t('Country & Regional Settings')}</h3>
                  <dl className="grid grid-cols-1 gap-2 text-sm">
                    <div className="flex justify-between">
                      <dt className="text-gray-500">{t('Country')}:</dt>
                      <dd className="font-medium">{selectedCountry.name}</dd>
                    </div>
                    <div className="flex justify-between">
                      <dt className="text-gray-500">{t('Currency')}:</dt>
                      <dd className="font-medium">{selectedCountry.currency}</dd>
                    </div>
                    <div className="flex justify-between">
                      <dt className="text-gray-500">{t('Timezone')}:</dt>
                      <dd className="font-medium">{selectedCountry.timezone}</dd>
                    </div>
                  </dl>
                </div>

                <div className="bg-gray-50 rounded-lg p-4">
                  <h3 className="font-semibold text-gray-900 mb-3">{t('Company Information')}</h3>
                  <dl className="grid grid-cols-1 gap-2 text-sm">
                    <div className="flex justify-between">
                      <dt className="text-gray-500">{t('Company Name')}:</dt>
                      <dd className="font-medium">{formData.name}</dd>
                    </div>
                    {formData.legalName && (
                      <div className="flex justify-between">
                        <dt className="text-gray-500">{t('Legal Name')}:</dt>
                        <dd className="font-medium">{formData.legalName}</dd>
                      </div>
                    )}
                    {formData.taxId && (
                      <div className="flex justify-between">
                        <dt className="text-gray-500">{t('Tax ID')}:</dt>
                        <dd className="font-medium">{formData.taxId}</dd>
                      </div>
                    )}
                  </dl>
                </div>

                {(formData.email || formData.phone || formData.addressStreet) && (
                  <div className="bg-gray-50 rounded-lg p-4">
                    <h3 className="font-semibold text-gray-900 mb-3">{t('Contact Information')}</h3>
                    <dl className="grid grid-cols-1 gap-2 text-sm">
                      {formData.email && (
                        <div className="flex justify-between">
                          <dt className="text-gray-500">{t('Email')}:</dt>
                          <dd className="font-medium">{formData.email}</dd>
                        </div>
                      )}
                      {formData.phone && (
                        <div className="flex justify-between">
                          <dt className="text-gray-500">{t('Phone')}:</dt>
                          <dd className="font-medium">{formData.phone}</dd>
                        </div>
                      )}
                      {formData.addressStreet && (
                        <div>
                          <dt className="text-gray-500 mb-1">{t('Address')}:</dt>
                          <dd className="font-medium">
                            {formData.addressStreet}
                            {(formData.addressCity || formData.addressPostalCode) && (
                              <>
                                <br />
                                {[formData.addressPostalCode, formData.addressCity].filter(Boolean).join(' ')}
                              </>
                            )}
                          </dd>
                        </div>
                      )}
                    </dl>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>

        {/* Navigation */}
        <div className="flex justify-between">
          <button
            type="button"
            onClick={prevStep}
            disabled={stepIndex === 0 || mutation.isPending}
            className="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <ArrowLeft className="w-4 h-4 me-2" />
            {t('previous')}
          </button>

          {currentStep === 'review' ? (
            <button
              type="button"
              onClick={handleSubmit}
              disabled={!canProceed() || mutation.isPending}
              className="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {mutation.isPending && <Loader2 className="w-4 h-4 me-2 animate-spin" />}
              {mutation.isPending ? t('creating') : t('Create Company')}
            </button>
          ) : (
            <button
              type="button"
              onClick={nextStep}
              disabled={!canProceed()}
              className="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {t('next')}
              <ArrowRight className="w-4 h-4 ms-2" />
            </button>
          )}
        </div>
      </div>
    </div>
  )
}
