# Teams Management - Quick Setup Guide

## 🚀 Quick Start

### Step 1: Seed Permissions
Run this command to create all necessary permissions:

```bash
php artisan db:seed --class=TeamsPermissionSeeder
```

### Step 2: Clear Cache
Clear all application caches:

```bash
php artisan optimize:clear
```

### Step 3: Access Teams
1. Log in as an admin user
2. Navigate to **Teams** from the sidebar
3. Click **"Create Team"** to add your first team

## ✅ Verification Checklist

### Database
- [x] `teams` table exists (migration ran)
- [x] `team_id` column exists in `users` table

### Permissions
Run this command to verify permissions:
```bash
php artisan permission:show
```

You should see:
- teams.view
- teams.create
- teams.edit
- teams.delete

### Files Created
- ✅ `/resources/views/livewire/teams/index.blade.php` - Main component
- ✅ `/database/seeders/TeamsPermissionSeeder.php` - Permissions seeder
- ✅ `/resources/views/components/page-header.blade.php` - Page header component
- ✅ `TEAMS_DOCUMENTATION.md` - Full documentation
- ✅ `TEAMS_QUICK_SETUP.md` - This file

### Files Modified
- ✅ `/routes/web.php` - Added teams route
- ✅ `/resources/views/components/layouts/app/sidebar.blade.php` - Added navigation

### Model Configuration
- ✅ `app/Models/Team.php` - Implements HasMedia
- ✅ `app/Models/User.php` - Has team() relationship

## 🎨 Features Overview

### Create Team
1. Click "Create Team" button
2. Enter team name (required)
3. Add description (optional)
4. Upload logo (optional, max 2MB)
5. Click "Create Team"

### Edit Team
1. Click pencil icon on any team row
2. Modify fields as needed
3. Upload new logo or remove existing one
4. Click "Update Team"

### View Team
1. Click eye icon on any team row
2. View team stats and members
3. See recent activity logs

### Delete Team
1. Click trash icon on any team row
2. Confirm deletion
3. Note: Cannot delete teams with members

## 🔐 Permissions

### Admin Role
- Full access to all team operations
- Can create, edit, view, and delete teams

### Manager Role
- Can only view teams
- Cannot create, edit, or delete

### User Role
- Can be assigned to a team
- View own team information

## 📝 Assigning Users to Teams

### Option 1: Through Users Management
1. Go to **Users** page
2. Edit a user
3. Select team from dropdown
4. Save changes

### Option 2: Programmatically
```php
$user = User::find($userId);
$team = Team::find($teamId);
$user->team_id = $team->id;
$user->save();
```

## 🖼️ Team Logo Requirements

- **Format:** JPG, PNG, GIF
- **Max Size:** 2MB
- **Recommended:** 512x512px or larger
- **Aspect Ratio:** Square images work best

## 🐛 Troubleshooting

### "Permission denied" error
```bash
php artisan db:seed --class=TeamsPermissionSeeder
php artisan cache:clear
```

### Teams menu not visible
1. Check user has `teams.view` permission
2. Clear browser cache
3. Check role permissions

### Logo upload fails
```bash
php artisan storage:link
chmod -R 775 storage/
```

### Cannot delete team
- Teams with members cannot be deleted
- Reassign or remove members first

## 📊 Database Structure

### Teams Table
```sql
CREATE TABLE teams (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Users Relationship
```sql
ALTER TABLE users 
ADD COLUMN team_id BIGINT UNSIGNED NULL,
ADD FOREIGN KEY (team_id) REFERENCES teams(id);
```

## 🎯 Usage Examples

### Count team members
```php
$team = Team::find($teamId);
$memberCount = $team->members()->count();
```

### Get all teams with member count
```php
$teams = Team::withCount('members')->get();
```

### Find user's team
```php
$user = User::find($userId);
$team = $user->team; // Returns Team model or null
```

### Get team logo URL
```php
$team = Team::find($teamId);
$logoUrl = $team->getFirstMediaUrl('team_logo');
```

## 🔄 Common Operations

### Create Team via Code
```php
$team = Team::create([
    'name' => 'Marketing Team',
    'description' => 'Responsible for marketing activities',
]);

// Add logo
if ($request->hasFile('logo')) {
    $team->addMediaFromRequest('logo')
        ->toMediaCollection('team_logo');
}
```

### Update Team
```php
$team = Team::find($teamId);
$team->update([
    'name' => 'Updated Name',
    'description' => 'Updated description',
]);
```

### Delete Team
```php
$team = Team::find($teamId);

// Check for members
if ($team->members()->count() > 0) {
    return back()->with('error', 'Cannot delete team with members');
}

$team->delete(); // Automatically removes logo
```

## 📱 Mobile Responsive

The Teams interface is fully responsive:
- Mobile: Single column layout
- Tablet: 2-column grid
- Desktop: 3-column stats, full table

## 🌙 Dark Mode

Full dark mode support:
- Auto-detects system preference
- Consistent styling across all components
- Proper contrast ratios

## ⚡ Performance Tips

1. **Use pagination** - Don't load all teams at once
2. **Optimize logos** - Compress images before upload
3. **Cache queries** - Use Laravel's query caching
4. **Eager load** - Use `with()` for relationships

## 🧪 Testing Commands

### View all permissions
```bash
php artisan permission:show
```

### List all teams
```bash
php artisan tinker
>>> App\Models\Team::all();
```

### Check user's team
```bash
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $user->team;
```

## 📈 Next Steps

1. ✅ Seed permissions
2. ✅ Clear cache
3. ✅ Create first team
4. Assign users to teams
5. Upload team logos
6. Explore team details
7. Review activity logs

## 🎉 Success!

Your Teams Management system is now ready to use!

For detailed information, see `TEAMS_DOCUMENTATION.md`

---

**Need Help?**
- Check logs: `storage/logs/laravel.log`
- Review permissions: `php artisan permission:show`
- Clear everything: `php artisan optimize:clear`
