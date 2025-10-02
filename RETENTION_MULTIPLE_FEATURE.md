# Feature: Multiple Retentions Addition

## Overview
Enhanced the retention component to allow adding multiple retentions at once instead of adding them one by one.

## Changes Made

### UI Improvements
- **Split Screen Layout**: The dialog now shows two panels:
  - Left panel: Form to add individual retentions to the selection list
  - Right panel: List of selected retentions with preview and removal options

### New Features
1. **Multiple Selection**: Users can now add multiple retentions to a list before confirming all at once
2. **Prevention of Duplicates**: Cannot add the same retention type twice
3. **Real-time Calculation**: Shows total retention amount across all selected retentions
4. **Individual Removal**: Can remove specific retentions from the list before confirming
5. **Batch Confirmation**: All selected retentions are added to the document at once

### How to Use

1. **Open Retention Dialog**: Click "+ Agregar Retención" button
2. **Add Retentions to List**:
   - Select retention type from dropdown
   - Choose base type (Total, Administración, Imprevisto, Utilidad)
   - View calculated retention amount
   - Click "Agregar a Lista" to add to selection
3. **Review Selected Retentions**:
   - View all selected retentions in the right panel
   - See total retention amount
   - Remove individual retentions if needed
4. **Confirm All**: Click "Agregar Todas (X)" to add all retentions to the document

### Technical Implementation

#### Frontend Changes (retention.vue)
- **New Data Properties**:
  - `selectedRetentions`: Array to store selected retentions before confirmation
  
- **New Computed Properties**:
  - `availableRetentions`: Filters out already selected retention types
  - `isRetentionAlreadySelected`: Prevents duplicate selections
  - `totalRetentions`: Calculates sum of all selected retention amounts

- **New Methods**:
  - `addRetentionToList()`: Adds retention to selection list
  - `removeRetention(index)`: Removes specific retention from list
  - `confirmAllRetentions()`: Emits all selected retentions at once
  - `getBaseTypeLabel(baseType)`: Returns human-readable base type labels

#### Backward Compatibility
- The component maintains compatibility with existing code
- The original `@add` event is still emitted for each retention
- Legacy `clickAddItem()` method is preserved

### Benefits
1. **Improved UX**: Users can add multiple retentions in one session
2. **Error Prevention**: Cannot accidentally add duplicate retentions
3. **Better Overview**: Clear view of all selected retentions before confirmation
4. **Efficiency**: Reduced clicks and dialog reopening

### Files Modified
- `/root/pro2/modules/Factcolombia1/Resources/assets/js/views/tenant/document/partials/retention.vue`

### Components That Use This Feature
- Document notes (credit/debit notes)
- Regular document forms (Form2.vue)
- AIU document forms (FormAiu.vue)
- Support documents (Purchase module)

## Usage Example

Instead of:
1. Open retention dialog
2. Select retention A → Add → Close dialog
3. Open retention dialog again  
4. Select retention B → Add → Close dialog
5. Repeat...

Now:
1. Open retention dialog
2. Select retention A → Add to list
3. Select retention B → Add to list  
4. Select retention C → Add to list
5. Review all selections
6. Confirm all at once → Close dialog

This reduces the workflow from multiple dialog openings to a single session.
