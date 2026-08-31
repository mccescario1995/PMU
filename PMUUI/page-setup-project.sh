#!/bin/bash

echo "🚀 Creating Nuxt pages..."

# Dashboard
mkdir -p pages/dashboard
touch pages/dashboard/index.vue

# Transactions
mkdir -p pages/transactions/edit
touch pages/transactions/index.vue
touch pages/transactions/create.vue
touch pages/transactions/[id].vue
touch pages/transactions/edit/[id].vue

# Stakeholders
mkdir -p pages/stakeholders
touch pages/stakeholders/index.vue
touch pages/stakeholders/brokers.vue
touch pages/stakeholders/buyers.vue

# Inventory
mkdir -p pages/inventory
touch pages/inventory/index.vue
touch pages/inventory/categories.vue
touch pages/inventory/stocks.vue

# Revenue
mkdir -p pages/revenue
touch pages/revenue/index.vue

# Forecast
mkdir -p pages/forecast
touch pages/forecast/index.vue

# Reports
mkdir -p pages/reports
touch pages/reports/index.vue
touch pages/reports/daily.vue
touch pages/reports/monthly.vue
touch pages/reports/yearly.vue

# Accounts (under Admin CMS)
touch pages/cms/accounts/index.vue

# Settings
mkdir -p pages/settings
touch pages/settings/index.vue

echo "✅ Pages created successfully!"