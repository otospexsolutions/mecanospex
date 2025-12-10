# AutoERP Database Seeding System

## Overview

Comprehensive database seeding system for development and testing with realistic data volumes.

## Quick Start

### Fresh Database with Full Seed Data

```bash
cd apps/api
php artisan migrate:fresh --seed
```

This will create:
- ✅ 1 Demo tenant
- ✅ 1 Demo company
- ✅ 2 test users (test@example.com / password, admin@example.com / admin123)
- ✅ 10 standard payment methods (Cash, Check, Transfer, Card, etc.)
- ✅ 7 payment repositories (2 cash registers, 1 safe, 3 bank accounts, 1 PayPal)
- ✅ 23 partners (10 customers, 10 suppliers, 2 both, 1 inactive)
- ✅ 100 products (80 goods, 15 services, 5 inactive)
- ✅ 8-24 vehicles (assigned to random customers)

**Total seeding time:** ~5-10 seconds

---

## What Gets Seeded

### System Data (Shared Across Tenants)

#### Countries & Tax Rates
- `CountriesSeeder` - All world countries
- `CountryTaxRatesSeeder` - VAT/Tax rates by country

#### Subscription Plans
- `PlansSeeder` - Starter, Professional, Enterprise plans

#### Roles & Permissions
- `RolesAndPermissionsSeeder` - Modular permissions system
- `PermissionSeeder` - Granular permissions per module

#### Super Admin
- `SuperAdminSeeder` - Platform administrator account

### Tenant-Specific Data

#### Payment Methods
**Seeder:** `PaymentMethodSeeder`

Creates 10 standard payment methods:
1. **Cash** (CASH) - Immediate, no third party
2. **Check** (CHECK) - Physical, has maturity
3. **Bank Transfer** (TRANSFER) - Push payment
4. **Credit/Debit Card** (CARD) - Third party, has fees
5. **Direct Debit** (DIRECT_DEBIT) - Automatic, has maturity
6. **PayPal** (PAYPAL) - Third party, has fees
7. **Promissory Note** (LCR) - Physical, has maturity
8. **Bill of Exchange** (BILL_EXCHANGE) - Physical, has maturity
9. **Meal Voucher** (MEAL_VOUCHER) - Restricted use, has fees
10. **Cryptocurrency** (CRYPTO) - Disabled by default

#### Payment Repositories
**Seeder:** `PaymentRepositorySeeder`

Creates 7 repositories:
- **CASH-01** - Main Cash Register (€500.00)
- **CASH-02** - Workshop Cash Register (€200.00)
- **SAFE-01** - Office Safe (€5,000.00)
- **BANK-01** - BNP Paribas Current (€25,000.00)
- **BANK-02** - Crédit Agricole Business (€15,000.00)
- **BANK-03** - Société Générale Savings (€10,000.00)
- **VIRT-01** - PayPal Business (€3,500.00)

#### Partners (Customers & Suppliers)
**Factory:** `PartnerFactory`

**Generated data:**
- Realistic French company names (SARL, SAS, SA, etc.)
- Unique codes (CUST1234, SUPP5678)
- Full contact details (email, phone, mobile, fax, website)
- French tax IDs (FR + 11 digits)
- Complete addresses (street, city, postal code)
- Payment terms (0, 15, 30, 45, 60 days)
- Credit limits and discount rates
- Notes

**Distribution:**
- 10 customers
- 10 suppliers
- 2 both (customer AND supplier)
- 1 inactive

#### Products
**Factory:** `ProductFactory`

**Categories:**
- Engine Parts (filters, pumps, belts, etc.)
- Brake System (pads, discs, fluid, etc.)
- Suspension (shocks, springs, arms, etc.)
- Electrical (battery, bulbs, sensors, etc.)
- Body Parts (bumpers, doors, mirrors, etc.)
- Fluids (oils, coolant, brake fluid, etc.)
- Tires (summer, winter, all-season, performance)
- Interior (covers, mats, accessories)
- Services (oil change, brake service, alignment, etc.)

**Generated data:**
- SKU codes (PRD-XXXXXXXX for goods, SVC-XXXXXX for services)
- Category and brand
- Realistic pricing with 15-50% margin
- French VAT rates (20%, 10%, 5.5%, 0%)
- Stock levels (min/max)
- Barcode (EAN-13 for 80% of products)
- Weight and dimensions (optional)
- Units (piece, liter, set, pair, meter, service)

**Distribution:**
- 80 physical goods
- 15 services
- 5 inactive

#### Vehicles
**Factory:** `VehicleFactory`

**Brands:** Peugeot, Renault, Citroën, VW, BMW, Mercedes, Audi, Ford, Toyota, Nissan

**Generated data:**
- Realistic French license plates (both old and new formats)
  - Old: 123-ABC-45
  - New: AB-123-CD
- Brand and model combinations
- Year (2010-2024)
- Color
- VIN (VF + 14 characters, 80% of vehicles)
- Engine number (optional)
- Mileage (5,000-250,000 km)
- Fuel type (Gasoline, Diesel, Electric, Hybrid, LPG)
- Transmission (Manual, Automatic)

**Distribution:**
- 1-3 vehicles per customer
- Assigned to 8 random customers
- Total: ~8-24 vehicles

---

## Factories

### ProductFactory

```php
// Create specific product types
Product::factory()->goods()->create(['company_id' => $companyId]);
Product::factory()->service()->create(['company_id' => $companyId]);
Product::factory()->inactive()->create(['company_id' => $companyId]);

// Create multiple
Product::factory()->count(50)->goods()->create(['company_id' => $companyId]);
```

### PartnerFactory

```php
// Create specific partner types
Partner::factory()->customer()->create(['company_id' => $companyId]);
Partner::factory()->supplier()->create(['company_id' => $companyId]);
Partner::factory()->both()->create(['company_id' => $companyId]);
Partner::factory()->inactive()->create(['company_id' => $companyId]);

// Create multiple
Partner::factory()->count(20)->customer()->create(['company_id' => $companyId]);
```

### VehicleFactory

```php
// Create vehicles
Vehicle::factory()->create(['partner_id' => $partnerId]);
Vehicle::factory()->electric()->create(['partner_id' => $partnerId]);
Vehicle::factory()->hybrid()->create(['partner_id' => $partnerId]);

// Create multiple
Vehicle::factory()->count(5)->create(['partner_id' => $partnerId]);
```

---

## Customizing Seed Data

### Option 1: Modify DatabaseSeeder

Edit `database/seeders/DatabaseSeeder.php`:

```php
private function createProducts(Company $company): void
{
    // Change counts here
    Product::factory()->count(200)->goods()->create(['company_id' => $company->id]);
    Product::factory()->count(30)->service()->create(['company_id' => $company->id]);
}
```

### Option 2: Create Custom Seeder

```bash
php artisan make:seeder CustomDevelopmentSeeder
```

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        // Get demo company
        $company = \App\Modules\Company\Domain\Company::first();

        // Seed MORE data
        \App\Modules\Product\Domain\Product::factory()
            ->count(500)
            ->create(['company_id' => $company->id]);
    }
}
```

Run it:
```bash
php artisan db:seed --class=CustomDevelopmentSeeder
```

---

## Testing Workflows

### 1. Reset and Re-seed Anytime

```bash
php artisan migrate:fresh --seed
```

**Use case:** Clean slate for testing

### 2. Seed Only Specific Data

```bash
php artisan db:seed --class=PaymentMethodSeeder
php artisan db:seed --class=ProductFactory
```

**Use case:** Add more data without resetting everything

### 3. Seed Multiple Tenants

Create a custom seeder:

```php
public function run(): void
{
    for ($i = 1; $i <= 3; $i++) {
        $tenant = Tenant::create([/*...*/]);
        $company = Company::create([/*...*/]);

        // Seed data for each tenant
        Partner::factory()->count(20)->create(['company_id' => $company->id]);
        Product::factory()->count(100)->create(['company_id' => $company->id]);
    }
}
```

---

## Test Credentials

### Regular User
- **Email:** test@example.com
- **Password:** password
- **Role:** Manager (full operational access)

### Admin User
- **Email:** admin@example.com
- **Password:** admin123
- **Role:** Admin (full access)

### Super Admin
- **Email:** superadmin@autoerp.local
- **Password:** (set via SuperAdminSeeder)
- **Access:** Platform-wide administration

---

## Seeding Best Practices

### ✅ DO

- Run `migrate:fresh --seed` regularly to test with clean data
- Use factories for random, realistic data
- Use seeders for fixed, configuration data (payment methods, plans, etc.)
- Version control all seeders and factories
- Document custom seeders in this README

### ❌ DON'T

- Commit real customer data to seeders
- Hardcode production credentials
- Seed sensitive data (passwords should use Hash::make())
- Mix testing data with production data

---

## Troubleshooting

### Issue: "Class 'Product' not found"

**Solution:** Factories use full namespace. Update factory:
```php
protected $model = \App\Modules\Product\Domain\Product::class;
```

### Issue: "Duplicate entry for key 'partners_email_unique'"

**Solution:** Faker's `unique()` modifier ensures uniqueness within a single seeding run. For multiple runs:
```bash
php artisan migrate:fresh --seed
```

### Issue: Seeding is slow

**Solution:** Wrap in DB transaction:
```php
DB::transaction(function () {
    Product::factory()->count(1000)->create([/*...*/]);
});
```

---

## Maintenance

### Updating Seeders

When adding new fields to models, update corresponding factories:

1. Edit factory definition method
2. Add new field with faker data
3. Test:
   ```bash
   php artisan migrate:fresh --seed
   ```

### Adding New Entity Types

1. Create factory: `php artisan make:factory EntityFactory`
2. Define realistic fake data
3. Add to `DatabaseSeeder::run()`
4. Document in this README

---

## Summary

| Entity | Count | Type | Time |
|--------|-------|------|------|
| Tenants | 1 | Fixed | <1s |
| Companies | 1 | Fixed | <1s |
| Users | 2 | Fixed | <1s |
| Payment Methods | 10 | Fixed | <1s |
| Payment Repositories | 7 | Fixed | <1s |
| Partners | 23 | Random | ~2s |
| Products | 100 | Random | ~3s |
| Vehicles | 8-24 | Random | ~1s |
| **TOTAL** | **152-168** | **Mixed** | **~8-10s** |

---

## Questions?

Contact the development team or check Laravel documentation:
- [Database Seeding](https://laravel.com/docs/11.x/seeding)
- [Model Factories](https://laravel.com/docs/11.x/eloquent-factories)
