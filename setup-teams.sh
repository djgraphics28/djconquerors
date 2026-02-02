#!/bin/bash

# Teams Management System - Setup Script
# Run this script to set up the Teams Management system

echo "🚀 Setting up Teams Management System..."
echo ""

# Step 1: Seed Permissions
echo "📝 Step 1: Seeding permissions..."
php artisan db:seed --class=TeamsPermissionSeeder
echo "✅ Permissions seeded successfully!"
echo ""

# Step 2: Clear all caches
echo "🧹 Step 2: Clearing caches..."
php artisan optimize:clear
echo "✅ Caches cleared!"
echo ""

# Step 3: Verify storage link
echo "🔗 Step 3: Creating storage link..."
php artisan storage:link
echo "✅ Storage linked!"
echo ""

# Step 4: Set proper permissions
echo "🔒 Step 4: Setting storage permissions..."
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
echo "✅ Permissions set!"
echo ""

echo "✨ Setup complete!"
echo ""
echo "📱 Next steps:"
echo "1. Visit your application: /teams"
echo "2. Log in as admin"
echo "3. Start creating teams!"
echo ""
echo "📚 Documentation:"
echo "- Quick Start: TEAMS_QUICK_SETUP.md"
echo "- Full Docs: TEAMS_DOCUMENTATION.md"
echo "- Summary: TEAMS_IMPLEMENTATION_SUMMARY.md"
echo ""
echo "🎉 Happy team managing!"
