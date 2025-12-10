# AutoERP Component Library

This directory contains the atomic design system components for AutoERP.

## Architecture

We follow **Atomic Design Principles**:

- **Atoms** (`/atoms`): Smallest building blocks (Button, Input, Select, FormField, etc.)
- **Molecules** (`/molecules`): Simple combinations of atoms (SearchInput, Tabs, etc.)
- **Organisms** (`/organisms`): Complex UI sections (Modal, Sidebar, TopBar, etc.)
- **Templates** (in `/features`): Page-level layouts with business logic

## Design Tokens

All components use design tokens from `lib/designTokens.ts` for consistent styling.

### Benefits

- **Consistency**: All inputs, buttons, modals use the same colors, spacing, and styles
- **Maintainability**: Change one token, update everywhere
- **Themability**: Easy to add dark mode or custom themes
- **Type Safety**: TypeScript ensures you use valid tokens

### Usage

```tsx
import { tokens } from '@/lib/designTokens'
import { cn } from '@/lib/utils'

// Use composed tokens (recommended)
<input className={tokens.input.base} />
<button className={tokens.button.primary} />

// Or combine with custom classes
<input className={cn(tokens.input.base, 'w-full')} />
```

## Form Components

### Input

Text input primitive with error states.

```tsx
import { Input } from '@/components/atoms'

// Basic
<Input placeholder="Enter name" />

// With React Hook Form
<Input {...register('email')} error={!!errors.email} />

// With custom styling
<Input className="w-full" type="email" />
```

### Select

Dropdown/select primitive with error states.

```tsx
import { Select } from '@/components/atoms'

// Basic
<Select>
  <option value="">Choose...</option>
  <option value="1">Option 1</option>
</Select>

// With React Hook Form
<Select {...register('type')} error={!!errors.type}>
  <option value="">Select type</option>
  <option value="customer">Customer</option>
</Select>
```

### Textarea

Multi-line text input primitive.

```tsx
import { Textarea } from '@/components/atoms'

// Basic
<Textarea rows={4} placeholder="Enter notes..." />

// With React Hook Form
<Textarea {...register('notes')} />
```

### FormField

Composition component that wraps inputs with labels, errors, and helper text.

```tsx
import { FormField, Input } from '@/components/atoms'

// Basic usage
<FormField label="Name" htmlFor="name" required>
  <Input id="name" {...register('name')} />
</FormField>

// With error message
<FormField
  label="Email"
  htmlFor="email"
  required
  error={errors.email?.message}
>
  <Input id="email" type="email" {...register('email')} />
</FormField>

// With helper text
<FormField
  label="Password"
  htmlFor="password"
  helperText="Must be at least 8 characters"
>
  <Input id="password" type="password" {...register('password')} />
</FormField>
```

### Complete Form Example

```tsx
import { useForm } from 'react-hook-form'
import { FormField, Input, Select, Textarea, Button } from '@/components/atoms'

export function MyForm() {
  const { register, handleSubmit, formState: { errors } } = useForm()

  const onSubmit = (data) => {
    console.log(data)
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <FormField
        label="Name"
        htmlFor="name"
        required
        error={errors.name?.message}
      >
        <Input
          id="name"
          {...register('name', { required: 'Name is required' })}
        />
      </FormField>

      <FormField
        label="Type"
        htmlFor="type"
        required
        error={errors.type?.message}
      >
        <Select
          id="type"
          {...register('type', { required: 'Type is required' })}
        >
          <option value="">Select type</option>
          <option value="customer">Customer</option>
          <option value="supplier">Supplier</option>
        </Select>
      </FormField>

      <FormField label="Notes" htmlFor="notes">
        <Textarea id="notes" rows={4} {...register('notes')} />
      </FormField>

      <div className="flex gap-2">
        <Button type="button" variant="secondary">Cancel</Button>
        <Button type="submit" variant="primary">Save</Button>
      </div>
    </form>
  )
}
```

## Modal Component

Flexible modal/dialog component with composition support.

### Simple Usage

```tsx
import { Modal, Button } from '@/components'
import { useState } from 'react'

export function MyComponent() {
  const [isOpen, setIsOpen] = useState(false)

  return (
    <>
      <Button onClick={() => setIsOpen(true)}>Open Modal</Button>

      <Modal
        isOpen={isOpen}
        onClose={() => setIsOpen(false)}
        title="Add Partner"
      >
        <form className="space-y-4">
          <Input placeholder="Name" />
          <div className="flex gap-2">
            <Button variant="secondary" onClick={() => setIsOpen(false)}>
              Cancel
            </Button>
            <Button type="submit">Save</Button>
          </div>
        </form>
      </Modal>
    </>
  )
}
```

### Composition Usage

For more control, use the subcomponents:

```tsx
import { Modal, ModalHeader, ModalContent, ModalFooter, Button } from '@/components'

<Modal isOpen={isOpen} onClose={onClose} size="lg">
  <ModalHeader title="Add Partner" onClose={onClose} />

  <ModalContent>
    <form>
      <FormField label="Name" required>
        <Input />
      </FormField>
      <FormField label="Email">
        <Input type="email" />
      </FormField>
    </form>
  </ModalContent>

  <ModalFooter>
    <Button variant="secondary" onClick={onClose}>Cancel</Button>
    <Button type="submit" variant="primary">Save</Button>
  </ModalFooter>
</Modal>
```

## Best Practices

### 1. Always Use Design Tokens

❌ **Don't**:
```tsx
<input className="border-gray-300 focus:border-blue-500 rounded-lg px-3 py-2" />
```

✅ **Do**:
```tsx
import { Input } from '@/components/atoms'
<Input />
```

### 2. Compose with FormField

❌ **Don't** duplicate label/error logic:
```tsx
<label>Name</label>
<Input {...register('name')} />
{errors.name && <p className="text-red-600">{errors.name.message}</p>}
```

✅ **Do** use FormField:
```tsx
<FormField label="Name" error={errors.name?.message}>
  <Input {...register('name')} />
</FormField>
```

### 3. Use forwardRef-Compatible Components

All our primitives (Input, Select, Textarea) are `forwardRef` compatible, so they work seamlessly with React Hook Form:

```tsx
const { register } = useForm()

// ✅ This works!
<Input {...register('name')} />
<Select {...register('type')} />
<Textarea {...register('notes')} />
```

### 4. Extend with className

All components accept `className` prop for additional styling:

```tsx
<Input className="w-full" />
<Select className="max-w-xs" />
<FormField className="col-span-2">
  <Input />
</FormField>
```

### 5. Context-Aware Modals

When creating modals that adapt to context (e.g., "Add Customer" vs "Add Supplier"), follow the PartnerForm pattern:

```tsx
interface AddPartnerModalProps {
  partnerType?: 'customer' | 'supplier'
  // ...
}

export function AddPartnerModal({ partnerType, ...props }: AddPartnerModalProps) {
  const location = useLocation()

  // Detect context from prop or URL
  const isCustomerContext = partnerType === 'customer' || location.pathname.includes('/sales')
  const defaultType = isCustomerContext ? 'customer' : 'supplier'

  // Use context-aware defaults
  const { register } = useForm({
    defaultValues: { type: defaultType }
  })

  // ...
}
```

## Adding New Components

When creating new components:

1. **Place in correct layer**:
   - Atoms: Single-purpose, no dependencies on other components
   - Molecules: Combinations of atoms
   - Organisms: Complex UI sections

2. **Use design tokens**: Import and use `tokens` from `lib/designTokens.ts`

3. **Export from index**: Add to `/atoms/index.ts`, `/molecules/index.ts`, or `/organisms/index.ts`

4. **Document with JSDoc**: Add usage examples in component file

5. **Support forwardRef**: If it wraps a form element, use `forwardRef`

```tsx
import { forwardRef } from 'react'
import { tokens } from '@/lib/designTokens'

export const MyInput = forwardRef<HTMLInputElement, MyInputProps>(
  (props, ref) => {
    return <input ref={ref} className={tokens.input.base} {...props} />
  }
)

MyInput.displayName = 'MyInput'
```

## Questions?

See `CLAUDE.md` in the project root for architectural principles and conventions.
