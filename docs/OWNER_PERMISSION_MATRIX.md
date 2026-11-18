# Owner Management permission matrix

The source PIMS specification explicitly limits owner name/number/profession/address/legal note/expected price/relationship manager to authorized personnel and final negotiated price to management.

Suggested mapping (configure in the application's existing RBAC system):

| Action | Ability | Super Admin | Director/Management | Inventory Manager | Sales Manager | Sales Executive | Inventory Hunter | Legal Officer | CRM Executive |
|---|---|---:|---:|---:|---:|---:|---:|---:|---:|
| View owner directory | owners.view | ✓ | ✓ | ✓ | ✓ | Optional | ✓ | Optional | ✓ |
| Create owner | owners.create | ✓ | ✓ | ✓ | — | — | ✓ | — | — |
| Update owner | owners.update | ✓ | ✓ | ✓ | Assigned only | — | Assigned only | — | — |
| Archive owner | owners.delete | ✓ | ✓ | — | — | — | — | — | — |
| View restricted contact fields | owners.view_contact | ✓ | ✓ | ✓ | Explicit | No by default | Explicit | Explicit | Explicit |
| View final negotiated price | owners.view_final_price | ✓ | ✓ | No by default | No | No | No | No | No |
| Request contact access | owners.request_contact | Optional | Optional | ✓ | ✓ | ✓ | — | — | ✓ |
| Review contact requests | owners.review_contact_requests | ✓ | ✓ | ✓ | ✓ | — | — | — | — |

"Assigned only" must be enforced by the host application's permission/policy layer; this overlay provides global ability checks and does not infer an assigned-owner rule.
