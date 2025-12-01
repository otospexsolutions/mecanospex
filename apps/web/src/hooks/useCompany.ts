import { useCompanyStore, type Company } from '../stores/companyStore'

/**
 * Hook for accessing company context
 *
 * Provides access to current company, list of available companies,
 * and company switching functionality.
 */
export function useCompany() {
  const currentCompanyId = useCompanyStore((state) => state.currentCompanyId)
  const companies = useCompanyStore((state) => state.companies)
  const isLoading = useCompanyStore((state) => state.isLoading)
  const setCurrentCompany = useCompanyStore((state) => state.setCurrentCompany)
  const getCurrentCompany = useCompanyStore((state) => state.getCurrentCompany)

  const currentCompany = getCurrentCompany()

  return {
    /** Current selected company */
    currentCompany,
    /** Current company ID */
    currentCompanyId,
    /** List of all companies the user has access to */
    companies,
    /** Whether companies are being loaded */
    isLoading,
    /** Whether user has multiple companies */
    hasMultipleCompanies: companies.length > 1,
    /** Switch to a different company */
    switchCompany: setCurrentCompany,
  }
}

/**
 * Hook to require company context
 * Throws if no company is selected
 */
export function useRequireCompany(): Company {
  const { currentCompany } = useCompany()

  if (!currentCompany) {
    throw new Error('No company selected. User must select a company to continue.')
  }

  return currentCompany
}
