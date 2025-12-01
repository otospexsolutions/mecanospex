import { useLocationStore, type Location } from '../stores/locationStore'

/**
 * Hook for accessing location context
 *
 * Provides access to current location, list of available locations,
 * and location switching functionality.
 */
export function useLocation() {
  const currentLocationId = useLocationStore((state) => state.currentLocationId)
  const locations = useLocationStore((state) => state.locations)
  const isLoading = useLocationStore((state) => state.isLoading)
  const setCurrentLocation = useLocationStore((state) => state.setCurrentLocation)
  const getCurrentLocation = useLocationStore((state) => state.getCurrentLocation)

  const currentLocation = getCurrentLocation()

  return {
    /** Current selected location */
    currentLocation,
    /** Current location ID */
    currentLocationId,
    /** List of all locations for the current company */
    locations,
    /** Whether locations are being loaded */
    isLoading,
    /** Whether company has multiple locations */
    hasMultipleLocations: locations.length > 1,
    /** Switch to a different location */
    switchLocation: setCurrentLocation,
  }
}

/**
 * Hook to require location context
 * Throws if no location is selected
 */
export function useRequireLocation(): Location {
  const { currentLocation } = useLocation()

  if (!currentLocation) {
    throw new Error('No location selected. A location must be selected for inventory operations.')
  }

  return currentLocation
}
