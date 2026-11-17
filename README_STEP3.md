# PIMS Step 3 — Property Details, Editing & Photos

This is an additive overlay for the existing Laravel + Vue PIMS project. It continues the Step 1/2 conventions:

- Backend: Controller → FormRequest → Service/Repository (existing property service is reused) → Eloquent Model.
- Frontend: Vue page → Pinia store → API module → existing shared Axios client.
- Existing Sanctum session authentication is retained.

## What this step adds

1. Property details screen with all standard inventory fields.
2. Edit screen for the standard property fields already supported by the Step 2 `StorePropertyRequest`.
3. Multiple property photo uploads (JPG/JPEG/PNG/WEBP, maximum 5 MB each, maximum 10 files per request).
4. Delete photo action.
5. Photo metadata stored in `property_images`; files stored on Laravel's `public` disk.
6. Property list now includes Details and Edit actions.

Owner phone numbers, owner identity details, final negotiated price, and other restricted data are deliberately not added to these general property endpoints. They remain a separate permission-controlled module in line with the PIMS specification.

## Install on Windows PowerShell

### Option A — ZIP is in your project root

Open PowerShell in `C:\xampp\htdocs\real_state_inventory` and run:

```powershell
Expand-Archive -Path .\pims-step-3-property-details.zip -DestinationPath . -Force
```

If you downloaded the ZIP to Downloads, use its full path instead, for example:

```powershell
Expand-Archive -Path "$env:USERPROFILE\Downloads\pims-step-3-property-details.zip" -DestinationPath . -Force
```

The archive contains the `pims-step-3-property-details` folder at its root. Copy its contents into the Laravel project root with:

```powershell
Copy-Item .\pims-step-3-property-details\* . -Recurse -Force
```

If the extracted folder is not in the project root, adjust the source path accordingly. Do not copy the outer folder itself into the Laravel root.

### Option B — Extract and copy in one PowerShell sequence

```powershell
$zip = "$env:USERPROFILE\Downloads\pims-step-3-property-details.zip"
$tmp = Join-Path $env:TEMP 'pims-step-3-property-details'
Expand-Archive -Path $zip -DestinationPath $tmp -Force
Copy-Item (Join-Path $tmp 'pims-step-3-property-details\*') (Get-Location).Path -Recurse -Force
```

Back up the current project before copying. The package intentionally includes updated versions of these existing files:

- `app/Models/Property.php` (adds the images relationship)
- `app/Http/Resources/PropertyResource.php` (adds loaded image metadata)
- `app/Http/Controllers/Api/V1/Property/PropertyController.php` (keeps existing endpoints and eager-loads images on show/update)
- `resources/js/api/propertyApi.js`
- `resources/js/stores/properties.js`
- `resources/js/pages/PropertyIndexPage.vue`

## Required manual integration (preserve existing routes)

1. Open `routes/api.php`. Add the import and two image routes from `routes/api.php.append.txt`. Put the routes **inside the existing authenticated `auth:sanctum` group**, within the existing `v1` prefix. Do not replace the whole API routes file.
2. Open `resources/js/router/index.js`. Add the two route objects from `resources/js/router/index.js.append.txt` to the existing routes array. Keep all existing routes and the existing auth guard.
3. Ensure the existing property API routes from Step 2 remain registered (`index`, `store`, `show`, `update`).

## Run migrations and publish local storage

```powershell
php artisan optimize:clear
php artisan migrate
php artisan storage:link
```

If `storage:link` reports that the link already exists, do not delete it automatically; check that `public\storage` points to `storage\app\public`.

Then build the frontend:

```powershell
npm run build
```

## Test flow

1. Sign in to PIMS.
2. Open Property Inventory.
3. Select **Details** on an existing property.
4. Select **Edit**, change a standard property field, and save.
5. In Property Details, select one or more photos and upload them.
6. Confirm the photos appear and can be opened. Test Delete on a test photo.

## API endpoints added

All endpoints require the existing authenticated Sanctum session:

- `POST /api/v1/properties/{property}/images` — multipart form-data, `images[]` files.
- `DELETE /api/v1/properties/{property}/images/{image}` — delete one image belonging to that property.

Existing Step 2 endpoints continue to be used:

- `GET /api/v1/properties/{property}`
- `PUT /api/v1/properties/{property}`

## Notes

- The update screen sends the existing Step 2 property fields; it does not change the property code.
- Images are private to authenticated application screens only in the sense that upload/delete APIs require login; the Laravel `public` disk URLs themselves are publicly addressable if someone knows the URL. Do not upload confidential legal/identity documents here. A future document module should use private storage and authorization checks.
- The existing Step 2 update FormRequest requires the same required fields as create. The edit page preloads and resubmits the full standard property form to satisfy that contract.
- If the application has a custom disk configuration, keep the disk name consistent between the controller and `config/filesystems.php`.
