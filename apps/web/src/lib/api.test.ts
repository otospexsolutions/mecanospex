import { describe, it, expect } from 'vitest'
import { isApiError, getErrorMessage } from './api'

describe('API utilities', () => {
  describe('isApiError', () => {
    it('returns false for non-axios errors', () => {
      expect(isApiError(new Error('test'))).toBe(false)
    })

    it('returns false for null/undefined', () => {
      expect(isApiError(null)).toBe(false)
      expect(isApiError(undefined)).toBe(false)
    })
  })

  describe('getErrorMessage', () => {
    it('returns message for standard Error', () => {
      const error = new Error('Test error message')
      expect(getErrorMessage(error)).toBe('Test error message')
    })

    it('returns default message for unknown error types', () => {
      expect(getErrorMessage('string error')).toBe('An unexpected error occurred')
      expect(getErrorMessage(123)).toBe('An unexpected error occurred')
      expect(getErrorMessage(null)).toBe('An unexpected error occurred')
    })
  })
})
