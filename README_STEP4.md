# PIMS Step 4 — Verification & Approval Workflow

This is an **additive overlay** for the Laravel + Vue PIMS project represented by the Step 3 ZIP. It follows the same conventions:

- Backend: API Controller → FormRequest → Eloquent Model → API Resource.
- Frontend: Vue page → Pinia store → API module → existing shared Axios client (`@/api/http`).
- Authentication: existing Sanctum session and existing authenticated `v1` API group.
- UI: existing `AppLayout.vue` and Tailwind utility classes.

## What Step 4 implements

1. Submit a registered property to the verification queue.
2. Legal/verification officer reviews ownership, property documents, property information and pricing using a checklist.
3. Officer marks the verification `verified`, `changes_requested` or `rejected` and must add notes.
4. Inventory manager/authorized approver can approve or reject a property only after verification is `verified`.
5. Every submission, review and decision is appended to `property_verifications`; earlier actions are not overwritten.
6. Workflow queue, filters, property review panel and activity history in Vue.
7. API authorization is checked using Laravel permission abilities (`$user->can(...)`).

The package does not replace Step 1, Step 2 or Step 3 files. It contains one small relationship append snippet for the existing `Property` model and append-only route snippets.

## Important integration before running

### 1. Add the Property relationship

Open `app/Models/Property.php`. Keep all existing code, including `images()`. Follow `app/Models/Property.php.append.txt` to add the `HasMany` import and this relationship inside the existing class:

```php
public function verifications(): HasMany
{
    return $this->hasMany(PropertyVerification::class)->orderBy('id');
}
```

`PropertyVerification` is in the same `App\Models` namespace, so no extra model import is needed.

### 2. Add API routes

Open `routes/api.php`. Add the import and five routes from `routes/api.php.append.txt` **inside the existing `auth:sanctum` group nested under the `v1` prefix**. Do not replace the file or create a second `v1` group.

### 3. Add Vue route

Open `resources/js/router/index.js`. Add the route object from `resources/js/router/index.js.append.txt` to the existing routes array. Keep the current router guards and routes.

### 4. Configure permissions in your existing RBAC system

The actual role/permission package and role names are not included in the Step 3 ZIP, so this package does not guess or overwrite your user/role tables. The controller requires these Laravel permission abilities:

| Permission ability | Intended access from the PIMS document |
|---|---|
| `properties.workflow.view` | Super Admin, Director/Management, Inventory Manager, Sales Manager, Legal/Verification Officer |
| `properties.workflow.submit` | Inventory Manager, Inventory Hunter |
| `properties.workflow.verify` | Legal / Verification Officer |
| `properties.workflow.approve` | Inventory Manager, Director / Management, Super Admin |

Assign these abilities to your existing roles using the project's current RBAC mechanism. If you use **spatie/laravel-permission**, create/assign permissions with that package. Example permission creation (run in a seeder or Tinker; adapt role names to your actual database):

```php
use Spatie\Permission\Models\Permission;

foreach ([
    'properties.workflow.view',
    'properties.workflow.submit',
    'properties.workflow.verify',
    'properties.workflow.approve',
] as $name) {
    Permission::findOrCreate($name, 'web');
}

// Example only: assign to role records that already exist in your application.
// Role::findByName('Legal / Verification Officer')->givePermissionTo('properties.workflow.verify');
```

If your project does not use Spatie, define equivalent Laravel Gate abilities or ensure your User model's `can()` method resolves these permissions. Do not remove the authorization checks in the controller.

### 5. Install and migrate

Copy the contents of the extracted `pims-step-4-verification-approval` folder into your Laravel project root, preserving paths. Back up the project first.

```powershell
php artisan optimize:clear
php artisan migrate
npm run build
```

No new Composer or npm package is required. The package uses the existing `http` API client and Pinia setup from Step 3.

## Windows PowerShell merge instructions

If the ZIP is in Downloads:

```powershell
$zip = "$env:USERPROFILE\Downloads\pims-step-4-verification-approval.zip"
$tmp = Join-Path $env:TEMP 'pims-step-4-verification-approval'
Expand-Archive -Path $zip -DestinationPath $tmp -Force
Copy-Item (Join-Path $tmp 'pims-step-4-verification-approval\*') (Get-Location).Path -Recurse -Force
```

Then manually merge the three append snippets described above. Do not copy the append snippets over the complete existing files.

## Frontend navigation

After adding the Vue route, open:

`/property-workflow`

Add a navigation link in your existing sidebar if desired:

```vue
<RouterLink :to="{ name: 'properties.workflow' }">Verification & Approval</RouterLink>
```

Only users with the relevant backend permission can use each action. Hiding sidebar links based on role/permission is a frontend convenience; backend authorization remains authoritative.

## Workflow rules

- New property → `not_submitted`.
- Submit → verification `submitted`.
- Verification officer → `verified`, `changes_requested` or `rejected`.
- Manager decision is available only when latest verification is `verified`.
- Manager decision → approval `approved` or `rejected`.
- Every action creates a new history row with actor, time, stage, status, checklist and notes.
- An already approved property cannot be submitted again through this workflow endpoint.

## API endpoints

All endpoints require the existing authenticated Sanctum session and the corresponding permission ability.

| Method | Endpoint | Ability | Purpose |
|---|---|---|---|
| GET | `/api/v1/property-workflow` | `properties.workflow.view` | Paginated workflow queue; optional `?status=approved` |
| GET | `/api/v1/property-workflow/{property}` | `properties.workflow.view` | Property workflow details and audit history |
| POST | `/api/v1/properties/{property}/workflow/submit` | `properties.workflow.submit` | Submit for verification; optional notes |
| POST | `/api/v1/properties/{property}/workflow/review` | `properties.workflow.verify` | Verification result, notes and checklist |
| POST | `/api/v1/properties/{property}/workflow/decision` | `properties.workflow.approve` | Manager approval/rejection |

Review payload example:

```json
{
  "status": "verified",
  "notes": "Ownership details and submitted papers reviewed.",
  "checklist": {
    "ownership": true,
    "documents": true,
    "property_information": true,
    "pricing": true
  }
}
```

Decision payload example:

```json
{
  "status": "approved",
  "notes": "Approved for internal inventory circulation."
}
```

## Database

Creates `property_verifications` with a foreign key to `properties` and `users`. It stores each action as a separate immutable history row rather than overwriting the previous review. Deleting a property cascades its workflow rows; deleting a user who performed an action is restricted to preserve the audit trail.

## Scope and security notes

- This workflow tracks document review; it does **not** add a new legal-document upload/download module. The Step 3 ZIP only implements public-disk property photos. Do not store deeds, NIDs, owner identity files or confidential legal documents in the Step 3 photo uploader. Those require a separate private-storage document module with document-level authorization, as required by the PIMS specification.
- This package does not expose owner phone numbers, owner identity information, final negotiated prices or commission fields.
- `inventory_status` is not automatically changed on approval. The PIMS document says approval enables internal circulation, but the exact inventory status transition and public/internal visibility rules should be confirmed against the existing Step 2 schema before modifying it. The approval state is available separately as `approval_status`.
- The queue intentionally lists properties and shows `not_submitted` where no workflow event exists. This lets authorized staff submit a property from the review screen.

## Manual test checklist

1. Sign in as a user with `properties.workflow.view` and open `/property-workflow`.
2. Confirm registered properties appear with `not_submitted` where appropriate.
3. Select a property and submit it using a user with `properties.workflow.submit`.
4. Sign in as Legal / Verification Officer and confirm the property appears as `submitted`.
5. Attempt `Mark verified` with any checklist item unchecked; the button should remain disabled.
6. Tick all checklist items, enter notes and mark verified.
7. Sign in as Inventory Manager / Director and approve with decision notes.
8. Confirm approval status and the complete activity history.
9. Test `changes_requested` and `rejected` paths using a test property.
10. Confirm a user without the relevant permission receives HTTP 403.

## Compatibility note

This overlay is based on the Step 3 ZIP's Laravel + Vue file layout and API client. The full Step 1/2 project, exact role implementation, database schema and sidebar component were not part of that ZIP. Therefore, merge the relationship and route snippets and map the four permission abilities to your actual role records before testing.
