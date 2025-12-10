import { useState } from 'react'
import { Link, useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, Edit, Trash2, Car, Calendar, Gauge, Fuel, Settings } from 'lucide-react'
import { api, apiDelete } from '../../lib/api'
import { ConfirmDialog } from '../../components/ui/ConfirmDialog'

interface Vehicle {
  id: string
  tenant_id: string
  partner_id: string | null
  license_plate: string
  brand: string
  model: string
  year: number | null
  color: string | null
  mileage: number | null
  vin: string | null
  engine_code: string | null
  fuel_type: string | null
  transmission: string | null
  notes: string | null
  created_at: string
  updated_at: string
}

interface VehicleResponse {
  data: Vehicle
}

export function VehicleDetailPage() {
  const { t } = useTranslation(['vehicles', 'common'])
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [showDeleteDialog, setShowDeleteDialog] = useState(false)

  const { data, isLoading, error } = useQuery({
    queryKey: ['vehicle', id],
    queryFn: async () => {
      if (!id) throw new Error('No vehicle ID')
      const response = await api.get<VehicleResponse>(`/vehicles/${id}`)
      return response.data
    },
    enabled: Boolean(id),
  })

  const deleteMutation = useMutation({
    mutationFn: async () => {
      if (!id) throw new Error('No vehicle ID')
      return apiDelete(`/vehicles/${id}`)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['vehicles'] })
      void navigate('/vehicles')
    },
  })

  const vehicleId = id ?? ''

  const handleDelete = () => {
    setShowDeleteDialog(true)
  }

  const confirmDelete = () => {
    void deleteMutation.mutateAsync()
    setShowDeleteDialog(false)
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-gray-500">{t('common:status.loading')}</div>
      </div>
    )
  }

  if (error || !data?.data) {
    return (
      <div className="space-y-6">
        <Link
          to="/vehicles"
          className="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
        >
          <ArrowLeft className="h-4 w-4" />
          {t('common:actions.back')}
        </Link>
        <div className="rounded-lg bg-red-50 p-4 text-red-700">
          {t('messages.notFound')}
        </div>
      </div>
    )
  }

  const vehicle = data.data

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link
            to="/vehicles"
            className="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
          >
            <ArrowLeft className="h-4 w-4" />
            {t('common:actions.back')}
          </Link>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              {vehicle.brand} {vehicle.model}
            </h1>
            <p className="text-gray-500 font-mono">{vehicle.license_plate}</p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <Link
            to={`/vehicles/${vehicleId}/edit`}
            className="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            <Edit className="h-4 w-4" />
            {t('common:actions.edit')}
          </Link>
          <button
            onClick={handleDelete}
            disabled={deleteMutation.isPending}
            className="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:opacity-50"
          >
            <Trash2 className="h-4 w-4" />
            {t('common:actions.delete')}
          </button>
        </div>
      </div>

      {/* Vehicle Details */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Main Info */}
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Car className="h-5 w-5 text-gray-400" />
            {t('sections.vehicleInfo')}
          </h2>
          <dl className="grid grid-cols-2 gap-4">
            <div>
              <dt className="text-sm font-medium text-gray-500">{t('brand')}</dt>
              <dd className="mt-1 text-sm text-gray-900">{vehicle.brand}</dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">{t('model')}</dt>
              <dd className="mt-1 text-sm text-gray-900">{vehicle.model}</dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">{t('licensePlate')}</dt>
              <dd className="mt-1">
                <span className="inline-flex rounded-md bg-gray-100 px-2 py-1 text-sm font-mono font-medium text-gray-800">
                  {vehicle.license_plate}
                </span>
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">{t('vin')}</dt>
              <dd className="mt-1 text-sm text-gray-900 font-mono">
                {vehicle.vin ?? '-'}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">{t('color')}</dt>
              <dd className="mt-1 text-sm text-gray-900">{vehicle.color ?? '-'}</dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">{t('year')}</dt>
              <dd className="mt-1 text-sm text-gray-900 flex items-center gap-1">
                <Calendar className="h-4 w-4 text-gray-400" />
                {vehicle.year ?? '-'}
              </dd>
            </div>
          </dl>
        </div>

        {/* Technical Info */}
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Settings className="h-5 w-5 text-gray-400" />
            {t('sections.technicalDetails')}
          </h2>
          <dl className="grid grid-cols-2 gap-4">
            <div>
              <dt className="text-sm font-medium text-gray-500">{t('mileage')}</dt>
              <dd className="mt-1 text-sm text-gray-900 flex items-center gap-1">
                <Gauge className="h-4 w-4 text-gray-400" />
                {vehicle.mileage != null ? `${vehicle.mileage.toLocaleString()} km` : '-'}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">{t('fuelType')}</dt>
              <dd className="mt-1 text-sm text-gray-900 flex items-center gap-1">
                <Fuel className="h-4 w-4 text-gray-400" />
                {vehicle.fuel_type ?? '-'}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">{t('transmission')}</dt>
              <dd className="mt-1 text-sm text-gray-900">{vehicle.transmission ?? '-'}</dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">{t('engineCode')}</dt>
              <dd className="mt-1 text-sm text-gray-900 font-mono">{vehicle.engine_code ?? '-'}</dd>
            </div>
          </dl>
        </div>
      </div>

      {/* Notes */}
      {vehicle.notes && (
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">{t('notes')}</h2>
          <p className="text-sm text-gray-700 whitespace-pre-wrap">{vehicle.notes}</p>
        </div>
      )}

      {/* Metadata */}
      <div className="text-sm text-gray-500">
        <p>{t('created')}: {new Date(vehicle.created_at).toLocaleString()}</p>
        <p>{t('updated')}: {new Date(vehicle.updated_at).toLocaleString()}</p>
      </div>

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        isOpen={showDeleteDialog}
        onClose={() => { setShowDeleteDialog(false) }}
        onConfirm={confirmDelete}
        title={t('messages.deleteVehicle')}
        message={t('messages.confirmDeleteVehicle', { brand: vehicle.brand, model: vehicle.model, licensePlate: vehicle.license_plate })}
        confirmText={t('common:actions.delete')}
        variant="danger"
        isLoading={deleteMutation.isPending}
      />
    </div>
  )
}
