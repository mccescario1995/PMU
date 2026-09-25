# Stakeholder Schema Refactoring Plan

## Schema Changes

| Field | Old Schema | New Schema | Action |
|-------|-----------|-----------|--------|
| id | ✓ | ✓ | Keep |
| stakeholder_type_id | ✓ | ✓ | Keep |
| name | ✓ | ✓ | Keep |
| email | ✓ | ✗ | **Remove** |
| type | ✓ | ✗ | **Remove** |
| contact_no | ✓ | ✗ | **Remove** |
| address | ✓ | ✗ | **Remove** |
| official_receipt | ✗ | ✓ | **Add** (exactly 7 digits) |
| status | ✓ | ✓ | Keep |
| created_at | ✓ | ✓ | Keep |
| updated_at | ✓ | ✓ | Keep |
| deleted_at | ✓ | ✓ | Keep |

---

## Backend Changes (PMUAPI)

### 1. Migration
- Create new migration to:
  - Add `official_receipt` column (string, 7 chars, unique, nullable)
  - Remove `type`, `contact_no`, `email`, `address` columns

### 2. Stakeholder Model (`app/Models/Stakeholder.php`)
- Update `$fillable`: remove `type`, `contact_no`, `email`, `address`; add `official_receipt`

### 3. StakeholderResource (`app/Http/Resources/StakeholderResource.php`)
- Remove: `type`, `contact_no`, `email`, `address`
- Add: `official_receipt`

### 4. StakeholderController (`app/Http/Controllers/Api/V1/StakeholderController.php`)
- Update validation rules in `store()` and `update()`:
  - Remove: `type`, `contact_no`, `email`, `address`
  - Add: `official_receipt` (required, exactly 7 digits, unique)
- Update search query: remove `contact_no` from search
- Update audit log fields

### 5. DropdownController (`app/Http/Controllers/Api/V1/DropdownController.php`)
- Update search query: remove `contact_no` from search

---

## Frontend Changes (PMUUI)

### 1. `PMUUI/app/pages/stakeholders/index.vue` (Main page with modal)
**Changes:**
- Remove form fields: `email`, `contact_no`, `address`
- Remove validation for removed fields
- Add `official_receipt` field to form
- Add validation for `official_receipt` (required, exactly 7 digits, numbers only)
- Update `Stakeholder` type definition (remove `type`, `contact_no`, add `official_receipt`)
- Update table columns: remove Contact column, add Official Receipt column
- Remove `typeColor` and type badge logic (no more `type` field)
- Update type filter buttons: change from `type=buyer/broker/renter` to `stakeholder_type_id=X` (or keep using type filter but map to stakeholder_type_id)
- **Add Export button** to download filtered table as Excel

### 2. `PMUUI/app/pages/stakeholders/create.vue`
**Changes:**
- Remove form fields: `email`, `contact_no`, `address`
- Add `official_receipt` field with validation (7 digits)
- Update template

### 3. `PMUUI/app/pages/stakeholders/edit/[id].vue`
**Changes:**
- Remove form fields: `email`, `contact_no`, `address`
- Add `official_receipt` field
- Update `Object.assign` in onMounted
- Update template

### 4. `PMUUI/app/pages/stakeholders/[id].vue` (Detail view)
**Changes:**
- Remove display of: Type, Contact, Email, Address
- Add display of: Official Receipt
- Update `stakeholder` ref initial state

### 5. `PMUUI/app/pages/stakeholders/buyers.vue`
**Changes:**
- Update `Buyer` type: remove `contact_no`, `email`, add `official_receipt`
- Update table columns: remove Contact, Email; add Official Receipt
- Filter query: change from `type=buyer` to `stakeholder_type_id=X` (need to find correct ID)

### 6. `PMUUI/app/pages/stakeholders/brokers.vue`
**Changes:**
- Update `Broker` type: remove `contact_no`, `email`, add `official_receipt`
- Update table columns: remove Contact, Email; add Official Receipt
- Filter query: change from `type=broker` to `stakeholder_type_id=X`

### 7. `PMUUI/app/pages/cms/stakeholders/index.vue`
**Changes:**
- Update `Stakeholder` type: remove `type`, `contact_no`, add `official_receipt`
- Update table columns: remove Contact; add Official Receipt
- Remove `typeColor` and type badge logic
- **Add Export button**

### 8. `PMUUI/app/pages/cms/stakeholders/create.vue`
**Changes:**
- Remove form fields: `type`, `email`, `contact_no`, `address`
- Add `official_receipt` field
- Remove Type dropdown (uses `type` field)
- Keep Stakeholder Type dropdown (uses `stakeholder_type_id`)
- Update template

### 9. `PMUUI/app/pages/cms/stakeholders/edit/[id].vue`
**Changes:**
- Remove form fields: `type`, `email`, `contact_no`, `address`
- Add `official_receipt` field
- Update `Object.assign` in onMounted
- Remove Type dropdown
- Keep Stakeholder Type dropdown
- Update template

---

## Excel Export Implementation

### Option 1: Use SheetJS (xlsx) - Recommended
- Install: `npm install xlsx`
- Create composable `useExportToExcel.ts`
- Use in index.vue and cms/stakeholders/index.vue

### Option 2: Native CSV export
- Simpler, no dependencies
- But user asked for Excel file

---

## Priority Order

1. **Backend**: Migration, Model, Resource, Controller, DropdownController
2. **Frontend Main**: index.vue (most complex), create.vue, edit/[id].vue, [id].vue
3. **Frontend CMS**: cms/stakeholders/index.vue, create.vue, edit/[id].vue
4. **Frontend Filtered**: buyers.vue, brokers.vue
5. **Export functionality**: Add to index.vue and cms/stakeholders/index.vue

---

## Validation Rules for official_receipt

- **Required**: Yes
- **Format**: Exactly 7 digits (numbers only)
- **Regex**: `/^\d{7}$/`
- **Unique**: Yes (in database)

---

## API Filter Notes

The user mentioned "filters right now are working and mapped in the API correctly" - the API already supports:
- `type` query param (buyer/broker/renter) 
- `stakeholder_type_id` query param
- `search` query param (searches name, contact_no)

After refactor:
- `type` query param will be removed from API
- Frontend filters should use `stakeholder_type_id` instead
- Search will only search `name` (and optionally `official_receipt`)

---

## Testing Checklist

- [ ] Backend migration runs successfully
- [ ] Create stakeholder with official_receipt (7 digits)
- [ ] Edit stakeholder - official_receipt persists
- [ ] Validation rejects non-7-digit official_receipt
- [ ] Validation rejects duplicate official_receipt
- [ ] List pages show official_receipt column
- [ ] Detail view shows official_receipt
- [ ] Filter by stakeholder_type_id works
- [ ] Search by name works
- [ ] Export to Excel downloads filtered data
- [ ] No console errors for removed fields