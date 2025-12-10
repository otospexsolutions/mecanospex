import { useCallback, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Upload, FileText, X, AlertCircle } from 'lucide-react'
import { cn } from '@/lib/utils'

interface FileUploadProps {
  onFileSelect: (file: File) => void
  accept?: string
  maxSize?: number // in bytes
  disabled?: boolean
}

export function FileUpload({
  onFileSelect,
  accept = '.csv,.txt',
  maxSize = 10 * 1024 * 1024, // 10MB default
  disabled = false,
}: FileUploadProps) {
  const { t } = useTranslation('import')
  const [isDragOver, setIsDragOver] = useState(false)
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [error, setError] = useState<string | null>(null)

  const validateFile = useCallback(
    (file: File): string | null => {
      // Check file type
      const acceptedTypes = accept.split(',').map((t) => t.trim())
      const fileExtension = `.${file.name.split('.').pop()?.toLowerCase()}`
      if (!acceptedTypes.some((t) => fileExtension === t || file.type.includes(t.replace('.', '')))) {
        return t('errors.invalidFileType', { types: accept })
      }

      // Check file size
      if (file.size > maxSize) {
        const maxSizeMB = Math.round(maxSize / (1024 * 1024))
        return t('errors.fileTooLarge', { maxSize: `${String(maxSizeMB)}MB` })
      }

      return null
    },
    [accept, maxSize, t]
  )

  const handleFile = useCallback(
    (file: File) => {
      const validationError = validateFile(file)
      if (validationError) {
        setError(validationError)
        setSelectedFile(null)
        return
      }

      setError(null)
      setSelectedFile(file)
      onFileSelect(file)
    },
    [validateFile, onFileSelect]
  )

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsDragOver(true)
  }, [])

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setIsDragOver(false)
  }, [])

  const handleDrop = useCallback(
    (e: React.DragEvent) => {
      e.preventDefault()
      e.stopPropagation()
      setIsDragOver(false)

      if (disabled) return

      const file = e.dataTransfer.files[0]
      if (file) {
        handleFile(file)
      }
    },
    [disabled, handleFile]
  )

  const handleInputChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const file = e.target.files?.[0]
      if (file) {
        handleFile(file)
      }
    },
    [handleFile]
  )

  const clearFile = useCallback(() => {
    setSelectedFile(null)
    setError(null)
  }, [])

  const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) return `${String(bytes)} B`
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
  }

  return (
    <div className="space-y-4">
      {/* Drop Zone */}
      <div
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        className={cn(
          'relative rounded-lg border-2 border-dashed p-8 text-center transition-colors',
          isDragOver && !disabled && 'border-blue-400 bg-blue-50',
          !isDragOver && !disabled && 'border-gray-300 hover:border-gray-400',
          disabled && 'cursor-not-allowed border-gray-200 bg-gray-50',
          error && 'border-red-300 bg-red-50'
        )}
      >
        <input
          type="file"
          accept={accept}
          onChange={handleInputChange}
          disabled={disabled}
          className="absolute inset-0 cursor-pointer opacity-0 disabled:cursor-not-allowed"
        />

        {selectedFile ? (
          <div className="flex items-center justify-center gap-3">
            <FileText className="h-10 w-10 text-blue-500" />
            <div className="text-start">
              <p className="font-medium text-gray-900">{selectedFile.name}</p>
              <p className="text-sm text-gray-500">
                {formatFileSize(selectedFile.size)}
              </p>
            </div>
            <button
              type="button"
              onClick={(e) => {
                e.stopPropagation()
                clearFile()
              }}
              className="ms-4 rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
            >
              <X className="h-5 w-5" />
            </button>
          </div>
        ) : (
          <>
            <Upload
              className={cn(
                'mx-auto h-12 w-12',
                error ? 'text-red-400' : 'text-gray-400'
              )}
            />
            <p className="mt-2 text-sm font-medium text-gray-900">
              {t('upload.dragDrop')}
            </p>
            <p className="mt-1 text-sm text-gray-500">
              {t('upload.or')}{' '}
              <span className="text-blue-600 underline">{t('upload.browse')}</span>
            </p>
            <p className="mt-2 text-xs text-gray-400">
              {t('upload.formats', { formats: accept })} -{' '}
              {t('upload.maxSize', { size: `${String(Math.round(maxSize / (1024 * 1024)))}MB` })}
            </p>
          </>
        )}
      </div>

      {/* Error Message */}
      {error && (
        <div className="flex items-center gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
          <AlertCircle className="h-4 w-4 flex-shrink-0" />
          {error}
        </div>
      )}
    </div>
  )
}
