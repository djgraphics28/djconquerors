# Reply Template Quick Toggle Feature

## Overview
The Reply Template management interface now includes quick toggle switches for easily enabling/disabling templates and items without needing to edit them.

## Features Added

### 1. Template Toggle Switch
- **Location**: On each template card in the templates list
- **Functionality**: Click the toggle to instantly enable/disable a template
- **Visual Feedback**: 
  - Green toggle = Active
  - Gray toggle = Inactive
  - Inactive templates appear with reduced opacity
- **Effect**: Inactive templates won't appear in the dropdown selector on the first-reply-to-martin widget

### 2. Item Toggle Switch
- **Location**: On each item card in the items list
- **Functionality**: Click the toggle to instantly enable/disable an item
- **Visual Feedback**:
  - Green toggle = Active
  - Gray toggle = Inactive
  - Inactive items appear with gray background and reduced opacity
- **Effect**: Inactive items won't be included when rendering the template

### 3. Improved Layout
- **Drag Handles**: Clear visual indicators for dragging (≡ icon)
- **Better Spacing**: Improved padding and gaps between elements
- **Icon Headers**: Added icons to section headers for better visual hierarchy
- **Info Boxes**: Added helpful tips and information boxes
- **Responsive Design**: Better mobile and tablet support with flexbox layouts
- **Dark Mode**: Full dark mode support with proper color schemes

## Usage Examples

### Quick Disable/Enable Workflow

**Scenario 1: Testing a new template**
1. Create your new template
2. Add items to it
3. Toggle the template OFF while you're still working on it
4. Once ready, toggle it ON to make it available

**Scenario 2: Seasonal content**
1. Create a template for holiday messages
2. Toggle it ON during the holiday season
3. Toggle it OFF after the season ends
4. Keep it for next year without deleting

**Scenario 3: A/B testing replies**
1. Create two similar templates
2. Toggle one ON and one OFF
3. Test the active one
4. Switch them to compare results

**Scenario 4: Mixed static/dynamic content**
1. Create items with static text: "Language: English, Tagalog"
2. Add items with dynamic variables: "Your ID: {riscoin_id}"
3. Toggle items ON/OFF based on what information you need to include
4. Different situations need different information

## Visual Design

### Template Card Layout
```
┌─────────────────────────────────────────────┐
│ ≡  Template Name                   [Toggle] │
│    Description text...             [Edit]   │
│    📄 5 items                       [Delete] │
└─────────────────────────────────────────────┘
```

### Item Card Layout
```
┌──────────────────────────────────────────────────┐
│ ≡  Item Title  [Active Badge]        [Toggle]   │
│    ┌──────────────────────────┐      [Edit]     │
│    │ Content preview...       │      [Delete]   │
│    └──────────────────────────┘                 │
└──────────────────────────────────────────────────┘
```

## Technical Details

### Methods Added
- `toggleTemplateActive($templateId)` - Toggles template is_active status
- `toggleItemActive($itemId)` - Toggles item is_active status

### UI Components
- Toggle Switch: Custom CSS toggle with smooth transitions
- Hover Effects: Subtle hover states on buttons
- Color Coding: Green for active, gray for inactive
- Icons: SVG icons for all actions

## Benefits

1. **Speed**: Toggle instead of opening edit form
2. **Safety**: No risk of accidentally changing content
3. **Visibility**: Clear visual indication of active/inactive state
4. **Flexibility**: Quickly test different combinations
5. **Organization**: Keep inactive templates without deleting them

## Notes

- Toggling a template doesn't affect its items' active status
- Items are only rendered if BOTH the template AND the item are active
- Drag-and-drop still works on inactive items for organization
- All changes are saved immediately to the database
- Session flash messages confirm successful updates
