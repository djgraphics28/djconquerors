# Teams Management System Documentation

## Overview
Complete CRUD system for managing organization teams in DJ Conquerors application with Spatie Media Library integration for team logos.

## Files Created/Modified

### 1. Model
**Location:** `app/Models/Team.php`
- Already exists with proper configuration
- Implements `HasMedia` interface
- Has `team_logo` media collection (single file)
- Relationship with users via `members()` method

### 2. Migration
**Location:** `database/migrations/2026_01_31_225025_create_teams_table.php`
- Already exists
- Creates `teams` table with:
  - `id` (primary key)
  - `name` (string)
  - `description` (text, nullable)
  - `timestamps`

### 3. Livewire Component
**Location:** `resources/views/livewire/teams/index.blade.php`
- Complete CRUD implementation using Livewire Volt
- Features:
  - List teams with pagination
  - Search functionality
  - Create new teams
  - Edit existing teams
  - Delete teams (with validation)
  - View team details with members list
  - Upload/remove team logos
  - Activity logs tracking

### 4. Routes
**Location:** `routes/web.php`
- Added route: `Volt::route('teams', 'teams.index')->name('teams.index')->middleware('can:teams.view');`
- Protected with permission middleware

### 5. Navigation
**Location:** `resources/views/components/layouts/app/sidebar.blade.php`
- Added Teams menu item with `user-group` icon
- Protected with `@can('teams.view')` directive
- Positioned after Reply Templates and before Users

### 6. Permissions Seeder
**Location:** `database/seeders/TeamsPermissionSeeder.php`
- Creates permissions:
  - `teams.view`
  - `teams.create`
  - `teams.edit`
  - `teams.delete`
- Assigns permissions to admin role
- Gives view permission to manager role

## Features

### 1. Dashboard Stats
- Total Teams count
- Total Members in teams
- Average members per team

### 2. Teams Table
- Displays team logo (or default icon)
- Team name
- Description (truncated with line-clamp-2)
- Member count badge
- Creation date
- Action buttons (view, edit, delete)

### 3. Create Team
- Team name (required, unique)
- Description (optional)
- Team logo upload (optional, max 2MB)
- Real-time preview of uploaded logo
- Validation messages

### 4. Edit Team
- All create fields
- Shows current logo with remove option
- Update logo functionality
- Preserves data on validation errors

### 5. View Team Details
- Team logo and information
- Team stats (members, created date, last updated)
- List of all team members with:
  - Avatar initials
  - Name and email
  - Riscoin ID
- Recent activity logs (last 20)
- Scrollable lists for better UX

### 6. Delete Team
- Confirmation modal with warning
- Prevents deletion if team has members
- Shows member count warning
- Proper cleanup of media files

### 7. Search & Filters
- Real-time search across team names and descriptions
- Adjustable results per page (10, 25, 50, 100)
- URL query string persistence

### 8. Activity Logging
- Tracks all team operations
- Shows who performed actions
- Displays human-readable timestamps

## Installation & Setup

### 1. Run Migration (if not already done)
```bash
php artisan migrate
```

### 2. Seed Permissions
```bash
php artisan db:seed --class=TeamsPermissionSeeder
```

### 3. Clear Cache
```bash
php artisan optimize:clear
```

### 4. Verify Spatie Media Library
Ensure the media library is properly configured in `config/media-library.php`

## Usage

### Admin Access
1. Navigate to Teams from sidebar
2. Create, edit, view, or delete teams
3. Upload team logos
4. View team members and activity

### Manager Access
- Can view teams only
- No create, edit, or delete permissions

### User Access
- Team assignment via Users management
- View own team information

## Permissions Structure

```php
'teams.view'   => View teams list and details
'teams.create' => Create new teams
'teams.edit'   => Edit existing teams
'teams.delete' => Delete teams
```

## API Methods

### Component Properties
- `$team` - Current team being edited
- `$name` - Team name
- `$description` - Team description
- `$team_logo` - Logo file upload
- `$search` - Search query
- `$perPage` - Pagination count

### Component Methods
- `create()` - Create new team
- `edit($id)` - Load team for editing
- `update()` - Update existing team
- `delete()` - Delete team
- `viewTeam($id)` - Show team details
- `confirmDelete($id)` - Show delete confirmation
- `removeLogo()` - Mark logo for removal
- `openCreateModal()` - Show create modal
- `closeModal()` - Close create/edit modal
- `closeViewModal()` - Close view modal
- `closeDeleteModal()` - Close delete confirmation

## UI Components Used

### Flux UI Components
- `flux:button` - Action buttons
- `flux:input` - Text inputs
- `flux:textarea` - Description field
- `flux:select` - Dropdown selects
- `flux:modal` - Modal dialogs
- `flux:heading` - Section headings
- `flux:subheading` - Section subtitles
- `flux:label` - Form labels
- `flux:error` - Error messages

### Tailwind Classes
- Responsive grid layouts
- Dark mode support
- Hover effects and transitions
- Custom color schemes
- Proper spacing and typography

## Security Features

1. **Permission-based Access**
   - All routes protected with permissions
   - Blade directives for UI elements

2. **Validation**
   - Required fields validation
   - Unique team name constraint
   - File type and size validation
   - XSS protection via Livewire

3. **Data Integrity**
   - Prevents deletion of teams with members
   - Cascading media cleanup
   - Activity logging for audit trail

## Best Practices Followed

1. **Code Organization**
   - Separation of concerns
   - Reusable components
   - DRY principles

2. **User Experience**
   - Loading states
   - Confirmation dialogs
   - Success/error messages
   - Responsive design
   - Dark mode support

3. **Performance**
   - Lazy loading with pagination
   - Debounced search
   - Efficient queries with `withCount()`
   - Media optimization

4. **Accessibility**
   - Semantic HTML
   - ARIA labels
   - Keyboard navigation
   - Screen reader support

## Troubleshooting

### Teams not showing
- Check permissions are seeded
- Verify user has `teams.view` permission
- Clear application cache

### Logo upload fails
- Check storage permissions
- Verify media library configuration
- Check file size limits
- Ensure temp directory is writable

### Cannot delete team
- Check if team has members
- Reassign members first
- Verify `teams.delete` permission

## Future Enhancements

1. Team hierarchy/parent-child relationships
2. Team-based notifications
3. Team performance metrics
4. Bulk member assignment
5. Team templates
6. Export team data
7. Team calendar/events
8. Team chat integration

## Testing

### Manual Testing Checklist
- [ ] Create team without logo
- [ ] Create team with logo
- [ ] Edit team name
- [ ] Edit team description
- [ ] Update team logo
- [ ] Remove team logo
- [ ] View team details
- [ ] Search teams
- [ ] Delete empty team
- [ ] Attempt to delete team with members
- [ ] Check permissions for different roles
- [ ] Verify activity logs
- [ ] Test pagination
- [ ] Test responsive design
- [ ] Test dark mode

## Support

For issues or questions:
1. Check this documentation
2. Review Laravel Livewire docs
3. Check Spatie Media Library docs
4. Review application logs

---

**Last Updated:** January 31, 2026
**Version:** 1.0.0
**Developer:** Senior Laravel Developer
