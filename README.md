# PMU Pasacao - Fish Port Operations Management System

A web-based fish port operations management system with revenue forecasting for the Port Management Unit (PMU) of Pasacao, Camarines Sur.

## Project Structure

- PMUAPI/ - Laravel 13 backend (PHP)
- PMUUI/ - Nuxt 3 frontend (Vue 3)
- PMUML/ - Flask ML service (Python)

## Features

### 1. Transaction Management
- Records fish unloading, fee assessment, and collection
- Stakeholder management (buyers, brokers, renters)
- Fee type catalog (unloading, weighing, storage, etc.)
- Per-fee-type Excel reports

### 2. Inventory Management
- Tracks port resources (equipment, materials, supplies)
- Stock change logs with audit trail
- Low-stock alerts on dashboard

### 3. Revenue Forecasting
- ARIMA, SARIMA, and Linear Regression models
- Data-driven peak/off-peak classification
- Weather integration (temperature, rainfall, wind)
- Scheduled daily feature building and forecast generation
- One-click forecast generation from dashboard

## Quick Start

### Backend
cd PMUAPI
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan ml:build-features

### Frontend
cd PMUUI
npm install
npm run build

### ML Service
cd PMUML
pip install -r requirements.txt
python src/web.py

## Key Commands

- php artisan ml:build-features - Build transaction_revenue and features tables
- php artisan forecast:seed --days=30 --model=sarima - Generate forecast
- php artisan schedule:run - Run scheduled tasks

## Routes

### Forecasting
- GET /api/v1/forecasts - List all forecasts
- GET /api/v1/forecasts/model/arima - List ARIMA forecasts
- GET /api/v1/forecasts/model/sarima - List SARIMA forecasts
- GET /api/v1/forecasts/model/linear-regression - List LR forecasts
- POST /api/v1/forecasts/train/arima - Train ARIMA model
- POST /api/v1/forecasts/train/sarima - Train SARIMA model
- POST /api/v1/forecasts/train/linear-regression - Train LR model

### Reports
- GET /api/v1/reports/transaction?type=daily&date=... - Transaction report JSON
- GET /api/v1/reports/transaction/xlsx?type=daily&date=... - Transaction report Excel

## Database Tables

### Core Tables
- users - System users (port manager, statistician)
- stakeholders - Buyers, brokers, renters
- stakeholder_types - Stakeholder categories
- fee_types - Fee catalog (unloading, weighing, etc.)
- transactions - One row per transaction
- transaction_items - One row per fee charged per transaction
- inventory_items - Port resources
- inventory_logs - Stock change history
- revenue_histories - Daily revenue totals
- weather_data - Daily weather records

### Forecasting Tables
- transaction_revenue - Combined revenue + weather + date features (append-only)
- transaction_revenue_features - Lag and rolling features (regenerated)
- revenue_forecasts - General forecasts
- revenue_forecasts_arima - ARIMA-specific forecasts
- revenue_forecasts_sarima - SARIMA-specific forecasts
- revenue_forecasts_linear_regression - Linear Regression forecasts

## Roles and Permissions

- Port Manager: View dashboard, forecasts, inventory; trigger forecasts
- Statistician: Create/edit transactions, stakeholders, inventory, fee types
- Permissions enforced via spatie/laravel-permission

## Scope and Limitations

- Web-only, office-based operations
- Physical transactions recorded manually by statistician
- No real-time physical tracking
- No automated procurement or supplier management
- No mobile app or hardware integration
- No external government system integration
- Forecasts are estimates for planning, not guaranteed predictions