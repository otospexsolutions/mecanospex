import { forwardRef, type TextareaHTMLAttributes } from 'react'
import { tokens } from '../../../lib/designTokens'
import { cn } from '../../../lib/utils'

export interface TextareaProps
  extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  /**
   * Error state for validation feedback
   */
  error?: boolean
}

/**
 * Textarea - Multi-line text input primitive component
 *
 * A flexible textarea component that follows the atomic design pattern.
 * Uses design tokens for consistent styling across the application.
 *
 * @example
 * ```tsx
 * // Basic usage
 * <Textarea placeholder="Enter notes" rows={4} />
 *
 * // With React Hook Form
 * <Textarea {...register('notes')} error={!!errors.notes} />
 *
 * // With custom className
 * <Textarea className="min-h-32" />
 * ```
 */
export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ className, ...props }, ref) => {
    // Compose classes: base + custom
    const classes = cn(tokens.textarea.base, className)

    return <textarea ref={ref} className={classes} {...props} />
  }
)

Textarea.displayName = 'Textarea'
