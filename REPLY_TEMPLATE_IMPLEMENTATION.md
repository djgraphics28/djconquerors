# Reply Template System - Implementation Summary

## ✅ Implementation Complete!

A complete Reply Template management system with child items (ReplyTemplateItem), built using **Livewire Volt** following your existing code patterns (Guide/GuideItem structure).

## Database Structure

### Tables Created:
1. **reply_templates** - Parent table
   - id
   - name (template name)
   - description (optional)
   - order (for drag-drop sorting)
   - is_active (boolean)
   - timestamps

2. **reply_template_items** - Child table
   - id
   - reply_template_id (foreign key with cascade delete)
   - title (item title)
   - content (item content with variables)
   - order (for drag-drop sorting within template)
   - timestamps

## Models Created

### ReplyTemplate Model (`app/Models/ReplyTemplate.php`)
- Relationship: `hasMany` ReplyTemplateItem
- Methods:
  - `items()` - Get all items for the template
  - `scopeActive()` - Filter active templates
  - `scopeOrdered()` - Order by order column
  - `renderAllItems()` - Render all items with user data

### ReplyTemplateItem Model (`app/Models/ReplyTemplateItem.php`)
- Relationship: `belongsTo` ReplyTemplate
- Methods:
  - `replyTemplate()` - Get parent template
  - `render()` - Render item content with user data
  - `getVariableValue()` - Get variable value from User model (supports dot notation)
  - `getDefaultValue()` - Get fallback default values

## Views Created (Livewire Volt)

### resources/views/livewire/reply-template/index.blade.php
- **Two-column layout:**
  - **Left Column:** Templates list (drag-drop sortable)
  - **Right Column:** Selected template's items (drag-drop sortable)
- **Modal forms** for creating/editing templates and items
- **Features:**
  - ✅ Create, edit, delete templates
  - ✅ Create, edit, delete template items
  - ✅ Drag-and-drop reordering for both templates and items
  - ✅ Active/inactive status toggle
  - ✅ Real-time preview
  - ✅ Dark mode support
  - ✅ Responsive design

### resources/views/livewire/widget/first-reply-to-martin.blade.php (Updated)
- Template selector dropdown
- Live preview of rendered template
- Copy to clipboard functionality (mobile & desktop compatible)
- Falls back to original static form if no templates exist
- Displays all template items combined

## Available Template Variables

All variables are dynamically sourced from the User model with fallback defaults:

- `{name}` - User's full name → Default: "User"
- `{first_name}` - User's first name → Default: "User"
- `{last_name}` - User's last name → Default: ""
- `{email}` - User's email → Default: "email@example.com"
- `{riscoin_id}` - User's Riscoin ID → Default: "N/A"
- `{invested_amount}` - Formatted with commas → Default: "0.00"
- `{age}` - User's age → Default: "Not specified"
- `{gender}` - User's gender → Default: "Not specified"
- `{inviters_code}` - Inviter's code → Default: "N/A"
- `{assistant.riscoin_id}` - Assistant's Riscoin ID (dot notation) → Default: "N/A"

**Any User model attribute can be used!** The system supports dot notation for relationships.

## Routes

```php
// Protected by 'reply-template.access' permission
Volt::route('reply-template', 'reply-template.index')
    ->name('reply-template.index')
    ->middleware('can:reply-template.access');
```

**URL:** `/reply-template`

## Permission Required

Users need the **`reply-template.access`** permission to access the management interface.

### Grant Permission:
```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Create permission
$permission = Permission::firstOrCreate(['name' => 'reply-template.access']);

// Grant to admin role
$role = Role::findByName('admin');
$role->givePermissionTo($permission);

// Or grant to specific user
$user->givePermissionTo('reply-template.access');
```

## Sample Templates Seeded

The seeder creates 4 templates with multiple items:

### 1. First Reply to Martin - Standard (1 item)
Simple one-block format with all user information.

### 2. First Reply to Martin - Detailed (3 items)
- Greeting
- Personal Details
- Closing

### 3. First Reply to Martin - Professional (3 items)
- Professional Greeting
- Registration Details (bullet points)
- Professional Closing

### 4. Welcome Message (3 items)
- Welcome
- Confirmation
- Support

## Installation Status

✅ Migration created and run successfully
✅ Models created with relationships
✅ Livewire Volt views created
✅ Routes configured
✅ Seeder created and run
✅ Sample data populated
✅ Widget updated to use templates
✅ Caches cleared

## How to Use

### 1. Management Interface

Navigate to **`/reply-template`** to:
- View all templates
- Create new templates
- Edit template details
- Add/edit/delete template items
- Drag-and-drop to reorder templates and items
- Toggle active/inactive status

### 2. In the Dashboard Widget

The widget at `livewire/widget/first-reply-to-martin.blade.php`:
- Automatically loads active templates
- Shows a dropdown to select template
- Renders selected template with current user's data
- Provides "Copy to Clipboard" button
- Shows live preview of rendered content
- Falls back to original format if no templates exist

### 3. Adding New Variables

To add support for new variables, update `ReplyTemplateItem.php`:

```php
protected function getDefaultValue(string $variable): string
{
    $defaults = [
        'your_new_variable' => 'default value',
        'phone_number' => 'Not provided',
        'address' => 'Not specified',
        // ... add more
    ];
    return $defaults[$variable] ?? '{' . $variable . '}';
}
```

Then use in templates: `{your_new_variable}`

## Key Features

✅ **Parent-Child Relationship** - ReplyTemplate → ReplyTemplateItem
✅ **Drag-and-Drop Sorting** - For both templates and items using SortableJS
✅ **Modal-Based CRUD** - Clean UI without page refreshes
✅ **Dynamic Variable Replacement** - From User model with dot notation support
✅ **Fallback Default Values** - Graceful handling of missing data
✅ **Active/Inactive Status** - Control which templates are visible
✅ **Permission-Based Access** - Secure with `reply-template.access`
✅ **Mobile-Friendly Copy** - Works on all devices
✅ **Real-Time Livewire Updates** - No page refreshes needed
✅ **Dark Mode Support** - Full theme compatibility
✅ **Follows Existing Patterns** - Matches Guide/GuideItem structure exactly

## Architecture Highlights

### Following Your Existing Code Patterns:
- Uses **Livewire Volt** (not controllers)
- Parent-child relationship like `Guide` → `GuideItem`
- Drag-and-drop with SortableJS
- Modal forms instead of separate create/edit pages
- Dark mode styling
- Permission middleware
- Ordered scopes

### Template Variable System:
- Uses regex to find `{variable_name}` patterns
- Supports dot notation for relationships: `{assistant.riscoin_id}`
- Falls back to defaults if variable not found
- Formats numbers automatically (invested_amount)

## Troubleshooting

### If templates don't show in widget:
1. Check if templates are marked as active
2. Verify user has items in the template
3. Clear caches: `php artisan optimize:clear`

### If permission errors:
```php
// Grant permission to your user
$user = auth()->user();
$user->givePermissionTo('reply-template.access');
```

### If drag-and-drop doesn't work:
1. Check browser console for JavaScript errors
2. Verify SortableJS is loading: View page source, search for "sortablejs"
3. Make sure Livewire is properly initialized

## Next Steps

1. **Grant Permissions** to users who should manage templates
2. **Create Custom Templates** or edit the seeded ones
3. **Test the Widget** on the dashboard
4. **Add More Variables** as needed for your use case

## File Locations

- **Migration:** `database/migrations/2026_01_12_000001_create_reply_templates_table.php`
- **Models:** 
  - `app/Models/ReplyTemplate.php`
  - `app/Models/ReplyTemplateItem.php`
- **View:** `resources/views/livewire/reply-template/index.blade.php`
- **Widget:** `resources/views/livewire/widget/first-reply-to-martin.blade.php`
- **Seeder:** `database/seeders/ReplyTemplateSeeder.php`
- **Routes:** `routes/web.php` (line ~73)

---

**🎉 Implementation Complete!** Your Reply Template system is ready to use.
