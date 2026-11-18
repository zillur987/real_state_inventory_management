# PIMS — Owner Management Module

Overlay for the existing Laravel + Vue 3 + Pinia PIMS project. It follows the supplied Step 2 structure:

Controller → Service Interface → Service → Repository Interface → Repository → Eloquent Model

Frontend: Vue pages → Pinia store → API module → existing shared Axios client (`@/api/http`).

## Scope grounded in the PIMS document

The source document describes Owner Management as an "Owner profile and contact database". The owner fields explicitly listed in the property registration form are:
- Owner name
- Owner number
- Owner profession
- Property address in detail
- Legal status note
- Owner's expected price
- Final negotiated price (management only)
- Assigned relationship manager

The source also requires restricted owner information, hiding contact details from ordinary sales users unless permission is granted, logging who views/edits sensitive information, and allowing owner contact requests through authorized access.

This module implements those fields and controls. Owner documents, owner portal, SMS/email campaigns, and payment collection are not included because they are not specified as Owner Management requirements in the source document.

## Install

1. Extract this ZIP into the Laravel project root and merge folders. Do not replace the project.
2. Add the bindings from `app/Providers/RepositoryServiceProvider.php.append.txt` to the existing provider's `$bindings`.
3. Add API imports/routes from `routes/api.php.append.txt` inside the existing `/api/v1` route group.
4. Add Vue route objects from `resources/js/router/index.js.append.txt` to the existing routes array.
5. Add the navigation item from `resources/js/config/modules.js.append.txt` to your existing modules configuration.
6. Add the `owner_id` relationship patch to `app/Models/Property.php` as shown in `app/Models/Property.php.append.txt`.
7. Run:
   ```bash
   php artisan optimize:clear
   php artisan migrate
   ```
8. Run the existing Laravel and Vite servers.

## Permission integration — REQUIRED

This package deliberately fails closed. It does not guess your role/permission package or grant access to all authenticated users.

Register these abilities in your existing RBAC/permission system and assign them to the correct roles:

| Ability | Suggested roles |
|---|---|
| `owners.view` | Super Admin, Director/Management, Inventory Manager, Sales Manager, authorized CRM staff |
| `owners.create` | Super Admin, Director/Management, Inventory Manager, Inventory Hunter |
| `owners.update` | Super Admin, Director/Management, Inventory Manager, assigned authorized staff |
| `owners.delete` | Super Admin, Director/Management |
| `owners.view_contact` | Super Admin, Director/Management, Inventory Manager, Sales Manager, explicitly authorized staff |
| `owners.view_final_price` | Super Admin, Director/Management only |
| `owners.request_contact` | Sales Manager, Sales Executive, CRM Executive |
| `owners.review_contact_requests` | Super Admin, Director/Management, Sales Manager, Inventory Manager |

All API requests also require the existing `auth:sanctum` middleware. The app's existing authorization/Gate implementation must recognize the abilities above. If it does not, the API returns 403 (intentionally).

## API

All endpoints are under `/api/v1` and require Sanctum:

- `GET /owners` — searchable, paginated owner list
- `POST /owners` — create owner
- `GET /owners/{owner}` — owner profile
- `PUT/PATCH /owners/{owner}` — update owner
- `DELETE /owners/{owner}` — soft delete (permission restricted)
- `POST /owners/{owner}/contact-requests` — request access to contact details
- `GET /owner-contact-requests` — requests visible to requester/reviewer
- `PATCH /owner-contact-requests/{contactRequest}/review` — approve/reject request

Sensitive fields are conditionally serialized. Viewing an owner profile with contact permission writes an audit record. Updating an owner writes an activity record. Final negotiated price is only serialized for users with `owners.view_final_price`.

## Notes

- The `properties.owner_id` column is nullable to preserve existing property records.
- Existing `Property` model is extended by a small patch; do not overwrite it.
- Owner records use soft deletes so linked inventory and audit history are retained.
- `final_negotiated_price` is management-only and is not exposed in owner list responses.
- An approved contact request grants scoped contact visibility for that owner to the requester on the owner detail endpoint. It does not grant access to other owners or final negotiated prices.
- This is an integration overlay. It has not been run against your complete application/database, so verify migration compatibility and connect the listed abilities to your RBAC system before production.
