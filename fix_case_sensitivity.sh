#!/bin/bash

# Script to fix case sensitivity issues for Linux/VPS
# Run this script on your VPS: bash fix_case_sensitivity.sh

cd "$(dirname "$0")"

echo "Fixing directory case sensitivity..."

# Rename handlers to Handlers
if [ -d "src/handlers" ] && [ ! -d "src/Handlers" ]; then
    mv src/handlers src/Handlers
    echo "✓ Renamed handlers to Handlers"
fi

# Rename models to Models
if [ -d "src/models" ] && [ ! -d "src/Models" ]; then
    mv src/models src/Models
    echo "✓ Renamed models to Models"
fi

echo "Done! Directories now match namespace capitalization."

