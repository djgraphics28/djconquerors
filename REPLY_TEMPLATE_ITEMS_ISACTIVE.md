# Reply Template Items - is_active Feature Implementation

## ✅ Feature Added Successfully!

Reply Template Items now support the `is_active` field, allowing users to:
1. **Mix static text with dynamic variables** in item content
2. **Toggle items on/off** without deleting them
3. **Control which items are rendered** in the output

## What Was Updated

### 1. Database Migration
**File:** `database/migrations/2026_01_12_200001_add_is_active_to_reply_template_items.php`
- Added `is_active` boolean column to `reply_template_items` table
- Defaults to `true`
- Already migrated successfully ✅

### 2. ReplyTemplateItem Model
**File:** `app/Models/ReplyTemplateItem.php`

**Added:**
- `is_active` to `$fillable` array
- `is_active` boolean cast
- `scopeActive()` method to filter active items

**Rendering Logic:**
- Already supports mixing static text with dynamic variables
- Example: `"Language: English, Tagalog"` (static) + `"Your ID: {riscoin_id}"` (dynamic)
- Only variables in `{variable_name}` format are replaced

### 3. ReplyTemplate Model
**File:** `app/Models/ReplyTemplate.php`

**Updated:**
- `renderAllItems()` now filters only active items: `$this->items()->active()->orderBy('order')->get()`
- Inactive items are excluded from the rendered output

### 4. Management Interface
**File:** `resources/views/livewire/reply-template/index.blade.php`

**UI Changes:**
- **Item Display:**
  - Active items show green "Active" badge
  - Inactive items show gray "Inactive" badge and appear dimmed (opacity-60)
  
- **Item Form Modal:**
  - Added "Active" checkbox to control `is_active` status
  - Enhanced help text explaining static vs dynamic content
  - Shows examples: "Language: English, Tagalog" or "Your ID: {riscoin_id}"

- **Component Updates:**
  - Added `$itemIsActive` property (defaults to true)
  - `createItem()` now saves `is_active` value
  - `editItem()` loads current `is_active` value
  - `updateItem()` updates `is_active` value
  - `resetItemForm()` resets `is_active` to true

### 5. Seeder Updated
**File:** `database/seeders/ReplyTemplateSeeder.php`
- All sample items now include `'is_active' => true`
- Demonstrates both static and dynamic content patterns

## How It Works

### Creating Items with Mixed Content

Users can now create template items with:

#### 1. **Pure Static Text**
```
Language: English, Tagalog
Nationality: Filipino
```

#### 2. **Pure Dynamic Variables**
```
{name}
{riscoin_id}
{email}
```

#### 3. **Mixed Static + Dynamic** (Most Common)
```
Your own Riscoin account ID: {riscoin_id}
Deposit Amount: ${invested_amount}
My Name: {name}
Language: English, Tagalog
Nationality: Filipino
Age: {age}
Gender: {gender}
Inviter: {inviters_code}
Assistant: {assistant.riscoin_id}
```

### Active/Inactive Toggle

**Active Items (is_active = true):**
- Included in rendered output
- Shown normally in management UI
- Green "Active" badge

**Inactive Items (is_active = false):**
- Excluded from rendered output
- Dimmed in management UI (gray background, lower opacity)
- Gray "Inactive" badge
- Still visible for reference/editing
- Can be reactivated anytime

## Usage Examples

### Example 1: Standard Format with Static + Dynamic

```
Title: User Information
Content:
Your own Riscoin account ID: {riscoin_id}
Deposit Amount: ${invested_amount}
My Name: {name}
Language: English, Tagalog
Nationality: Filipino
Age: {age}
Gender: {gender}
Inviter: {inviters_code}
Assistant: {assistant.riscoin_id}
Active: ✅ Yes
```

**Rendered Output:**
```
Your own Riscoin account ID: R12345
Deposit Amount: $1,250.00
My Name: John Doe
Language: English, Tagalog
Nationality: Filipino
Age: 30
Gender: Male
Inviter: INV123
Assistant: A456
```

### Example 2: Multiple Items for Structured Reply

**Item 1: Greeting**
```
Content: Hello Sir Martin,

Here is my information:
Active: ✅ Yes
```

**Item 2: Personal Details**
```
Content: Riscoin ID: {riscoin_id}
Full Name: {name}
Email: {email}
Deposit Amount: ${invested_amount}
Language: English, Tagalog
Nationality: Filipino
Age: {age}
Gender: {gender}
Active: ✅ Yes
```

**Item 3: Closing** (Temporarily Disabled)
```
Content: Thank you!
Active: ❌ No
```

**Rendered Output** (Item 3 excluded):
```
Hello Sir Martin,

Here is my information:

Riscoin ID: R12345
Full Name: John Doe
Email: john@example.com
Deposit Amount: $1,250.00
Language: English, Tagalog
Nationality: Filipino
Age: 30
Gender: Male
```

## Available Dynamic Variables

All variables from the User model:
- `{name}` - Full name
- `{first_name}` - First name
- `{last_name}` - Last name
- `{email}` - Email address
- `{riscoin_id}` - Riscoin ID
- `{invested_amount}` - Investment amount (auto-formatted with commas)
- `{age}` - Age
- `{gender}` - Gender
- `{inviters_code}` - Inviter code
- `{assistant.riscoin_id}` - Assistant's Riscoin ID (dot notation)
- Any other User model attribute

## Benefits

✅ **Flexibility:** Mix static and dynamic content freely
✅ **Control:** Enable/disable items without deletion
✅ **Testing:** Deactivate items to test different message variations
✅ **Reusability:** Keep inactive items for future use
✅ **Organization:** Separate greeting, details, and closing into items
✅ **Consistency:** Standardize static text (language, nationality, etc.)

## Access

- **Management URL:** `/reply-template`
- **Permission Required:** `reply-template.access`
- **Already Granted To:** Admin role ✅

## Migration Status

✅ Migration run successfully
✅ Existing items updated to active
✅ Caches cleared
✅ Ready to use!

---

**🎉 The feature is fully implemented and ready to use!** Users can now create flexible reply template items with both static text and dynamic variables, and toggle them active/inactive as needed.
