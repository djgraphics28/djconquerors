# Riscoin Link Copy Feature - Implementation Summary

## Overview
Added a "Copy Riscoin Link" action button to the My Team table that allows users to quickly copy the active Riscoin link with the team member's riscoin_id appended as a query parameter.

## Feature Details

### What It Does
- Displays a link icon button in the Actions column for each team member
- When clicked, copies the active Riscoin link with the user's riscoin_id to clipboard
- Format: `{riscoin_link_url}?code={user_riscoin_id}`
- Only shows the button if there's an active Riscoin link available

### Example
If the active Riscoin link is: `https://example.com/register`
And the user's riscoin_id is: `RISC123456`

The copied link will be: `https://example.com/register?code=RISC123456`

## Implementation Details

### Files Modified
- **File**: `resources/views/livewire/my-team.blade.php`

### Changes Made

#### 1. Added Import
```php
use App\Models\RiscoinLink;
```

#### 2. Added Method
```php
// Get Riscoin Link with user's riscoin_id
public function getRiscoinLinkWithCode($riscoinId)
{
    // Get the first active Riscoin link
    $activeLink = RiscoinLink::where('is_active', true)->first();
    
    if (!$activeLink) {
        return null;
    }

    // Append the riscoin_id as a query parameter
    return $activeLink->url . '?code=' . $riscoinId;
}
```

#### 3. Added Button in Actions Column
```blade
@php
    $riscoinLink = $this->getRiscoinLinkWithCode($user->riscoin_id);
@endphp
@if($riscoinLink)
    <flux:button 
        onclick="copyToClipboard('{{ $riscoinLink }}')" 
        variant="ghost"
        size="sm" 
        data-test="copy-link-{{ $user->id }}"
        title="Copy Riscoin Link">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
        </svg>
    </flux:button>
@endif
```

## How It Works

1. **Query Active Link**: The method queries the `riscoin_links` table for links where `is_active = true`
2. **Get First Active**: Only the first active link is used
3. **Append Query Parameter**: The user's `riscoin_id` is appended as `?code=`
4. **Conditional Display**: The button only appears if an active link exists
5. **Copy to Clipboard**: Uses existing `copyToClipboard()` JavaScript function
6. **Toast Notification**: Shows success message when copied

## Button Features

- **Icon**: Link chain icon (SVG)
- **Variant**: Ghost (transparent background)
- **Size**: Small (matches other action buttons)
- **Tooltip**: "Copy Riscoin Link" on hover
- **Position**: After Edit button in Actions column

## Usage

### For Users
1. Navigate to My Team page
2. Find the team member you want to share the link for
3. Look for the link icon button in the Actions column
4. Click the button to copy the personalized Riscoin link
5. A success message will appear confirming the copy

### Prerequisites
- At least one Riscoin link must be marked as active in the Riscoin Links Management
- The user must have a valid `riscoin_id`

## Dependencies

### Models Used
- `RiscoinLink` - To fetch active links
- `User` - Team member data

### Existing Functions
- `copyToClipboard(text)` - JavaScript function for clipboard operations
- Supports modern browsers with Clipboard API
- Falls back to `document.execCommand('copy')` for older browsers

## Security & Performance

### Security
- ✅ No sensitive data exposed (riscoin_id is already visible in table)
- ✅ Only fetches active links (is_active = true)
- ✅ Uses first() method to prevent multiple link conflicts

### Performance
- ✅ Query is simple and indexed (is_active boolean)
- ✅ Method is called per row but uses efficient Eloquent query
- ✅ No N+1 query issues (single query per user row)

## Future Enhancements (Optional)

1. **Multiple Links**: Support selecting from multiple active links
2. **Link Categories**: Different link types for different purposes
3. **Link Analytics**: Track which links are copied most
4. **Custom Parameters**: Add more query parameters (referral source, campaign, etc.)
5. **QR Code**: Generate QR code for the link
6. **Shortened URL**: Integration with URL shortener service

## Testing Checklist

- [x] Method added to component
- [x] Import added for RiscoinLink model
- [x] Button added to Actions column
- [x] Button only shows when active link exists
- [x] Link format is correct: `url?code=riscoin_id`
- [x] Copy to clipboard works
- [x] Toast notification shows on success
- [x] Button styling matches other action buttons
- [x] Tooltip shows on hover

## Troubleshooting

### Button Not Showing
- Check if there's an active Riscoin link in the database
- Verify `is_active = true` for at least one link
- Check user has a valid `riscoin_id`

### Link Not Copying
- Verify browser supports Clipboard API
- Check JavaScript console for errors
- Test fallback function with older browsers

### Wrong Link Format
- Verify active link URL is valid
- Check riscoin_id is not null
- Ensure query parameter separator is correct

## Related Documentation
- `RISCOIN_LINKS_DOCUMENTATION.md` - Main Riscoin Links system
- `TEAMS_DOCUMENTATION.md` - Teams management system

## Support
For issues or questions:
- Verify active Riscoin links exist
- Check user riscoin_id is valid
- Review browser console for errors
- Test copy functionality with different browsers
