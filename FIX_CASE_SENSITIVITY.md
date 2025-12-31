# Fix Case Sensitivity Issue

## Problem
On Linux/VPS, the file system is case-sensitive. Your directories are lowercase (`handlers`, `models`) but your namespaces use capital letters (`Handlers`, `Models`). This causes the autoloader to fail.

## Solution

### Option 1: Run the Fix Script (Recommended)
On your VPS, run:
```bash
bash fix_case_sensitivity.sh
```

### Option 2: Manual Fix
On your VPS, run these commands:
```bash
cd /var/www/bot/sarvagoldbot
mv src/handlers src/Handlers
mv src/models src/Models
```

### Option 3: Using Git (if you're using Git)
On Windows, you can use Git to rename:
```bash
git mv src/handlers src/Handlers
git mv src/models src/Models
git commit -m "Fix case sensitivity for directories"
```

Then push and pull on VPS.

## After Fixing
After renaming the directories, your bot should work correctly on the VPS.

