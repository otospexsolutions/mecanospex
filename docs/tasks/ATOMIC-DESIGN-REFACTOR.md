Move feature-specific components out of generic components/ into their feature folder.

#### Phase 8: Update All Imports (1-2 hours)

After moving files, update imports everywhere:
```bash
# Find all imports of moved components
grep -r "from.*components/Button" apps/web/src/
grep -r "from.*components/TopBar" apps/web/src/
# etc.
```

Update to new paths:
```tsx
// Before
import { Button } from '@/components/Button';

// After
import { Button } from '@/components/atoms/Button';
// OR use barrel export
import { Button } from '@/components/atoms';
```

#### Phase 9: Verify and Clean Up (30 min)
```bash
# Build to catch any broken imports
cd apps/web && pnpm build

# Run tests
cd apps/web && pnpm test

# Check for unused files
# Remove any empty directories or orphaned files
```

### COMPONENT INVENTORY CHECKLIST

Mark each component as you refactor it:

#### Atoms
- [ ] Button
- [ ] Input
- [ ] Label
- [ ] Select
- [ ] Checkbox
- [ ] Radio
- [ ] Switch
- [ ] Textarea
- [ ] Badge
- [ ] Avatar
- [ ] Spinner/Loader
- [ ] Icon
- [ ] Tooltip
- [ ] Skeleton

#### Molecules
- [ ] FormField
- [ ] SearchBox
- [ ] Card
- [ ] Alert
- [ ] Modal/Dialog
- [ ] Dropdown/DropdownMenu
- [ ] Tabs
- [ ] Breadcrumb
- [ ] EmptyState
- [ ] ConfirmDialog
- [ ] Toast/Notification
- [ ] Pagination

#### Organisms
- [ ] TopBar
- [ ] Sidebar
- [ ] CompanySelector
- [ ] LocationSelector
- [ ] DataTable
- [ ] PageHeader
- [ ] NavigationMenu
- [ ] UserMenu
- [ ] NotificationBell

#### Templates
- [ ] DashboardLayout
- [ ] AuthLayout
- [ ] SettingsLayout

### HANDLING SHADCN/UI

If the project uses shadcn/ui components (in components/ui/):

Option A: Keep as-is and import from atoms
```tsx
// components/atoms/Button/Button.tsx
export { Button } from '@/components/ui/button';
```

Option B: Move shadcn components into atoms structure
```bash
mv apps/web/src/components/ui/button.tsx apps/web/src/components/atoms/Button/Button.tsx
```

Choose Option A for faster refactor - just re-export shadcn components from atomic structure.

### DEFINITION OF DONE

1. All components organized into atoms/molecules/organisms/templates
2. All feature-specific components in features/*/components/
3. Barrel exports (index.ts) in each directory
4. All imports updated to new paths
5. `pnpm build` passes with no errors
6. `pnpm test` passes (if tests exist)
7. Application runs and all pages render correctly
8. Document final structure in docs/ATOMIC-DESIGN.md

### PROGRESS TRACKING

Create: docs/tasks/ATOMIC-DESIGN-PROGRESS.md
```markdown
# Atomic Design Refactor Progress

Started: [TIMESTAMP]

## Phase 1: Setup - [ ]
## Phase 2: Categorize - [ ]
## Phase 3: Atoms - [ ]
## Phase 4: Molecules - [ ]
## Phase 5: Organisms - [ ]
## Phase 6: Templates - [ ]
## Phase 7: Features - [ ]
## Phase 8: Imports - [ ]
## Phase 9: Verify - [ ]

Completed: [TIMESTAMP]
```

### START NOW

1. First, analyze current structure and create categorization
2. Create directory structure
3. Refactor atoms first (smallest, no dependencies)
4. Work up through molecules, organisms, templates
5. Commit after each phase
6. Run build after each major change

Begin with Phase 1 and 2: Setup and categorization.
