# PIMS Step 5 — Management Dashboard & Reports

Additive Laravel 13 + Vue 3 + Pinia package. It follows the existing project conventions:

- Backend: API Controller + Laravel query builder + existing Sanctum authentication.
- Frontend: Vue page → Pinia store → API module → existing shared `http` Axios client.
- Authorization: Laravel Gates using the existing `App\Enums\UserRole` enum and `User::hasRole()`.
- No new composer package, Role model, permission tables, or migration is required.

## Included dashboard/report data

- Inventory total and counts by `inventory_status` (available/reserved/sold).
- Primary vs secondary (`market_type`).
- Verification status based on the latest `property_verifications` record at stage `verification`.
- Approval status based on the latest `property_verifications` record at stage `approval`.
- Inventory by area, developer, and average asking price by area.
- Inventory aging from `properties.created_at` in 0–30, 31–60, 61–90, and 90+ day bands.

The provided Step 3/4 schema does not show owner portfolio, employee/collector attribution, sales conversion, or commission tables/columns. Those are deliberately returned as unavailable rather than displaying invented numbers.

## Install (Windows PowerShell)

Back up the project first. Extract this ZIP and copy the contents of the inner `pims-step-5-dashboard-reports` folder into the Laravel project root. Example if the ZIP is in Downloads:

```powershell
$zip = "$env:USERPROFILE\Downloads\pims-step-5-dashboard-reports.zip"
$tmp = Join-Path $env:TEMP 'pims-step-5-dashboard-reports'
Expand-Archive -Path $zip -DestinationPath $tmp -Force
Copy-Item (Join-Path $tmp 'pims-step-5-dashboard-reports\*') (Get-Location).Path -Recurse -Force
```

### 1. Add controller

The package adds `app/Http/Controllers/Api/V1/Dashboard/ManagementDashboardController.php`.

### 2. Add Gates without replacing existing Gates

Open `app/Providers/AppServiceProvider.php`. Add the imports if not already present:

```php
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
```

Inside the existing `boot()` method, merge the definitions from `app/Providers/AppServiceProvider.gates.append.txt`. Do not replace the file or remove Step 4 Gate definitions.

### 3. Add API routes

Merge the import and route definitions from `routes/api.php.append.txt` into your existing `routes/api.php`. Put routes inside the existing `auth:sanctum` group nested under the `v1` prefix.

### 4. Add Vue files and route

The package adds:

- `resources/js/api/managementDashboardApi.js`
- `resources/js/stores/managementDashboard.js`
- `resources/js/pages/ManagementDashboardPage.vue`

Merge the route object from `resources/js/router/index.js.append.txt` into your existing router's routes array. Keep the existing auth guard and all existing routes.

### 5. Clear caches and build

```powershell
php artisan optimize:clear
php artisan route:list --path=dashboard
npm run build
```

No migration is included because this step reads the existing `properties` and `property_verifications` tables.

## URLs

- `GET /api/v1/dashboard/summary`
- `GET /api/v1/dashboard/reports`

Optional report filters: `from=YYYY-MM-DD`, `to=YYYY-MM-DD`. Both are inclusive dates. Example:

`/api/v1/dashboard/reports?from=2026-01-01&to=2026-12-31`

## Authorization

- `Admin`, `Manager`, `SalesAgent`, and `LegalOfficer`: dashboard view.
- `Admin` and `Manager`: detailed reports.
- Inactive accounts are denied.

If your project uses a different dashboard access policy, adjust the two Gate definitions deliberately. Do not remove authorization checks to silence a 403.

## Test

1. Sign in as an active Admin or Manager.
2. Visit `/management-dashboard`.
3. Confirm the summary cards and report tables load.
4. Test a date range.
5. Sign in as Sales Agent or Legal Officer: dashboard summary should be available, while detailed reports should return 403 by design.

## Notes

- The dashboard aggregates only fields present in the Step 3/4 model/schema: `inventory_status`, `market_type`, `area`, `developer`, `asking_price`, `created_at`, and the verification history table.
- If a listed database column/table is absent in your actual Step 2 migration, Laravel will return a database error. Compare the field names with your existing migrations before deploying.
- Counts are based on current property inventory. Verification/approval counts use the latest record per property and stage.
- The dashboard does not expose owner names, owner phone numbers, final negotiated prices, or commissions.
