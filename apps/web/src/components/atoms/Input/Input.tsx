import { forwardRef, type InputHTMLAttributes } from 'react'
import { tokens } from '../../../lib/designTokens'
import { cn } from '../../../lib/utils'

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  /**
   * Error state for validation feedback
   */
  error?: boolean
  /**
   * Success state for validation feedback
   */
  success?: boolean
}

/**
 * Input - Text input primitive component
 *
 * A flexible text input component that follows the atomic design pattern.
 * Uses design tokens for consistent styling across the application.
 *
 * @example
 * ```tsx
 * // Basic usage
 * <Input placeholder="Enter your name" />
 *
 * // With React Hook Form
 * <Input {...register('email')} error={!!errors.email} />
 *
 * // With custom className
 * <Input className="w-full" type="email" />
 * ```
 */
export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ className, error, success, type = 'text', ...props }, ref) => {
    // Compose classes: base + error/success state + custom
    const classes = cn(
      tokens.input.base,
      error && tokens.input.error,
      success && tokens.input.success,
      className
    )

    return <input ref={ref} type={type} className={classes} {...props} />
  }
)

Input.displayName = 'Input'
