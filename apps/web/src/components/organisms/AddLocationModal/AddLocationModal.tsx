import { useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import { X, Loader2 } from 'lucide-react'
import { createLocation, type CreateLocationInput, transformLocationResponse } from '../../../features/location/api'
import { useInvalidateLocations } from '../../../features/location/LocationProvider'
import { getErrorMessage } from '../../../lib/api'
import { useLocationStore } from '../../../stores/locationStore'
import type { LocationType } from '../../../stores/locationStore'

interface AddLocationModalProps {
  isOpen: boolean
  onClose: () => void
}

/**
 * Location types with labels
 */
const LOCATION_TYPES: { value: LocationType; label: string; description: string }[] = [
  { value: 'shop', label: 'Shop', description: 'Customer-facing retail location' },
  { value: 'warehouse', label: 'Warehouse', description: 'Storage and distribution center' },
  { value: 'office', label: 'Office', description: 'Administrative location' },
  { value: 'mobile', label: 'Mobile', description: 'Mobile service unit' },
]

/**
 * Modal for adding a new location
 */
export function AddLocationModal({ isOpen, onClose }: AddLocationModalProps) {
  const invalidateLocations = useInvalidateLocations()
  const setCurrentLocation = useLocationStore((state) => state.setCurrentLocation)

  const [formData, setFormData] = useState({
    name: '',
    type: 'shop' as LocationType,
    code: '',
    phone: '',
    email: '',
    addressStreet: '',
    addressCity: '',
    addressPostalCode: '',
    addressCountry: '',
    posEnabled: false,
  })

  const [error, setError] = useState<string | null>(null)

  const mutation = useMutation({
    mutationFn: async (input: CreateLocationInput) => {
      return createLocation(input)
    },
    onSuccess: (data) => {
      // Invalidate locations query to refetch the list
      void invalidateLocations()
      // Switch to the new location
      const transformed = transformLocationResponse(data)
      setCurrentLocation(transformed.id)
      // Close the modal
      onClose()
      // Reset form
      setFormData({
        name: '',
        type: 'shop',
        code: '',
        phone: '',
        email: '',
        addressStreet: '',
        addressCity: '',
        addressPostalCode: '',
        addressCountry: '',
        posEnabled: false,
      })
      setError(null)
    },
    onError: (err: unknown) => {
      setError(getErrorMessage(err))
    },
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)

    if (!formData.name.trim()) {
      setError('Location name is required')
      return
    }

    mutation.mutate({
      name: formData.name,
      type: formData.type,
      code: formData.code || undefined,
      phone: formData.phone || undefined,
      email: formData.email || undefined,
      addressStreet: formData.addressStreet || undefined,
      addressCity: formData.addressCity || undefined,
      addressPostalCode: formData.addressPostalCode || undefined,
      addressCountry: formData.addressCountry || undefined,
      posEnabled: formData.posEnabled,
    })
  }

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value, type } = e.target
    if (type === 'checkbox' && e.target instanceof HTMLInputElement) {
      const checked = e.target.checked
      setFormData((prev) => ({ ...prev, [name]: checked }))
    } else {
      setFormData((prev) => ({ ...prev, [name]: value }))
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50">
      <div className="relative mx-4 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
        {/* Header */}
        <div className="mb-6 flex items-center justify-between">
          <h2 className="text-xl font-semibold text-gray-900">Add New Location</h2>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
            aria-label="Close"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        {/* Error */}
        {error && (
          <div className="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
            {error}
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Location Type */}
          <div>
            <label htmlFor="type" className="block text-sm font-medium text-gray-700">
              Location Type <span className="text-red-500">*</span>
            </label>
            <select
              id="type"
              name="type"
              value={formData.type}
              onChange={handleChange}
              className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              {LOCATION_TYPES.map((locationType) => (
                <option key={locationType.value} value={locationType.value}>
                  {locationType.label}
                </option>
              ))}
            </select>
            <p className="mt-1 text-xs text-gray-500">
              {LOCATION_TYPES.find((t) => t.value === formData.type)?.description}
            </p>
          </div>

          {/* Location Name */}
          <div>
            <label htmlFor="name" className="block text-sm font-medium text-gray-700">
              Location Name <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="name"
              name="name"
              value={formData.name}
              onChange={handleChange}
              placeholder="Main Warehouse"
              className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              required
            />
          </div>

          {/* Location Code */}
          <div>
            <label htmlFor="code" className="block text-sm font-medium text-gray-700">
              Location Code
            </label>
            <input
              type="text"
              id="code"
              name="code"
              value={formData.code}
              onChange={handleChange}
              placeholder="WH-001 (auto-generated if empty)"
              className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            <p className="mt-1 text-xs text-gray-500">
              Leave empty to auto-generate
            </p>
          </div>

          {/* Email and Phone */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                Email
              </label>
              <input
                type="email"
                id="email"
                name="email"
                value={formData.email}
                onChange={handleChange}
                placeholder="warehouse@company.com"
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label htmlFor="phone" className="block text-sm font-medium text-gray-700">
                Phone
              </label>
              <input
                type="tel"
                id="phone"
                name="phone"
                value={formData.phone}
                onChange={handleChange}
                placeholder="+33 1 23 45 67 89"
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
          </div>

          {/* Address */}
          <div>
            <label htmlFor="addressStreet" className="block text-sm font-medium text-gray-700">
              Street Address
            </label>
            <input
              type="text"
              id="addressStreet"
              name="addressStreet"
              value={formData.addressStreet}
              onChange={handleChange}
              placeholder="123 Industrial Ave"
              className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="addressCity" className="block text-sm font-medium text-gray-700">
                City
              </label>
              <input
                type="text"
                id="addressCity"
                name="addressCity"
                value={formData.addressCity}
                onChange={handleChange}
                placeholder="Paris"
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
            <div>
              <label htmlFor="addressPostalCode" className="block text-sm font-medium text-gray-700">
                Postal Code
              </label>
              <input
                type="text"
                id="addressPostalCode"
                name="addressPostalCode"
                value={formData.addressPostalCode}
                onChange={handleChange}
                placeholder="75001"
                className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
            </div>
          </div>

          {/* POS Enabled */}
          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="posEnabled"
              name="posEnabled"
              checked={formData.posEnabled}
              onChange={handleChange}
              className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <label htmlFor="posEnabled" className="text-sm font-medium text-gray-700">
              Enable Point of Sale (POS)
            </label>
          </div>

          {/* Actions */}
          <div className="mt-6 flex justify-end gap-3">
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
              disabled={mutation.isPending}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
              disabled={mutation.isPending}
            >
              {mutation.isPending && <Loader2 className="h-4 w-4 animate-spin" />}
              Create Location
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
