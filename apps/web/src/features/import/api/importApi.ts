import { api, apiPost, apiGet } from '@/lib/api'
import type {
  ImportJob,
  ImportJobResponse,
  ImportJobListResponse,
  ImportErrorsResponse,
  CreateImportResponse,
  MigrationWizardOrder,
  DependencyCheck,
  ColumnMappingSuggestions,
  MigrationStatus,
  ImportType,
} from '../types'

const IMPORT_URL = '/imports'
const WIZARD_URL = '/migration-wizard'

export const importApi = {
  // Import Jobs
  list: async (): Promise<ImportJobListResponse> => {
    const response = await apiGet<ImportJobListResponse>(IMPORT_URL)
    return response
  },

  getJob: async (id: number): Promise<ImportJob> => {
    const response = await apiGet<ImportJobResponse>(`${IMPORT_URL}/${String(id)}`)
    return response.data
  },

  createJob: async (
    type: ImportType,
    file: File,
    _columnMapping?: Record<string, string>
  ): Promise<CreateImportResponse> => {
    const formData = new FormData()
    formData.append('type', type)
    formData.append('file', file)

    const response = await api.post<CreateImportResponse>(IMPORT_URL, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  getErrors: async (jobId: number): Promise<ImportErrorsResponse> => {
    const response = await apiGet<ImportErrorsResponse>(
      `${IMPORT_URL}/${String(jobId)}/errors`
    )
    return response
  },

  executeImport: async (jobId: number): Promise<ImportJob> => {
    const response = await apiPost<ImportJobResponse>(
      `${IMPORT_URL}/${String(jobId)}/execute`
    )
    return response.data
  },

  deleteJob: async (jobId: number): Promise<void> => {
    await api.delete(`${IMPORT_URL}/${String(jobId)}`)
  },

  // Migration Wizard
  getWizardOrder: async (): Promise<MigrationWizardOrder> => {
    const response = await apiGet<MigrationWizardOrder>(`${WIZARD_URL}/order`)
    return response
  },

  checkDependencies: async (type: ImportType): Promise<DependencyCheck> => {
    const response = await apiGet<{ data: DependencyCheck }>(
      `${WIZARD_URL}/dependencies/${type}`
    )
    return response.data
  },

  suggestMapping: async (
    type: ImportType,
    headers: string[]
  ): Promise<ColumnMappingSuggestions> => {
    const response = await apiPost<{ data: ColumnMappingSuggestions }>(
      `${WIZARD_URL}/suggest-mapping`,
      { type, headers }
    )
    return response.data
  },

  downloadTemplateUrl: (type: ImportType): string => {
    // Returns URL for direct download
    return `/api/v1${WIZARD_URL}/template/${type}`
  },

  getMigrationStatus: async (): Promise<MigrationStatus> => {
    const response = await apiGet<{ data: MigrationStatus }>(
      `${WIZARD_URL}/status`
    )
    return response.data
  },
}
