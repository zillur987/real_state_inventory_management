# PIMS Step 6 — Client Matching & CRM Integration

This is an additive module for the existing Laravel 13 + Vue 3 + Pinia + Sanctum PIMS project. It follows the supplied PIMS roadmap's Phase 6: **Client matching and CRM integration**.

## What this step adds

- Client registration and searchable client list.
- Client property requirements: category, primary/secondary market, location, budget, apartment size, bedrooms, bathrooms, parking, facing and handover year.
- Property matching against the existing `properties` inventory.
- Matching is intentionally restricted to properties whose inventory status is `available`, whose latest verification record is `verified`, and whose latest approval record is `approved`.
- Match reasons and a simple criterion coverage score.
- Client shortlist records, without copying property data into the CRM.
- CRM activity timeline for calls, meetings, emails, property visits, notes and follow-ups.
- Role-based access using the project's existing `UserRole` enum and Laravel Gates. No Spatie package, Role model, role table or permission table is introduced.

The module does not expose property owner identity/contact details, legal notes, final negotiated price or commissions.

## Existing project assumptions checked against the supplied Step 3–5 packages

- Property fields used: `category`, `market_type`, `division`, `district`, `area`, `inventory_status`, `asking_price`, `apartment_size_sqft`, `bedrooms`, `bathrooms`, `parking_spaces`, `facing`, `handover_year`.
- Verification history: `property_verifications` with `property_id`, `stage`, `status`, `id`.
- User roles: `Admin`, `Manager`, `SalesAgent`, `LegalOfficer`.
- Existing Axios client: `resources/js/api/http.js`, imported as `./http` from API modules.
- Existing Vue aliases: `@/api`, `@/stores`, `@/pages`.

**Prerequisites:** Step 3 property tables and Step 4 `property_verifications` migration must already be installed. The properties table must use the field names above. This package does not alter those earlier migrations.

## Folder structure

```text
pims-step-6-client-matching/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/Clients/ClientMatchingController.php
│   │   └── Requests/Clients/
│   │       ├── StoreClientRequest.php
│   │       ├── UpdateClientRequest.php
│   │       ├── SaveClientRequirementRequest.php
│   │       └── StoreClientActivityRequest.php
│   └── Providers/AppServiceProvider.gates.append.txt
├── database/migrations/
│   ├── 2026_11_21_000001_create_pims_clients_table.php
│   ├── 2026_11_21_000002_create_client_requirements_table.php
│   ├── 2026_11_21_000003_create_client_property_matches_table.php
│   └── 2026_11_21_000004_create_client_activities_table.php
├── resources/js/
│   ├── api/clientMatchingApi.js
│   ├── stores/clientMatching.js
│   ├── pages/ClientMatchingPage.vue
│   └── router/index.js.append.txt
└── routes/api.php.append.txt
```

## Installation on Windows PowerShell

1. Back up your project.
2. Extract the ZIP. Copy the contents inside `pims-step-6-client-matching` into the Laravel project root (`C:\xampp\htdocs\real_state_inventory`). Do not copy the outer folder as a nested folder.
3. Copy the new `app/Http`, `database/migrations`, and `resources/js` files into the matching existing directories.
4. Merge API routes from `routes/api.php.append.txt` into the existing `routes/api.php`.
5. Add the Vue route from `resources/js/router/index.js.append.txt` to the existing routes array. Preserve the existing authentication guard.
6. Add the Gate definitions as described below.

### Gate integration — important

Open `app/Providers/AppServiceProvider.php`.

- Add these imports at the top of the file only if they are not already present:

```php
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
```

- Merge the four `Gate::define(...)` statements from `app/Providers/AppServiceProvider.gates.append.txt` **inside the existing `boot()` method**.
- Do not replace the provider. Keep all Step 4 workflow and Step 5 dashboard Gates already installed.
- If a Gate name already exists, keep one definition only.

### Run migrations and build

```powershell
php artisan optimize:clear
php artisan migrate
php artisan route:list --path=clients
npm run build
```

No `composer install` is required. No third-party package is required.

## Frontend route

Open:

`http://localhost:5173/clients/matching`

Use your actual Vite development port if it differs. The route requires the existing authenticated session.

## API routes

All routes are inside the existing `v1` + `auth:sanctum` groups.

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/clients` | Search/list clients |
| POST | `/api/v1/clients` | Create client |
| GET | `/api/v1/clients/{client}` | Client details, requirement, shortlist and recent activities |
| PUT | `/api/v1/clients/{client}` | Update client |
| PUT | `/api/v1/clients/{client}/requirements` | Save/update buying requirements |
| GET | `/api/v1/clients/{client}/matches` | Generate eligible property matches |
| POST | `/api/v1/clients/{client}/matches/{property}/shortlist` | Add eligible property to shortlist |
| DELETE | `/api/v1/clients/{client}/matches/{property}/shortlist` | Remove shortlist entry |
| GET | `/api/v1/clients/{client}/activities` | Activity timeline |
| POST | `/api/v1/clients/{client}/activities` | Record interaction/follow-up |

## Authorization for your actual enum roles

| Ability | Admin | Manager | SalesAgent | LegalOfficer |
|---|:---:|:---:|:---:|:---:|
| `clients.view` | Yes | Yes | Yes | No |
| `clients.create` | Yes | Yes | Yes | No |
| `clients.update` | Yes | Yes | Yes | No |
| `clients.match` | Yes | Yes | Yes | No |

Every Gate also denies inactive users (`is_active = false`). The client phone/email and CRM timeline are therefore not exposed to Legal Officer or inactive accounts through these endpoints.

## Matching rules

The matching endpoint treats saved criteria as filters. It only returns properties that meet all supplied filters and satisfy all three inventory conditions:

1. `inventory_status = available`.
2. The latest `verification` stage record has `status = verified`.
3. The latest `approval` stage record has `status = approved`.

Bedrooms, bathrooms and parking are minimums. Handover year is a minimum year. Budget and apartment size use inclusive minimum/maximum bounds. If no criteria are supplied, the API asks the user to save at least one criterion instead of returning the entire inventory.

The match score is a simple coverage score based on the number of saved criteria met. It is not an AI prediction or a guarantee of suitability. Matching is limited to the first 50 eligible properties ordered by asking price.

## Smoke test

1. Sign in as an active Admin, Manager or SalesAgent.
2. Open `/clients/matching` and add a test client.
3. Select the client and save at least one requirement (for example district and budget range).
4. Click **Find Matching Properties**.
5. Confirm only available + latest verified + latest approved properties appear.
6. Add a match to the shortlist.
7. Open CRM Activities and record a call or follow-up.
8. Sign in as LegalOfficer and confirm the client module is denied by design.

## Notes / boundaries

- This is an internal client CRM and matching module, not a public client portal.
- The current client record supports one active requirement profile per client. If you need multiple concurrent searches per client later, the requirement table can be changed to one-to-many in a separate migration.
- The package stores a client phone/email because the CRM requires client contact details; it does not expose property owner contact details.
- `assigned_to` references the existing `users` table. This initial page does not yet provide an assignment selector; assignment can be set through the API or extended in the UI.
- Property sharing, PDF generation, client visit feedback linked to a specific property, reservation/closing pipeline, and external CRM integrations are not implemented in this phase package. They require explicit workflow/data definitions and can be added as follow-on enhancements.
- If your actual Step 2 property migration uses different enum strings or column names, align the requirement options and matching filters with that schema before production use.
