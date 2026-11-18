# Step 4 — Advanced Property Search & Smart Inventory Search

Installable overlay for the existing Laravel + Vue + Pinia PIMS project. This package follows the Step 2 architecture:

Backend: Controller → Service Interface → Service → Repository Interface → Repository → Eloquent Model  
Frontend: Vue Page → Pinia Store → API Module → existing Axios `http` client

It does not replace the existing project, authentication, layout, HTTP client, or property registration screens.

## Included

- Combined advanced property filters (all active filters are applied with AND logic).
- Developer, floor, handover year, building height, parking, gas, paper status, legal verification, market type.
- Asking-price and price-per-square-foot ranges.
- Owner-direct and recently-added filters. Add the owner-direct checkbox to the existing registration form using the included patch snippet.
- Paginated property cards using the existing `PropertyResource`, plus a property detail page.
- Save, list, run and delete frequently used searches.
- Multi-property comparison (client-side selection).
- Shortlist (browser local storage).
- Shareable client-safe property summary using the Web Share API with clipboard fallback.
- Print selected property profile / browser Save as PDF.
- Owner-contact access request endpoint protected by a Laravel policy.

## Important schema note

The Step 2 `properties` table does not include an owner-direct flag. This package adds `is_owner_direct` through a separate migration. It also adds `saved_property_searches` and `owner_contact_access_requests`.

Price per square foot is calculated from `asking_price / apartment_size_sqft`. Records without a positive apartment size are excluded when a price-per-square-foot range is supplied.

## Installation

1. Extract this ZIP into the Laravel project root and merge folders. Do not replace the whole project.
2. Apply the model additions shown in `app/Models/Property.php.append.txt`, the resource addition shown in `app/Http/Resources/PropertyResource.php.append.txt`, and the request/form additions shown in the two `*.append.txt` files for `StorePropertyRequest` and `PropertyCreatePage.vue`.
3. Add repository bindings shown in `app/Providers/RepositoryServiceProvider.php.append.txt` to the existing provider's `$bindings`.
4. Add the API route snippet from `routes/api.php.append.txt` inside your existing `/api/v1` group. Keep the existing authentication middleware and routes.
5. Add the Vue route from `resources/js/router/index.js.append.txt` to the existing routes array.
6. Add the sidebar/module entry from `resources/js/config/modules.js.append.txt` if your module navigation is configured there.
7. Run:
   ```bash
   php artisan optimize:clear
   php artisan migrate
   npm run dev
   ```

## Owner contact authorization

The endpoint intentionally does not return owner details. It records an access request only after Laravel authorizes the current user against the `viewOwnerContact` policy ability.

Define a policy ability for `Property` in your existing `AuthServiceProvider` / policy registration. Example policy method:

```php
public function viewOwnerContact(User $user, Property $property): bool
{
    return $user->hasPermissionTo('owner_contact.view');
}
```

Adapt this example to your actual permission package and role structure. Do not grant this permission to ordinary sales users by default. The request is logged in `owner_contact_access_requests` for audit review.

## Saved search ownership

Saved searches are scoped to the authenticated user. A user can only list or delete their own saved searches.

## API endpoints

All endpoints require the existing `auth:sanctum` middleware:

- `GET /api/v1/property-search` — combined filter search
- `GET /api/v1/saved-property-searches`
- `POST /api/v1/saved-property-searches`
- `DELETE /api/v1/saved-property-searches/{savedSearch}`
- `POST /api/v1/properties/{property}/owner-contact-requests`

## Supported query parameters

`q`, `developer`, `area`, `market_type`, `floor`, `handover_year_from`, `handover_year_to`, `building_height_min`, `building_height_max`, `parking_min`, `gas_connection`, `paper_status`, `legal_verification`, `asking_price_min`, `asking_price_max`, `price_per_sqft_min`, `price_per_sqft_max`, `owner_direct`, `recent_days`, `per_page`, `page`.

Empty filters are ignored. Multiple supplied filters are combined.

## UI notes

The search page uses the existing `AppLayout.vue`, Tailwind utility classes, brand classes, Pinia and `@/api/http`. The page route is `properties.search`. The result card displays only fields already included in the general `PropertyResource`; owner details are never displayed.
