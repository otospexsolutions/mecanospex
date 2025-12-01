import { create } from 'zustand'
import { persist } from 'zustand/middleware'

/**
 * Company type for multi-company support
 */
export interface Company {
  id: string
  name: string
  legalName: string
  taxId: string | null
  countryCode: string
  currency: string
  locale: string
  timezone: string
}

/**
 * Company state interface
 */
interface CompanyState {
  currentCompanyId: string | null
  companies: Company[]
  isLoading: boolean
}

/**
 * Company actions interface
 */
interface CompanyActions {
  setCompanies: (companies: Company[]) => void
  setCurrentCompany: (companyId: string) => void
  setLoading: (loading: boolean) => void
  getCurrentCompany: () => Company | null
  reset: () => void
}

/**
 * Company store type
 */
type CompanyStore = CompanyState & CompanyActions

/**
 * Initial state
 */
const initialState: CompanyState = {
  currentCompanyId: null,
  companies: [],
  isLoading: true,
}

/**
 * Company store with persistence
 *
 * Stores the current company selection for multi-company users.
 * Company list is fetched from the server.
 */
export const useCompanyStore = create<CompanyStore>()(
  persist(
    (set, get) => ({
      ...initialState,

      setCompanies: (companies) => {
        const state = get()
        // If no current company selected or current company not in list,
        // default to first company
        let currentCompanyId = state.currentCompanyId
        if (!currentCompanyId || !companies.find((c) => c.id === currentCompanyId)) {
          currentCompanyId = companies.length > 0 ? companies[0].id : null
        }
        set({
          companies,
          currentCompanyId,
          isLoading: false,
        })
      },

      setCurrentCompany: (companyId) => {
        const { companies } = get()
        // Validate that the company exists in the list
        if (companies.find((c) => c.id === companyId)) {
          set({ currentCompanyId: companyId })
        }
      },

      setLoading: (isLoading) => set({ isLoading }),

      getCurrentCompany: () => {
        const { currentCompanyId, companies } = get()
        if (!currentCompanyId) return null
        return companies.find((c) => c.id === currentCompanyId) ?? null
      },

      reset: () => set(initialState),
    }),
    {
      name: 'autoerp-company',
      partialize: (state) => ({
        currentCompanyId: state.currentCompanyId,
      }),
    }
  )
)
