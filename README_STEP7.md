# PIMS Step 7 — Mobile App, Maps & Automation

Additive package for the same Laravel API + Vue 3 + Pinia + Sanctum architecture used by Step 6. It does not replace existing routes, providers, stores, or application bootstrapping.

## Included

- Responsive Vue property intelligence workspace for mobile browser/tablet/desktop.
- Authenticated map-points API, location coordinate update endpoint, and OpenStreetMap preview/link integration.
- Inventory expiry and 30/60/90+ day aging alert generation via a Laravel scheduled command.
- Potential duplicate detection using normalized property name, district, area, category and apartment size. Results are review-only; nothing is auto-merged/deleted.
- Asking-price update endpoint with immutable old/new price history and actor attribution.
- Automation overview, pending alerts and alert acknowledgement APIs.
- Laravel Gates based on the existing `UserRole` enum; no role/permission tables or third-party packages.

## Important scope notes

1. The attached PIMS roadmap calls Step 7 “Mobile App, Maps & Automation” and lists location mapping, expiry alerts, duplicate detection, inventory aging, price history, owner relationship follow-ups and API integration. This package provides a responsive mobile web workspace and mobile-ready authenticated APIs; it is not a compiled native Android/iOS app. Native apps can consume the same `/api/v1` endpoints later.
2. Owner contact details are deliberately not returned by the map/automation APIs. Follow-up alerts are generic operational reminders; automated SMS/WhatsApp/email delivery is not included because no provider credentials or messaging rules were supplied.
3. Geocoding is not performed automatically. Authorized staff must save latitude/longitude; the map uses OpenStreetMap embed and outbound links.
4. Duplicate matching is a candidate heuristic, not proof of duplication. Review each group before changing records.
5. Property price history is recorded for price changes made through this Step 7 endpoint. Existing code paths that update `properties.asking_price` directly should be routed through this service/endpoint or separately instrumented if every historical change must be captured.
6. `inventory_expires_at` is a new optional field. Set it when an inventory record is collected/reconfirmed; this package does not assume a validity period.

## Assumptions carried from Step 6

- Laravel 13, PHP compatible with the existing project, Vue 3, Pinia and Sanctum.
- Existing `properties` table fields include `id`, `property_code`, `name`, `category`, `district`, `area`, `inventory_status`, `asking_price`, `apartment_size_sqft`, `created_at`, `updated_at`.
- Existing roles are `Admin`, `Manager`, `SalesAgent`, and `LegalOfficer` in `App\Enums\UserRole`; `User::hasRole()` and `is_active` exist.
- Existing Axios instance is `resources/js/api/http.js`, exported as `{ http }`; aliases `@/api`, `@/stores`, `@/pages` are configured.
- Existing API routes use `/api/v1` and `auth:sanctum`.

If the actual role enum or fields differ, align the Gate definitions/queries with the project's source before deploying.

## Install (Windows PowerShell)

1. Back up the project and extract this ZIP.
2. Copy the contents inside `pims-step-7-mobile-maps-automation` into the Laravel project root, merging folders. Do not copy the outer directory as a nested folder.
3. Merge `routes/api.php.append.txt` into the existing `routes/api.php` inside the existing `v1` + `auth:sanctum` group. Add its controller import once.
4. Merge `app/Providers/AppServiceProvider.gates.append.txt` inside the existing `boot()` method. Add imports only if missing. Do not replace the provider or remove earlier Step 4–6 Gates.
5. Merge `routes/console.php.append.txt` into the existing `routes/console.php`. Keep one `Schedule` import.
6. Add the router object from `resources/js/router/index.js.append.txt` to the existing routes array. Adapt the permission meta key to your existing router convention if needed.
7. Ensure the main application layout/router displays the page link for users with `automation.view`.
8. Run:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan pims:process-property-automation
php artisan route:list --path=automation
php artisan route:list --path=properties
npm run build
```

Install the map dependency once with `npm install leaflet`. The map uses OpenStreetMap tiles; internet access is needed for map tiles.

## Scheduler setup

For production, configure Laravel's scheduler to execute every minute using your hosting scheduler/Windows Task Scheduler. The command itself runs daily at 08:00 through `routes/console.php` and uses `withoutOverlapping()`.

For local Windows development, run `php artisan schedule:work` in a separate PowerShell terminal while testing scheduled automation.

## API endpoints (all authenticated)

| Method | Endpoint | Ability | Purpose |
|---|---|---|---|
| GET | `/api/v1/automation/overview` | `automation.view` | KPI counts |
| GET | `/api/v1/automation/alerts` | `automation.view` | Pending alerts |
| POST | `/api/v1/automation/alerts/{alert}/acknowledge` | `automation.manage` | Acknowledge alert |
| GET | `/api/v1/automation/duplicates` | `automation.duplicates` | Candidate duplicate groups |
| GET | `/api/v1/properties/map-points` | `automation.maps` | Mapped available inventory |
| PUT | `/api/v1/properties/{property}/location` | `automation.maps` | Save coordinates |
| PUT | `/api/v1/properties/{property}/asking-price` | `automation.price-history` | Update asking price and write history |
| GET | `/api/v1/properties/{property}/price-history` | `automation.price-history` | Paginated price changes |

## Add location and expiry fields to property form

The migration adds `latitude`, `longitude`, `inventory_expires_at`, and `availability_confirmed_at`. Add coordinate inputs and an expiry date control to the existing property create/edit FormRequest and UI if users should maintain them there. The provided `PUT /properties/{id}/location` endpoint is available for a map-picker UI or mobile app.

## Mobile app integration

The responsive page is at `/automation/property-intelligence`. A native client should use the same Sanctum-authenticated API conventions as the existing frontend. For native mobile token authentication, confirm the project's Sanctum setup and intended token lifecycle before enabling personal access tokens; do not expose confidential owner data in mobile responses by default.

## Verification checklist

- Run migrations after Step 3 property tables and Step 4 workflow tables are installed.
- Confirm the UserRole enum contains the roles used by the Gate snippet.
- Save coordinates for an available property and call `GET /api/v1/properties/map-points`.
- Set `inventory_expires_at` to within 7 days, run `php artisan pims:process-property-automation`, and confirm an alert appears.
- Run the command again on the same day and confirm it does not duplicate the same expiry alert.
- Update a property's asking price through the API and verify `property_price_histories` stores old/new price and changed_by.
- Review duplicate candidates manually; verify no property is changed by the scan.
- Sign in as roles without the corresponding Gate and confirm endpoints return 403.
- Test the Vue page at narrow mobile widths and run `npm run build`.
