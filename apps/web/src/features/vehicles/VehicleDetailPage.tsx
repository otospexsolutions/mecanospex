import { Link, useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { ArrowLeft, Edit, Trash2, Car, Calendar, Gauge, Fuel, Settings } from 'lucide-react'
import { api, apiDelete } from '../../lib/api'

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
  const { t } = useTranslation()
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

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
    if (window.confirm('Are you sure you want to delete this vehicle?')) {
      void deleteMutation.mutateAsync()
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-gray-500">{t('status.loading')}</div>
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
          {t('actions.back')}
        </Link>
        <div className="rounded-lg bg-red-50 p-4 text-red-700">
          Vehicle not found or error loading data.
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
            {t('actions.back')}
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
            {t('actions.edit')}
          </Link>
          <button
            onClick={handleDelete}
            disabled={deleteMutation.isPending}
            className="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:opacity-50"
          >
            <Trash2 className="h-4 w-4" />
            {t('actions.delete')}
          </button>
        </div>
      </div>

      {/* Vehicle Details */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Main Info */}
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Car className="h-5 w-5 text-gray-400" />
            Vehicle Information
          </h2>
          <dl className="grid grid-cols-2 gap-4">
            <div>
              <dt className="text-sm font-medium text-gray-500">Brand</dt>
              <dd className="mt-1 text-sm text-gray-900">{vehicle.brand}</dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Model</dt>
              <dd className="mt-1 text-sm text-gray-900">{vehicle.model}</dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">License Plate</dt>
              <dd className="mt-1">
                <span className="inline-flex rounded-md bg-gray-100 px-2 py-1 text-sm font-mono font-medium text-gray-800">
                  {vehicle.license_plate}
                </span>
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">VIN</dt>
              <dd className="mt-1 text-sm text-gray-900 font-mono">
                {vehicle.vin ?? '-'}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Color</dt>
              <dd className="mt-1 text-sm text-gray-900">{vehicle.color ?? '-'}</dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Year</dt>
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
            Technical Details
          </h2>
          <dl className="grid grid-cols-2 gap-4">
            <div>
              <dt className="text-sm font-medium text-gray-500">Mileage</dt>
              <dd className="mt-1 text-sm text-gray-900 flex items-center gap-1">
                <Gauge className="h-4 w-4 text-gray-400" />
                {vehicle.mileage != null ? `${vehicle.mileage.toLocaleString()} km` : '-'}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Fuel Type</dt>
              <dd className="mt-1 text-sm text-gray-900 flex items-center gap-1">
                <Fuel className="h-4 w-4 text-gray-400" />
                {vehicle.fuel_type ?? '-'}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Transmission</dt>
              <dd className="mt-1 text-sm text-gray-900">{vehicle.transmission ?? '-'}</dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Engine Code</dt>
              <dd className="mt-1 text-sm text-gray-900 font-mono">{vehicle.engine_code ?? '-'}</dd>
            </div>
          </dl>
        </div>
      </div>

      {/* Notes */}
      {vehicle.notes && (
        <div className="rounded-lg border border-gray-200 bg-white p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Notes</h2>
          <p className="text-sm text-gray-700 whitespace-pre-wrap">{vehicle.notes}</p>
        </div>
      )}

      {/* Metadata */}
      <div className="text-sm text-gray-500">
        <p>Created: {new Date(vehicle.created_at).toLocaleString()}</p>
        <p>Updated: {new Date(vehicle.updated_at).toLocaleString()}</p>
      </div>
    </div>
  )
}
