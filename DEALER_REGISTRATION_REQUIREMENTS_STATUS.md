# Dealer Registration Requirements - Implementation Status

## ✅ Already Implemented

1. Basic dealer profile structure
2. Materials dealt in (many-to-many)
3. Machines available
4. Locations (factory/warehouse)
5. Capacity (daily/monthly + unit)
6. Opportunity feed
7. Accept/Decline functionality
8. Session locking
9. Chat system
10. Quotations

## ❌ Missing Requirements (Need Implementation)

### 1. Per-Material Details Structure
**Required:**
- For EACH material, dealer should specify:
  - Material ID ✅
  - Mill/Brand (optional) ❌
  - Agent Type (AUTHORIZED_AGENT/DEALER) - only if mill selected ❌
  - Finishes/Grades/Coating (optional array) ❌
  - Thickness ranges per unit (GSM/MM/OUNCE/BF/MICRON) ❌

**Current Status:** Only basic many-to-many relationship exists

**Solution:** Created `dealer_material_details` table with migrations

### 2. Warehouse Option
**Required:**
- "Don't have warehouse (only bulk orders)" option ❌

**Current Status:** Locations are required

**Solution:** Added `has_warehouse` and `bulk_orders_note` fields

### 3. Thickness Matching with Tolerance
**Required:**
- Normal inquiry: GSM ±5%, MM ±0.2mm ❌
- Urgent inquiry: GSM ±10%, MM ±0.3mm ❌

**Current Status:** No thickness matching logic

**Solution:** Need to add thickness fields to inquiries and implement tolerance logic

### 4. Agent Prioritization
**Required:**
- Authorized agents should be prioritized over dealers ❌

**Current Status:** No prioritization logic

**Solution:** Update matching algorithm to prioritize by agent_type

### 5. Material-Thickness Unit Mapping
**Required:**
- Paper → GSM
- Paperboard → GSM/MM
- Dynamic dropdown based on material type ❌

**Solution:** Created `material_thickness_types` table

## Next Steps

1. ✅ Create database migrations (DONE)
2. ⏳ Update ProfileCompleteRequest validation
3. ⏳ Update DealerService::completeProfile()
4. ⏳ Create/Update models (DealerMaterialDetail, MaterialFinish, MaterialThicknessType)
5. ⏳ Update OpportunityService matching logic with thickness tolerance
6. ⏳ Add agent prioritization in matching
7. ⏳ Update API documentation

