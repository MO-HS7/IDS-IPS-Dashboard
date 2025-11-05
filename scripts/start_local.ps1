# IDS-IPS Dashboard - Local Setup Script (Windows)

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "IDS-IPS Dashboard - Local Setup Script" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Check if .env exists
if (!(Test-Path .env)) {
    Write-Host "Creating .env file..." -ForegroundColor Yellow
    Copy-Item .env.example .env
    
    # Append ML configuration
    if (Test-Path .env.ml.example) {
        Add-Content .env "`n# ML Configuration"
        Get-Content .env.ml.example | Add-Content .env
    }
    
    Write-Host "✓ .env created" -ForegroundColor Green
} else {
    Write-Host "✓ .env file exists" -ForegroundColor Green
}

# Install Composer dependencies
Write-Host "`nInstalling PHP dependencies..." -ForegroundColor Yellow
composer install --no-interaction
Write-Host "✓ Composer dependencies installed" -ForegroundColor Green

# Install NPM dependencies
Write-Host "`nInstalling Node dependencies..." -ForegroundColor Yellow
npm install
Write-Host "✓ NPM dependencies installed" -ForegroundColor Green

# Generate application key
Write-Host "`nGenerating application key..." -ForegroundColor Yellow
php artisan key:generate --ansi
Write-Host "✓ Application key generated" -ForegroundColor Green

# Run database migrations
Write-Host "`nRunning database migrations..." -ForegroundColor Yellow
php artisan migrate --force
Write-Host "✓ Migrations completed" -ForegroundColor Green

# Seed database (optional)
$seed = Read-Host "Seed database with test data? (y/n)"
if ($seed -eq "y" -or $seed -eq "Y") {
    php artisan db:seed
    Write-Host "✓ Database seeded" -ForegroundColor Green
}

# Create storage symlink
Write-Host "`nCreating storage symlink..." -ForegroundColor Yellow
php artisan storage:link
Write-Host "✓ Storage symlink created" -ForegroundColor Green

# Clear caches
Write-Host "`nClearing caches..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
php artisan view:clear
Write-Host "✓ Caches cleared" -ForegroundColor Green

# Build frontend assets
Write-Host "`nBuilding frontend assets..." -ForegroundColor Yellow
npm run build
Write-Host "✓ Frontend assets built" -ForegroundColor Green

# Check Python and ML models
Write-Host "`nChecking Python environment..." -ForegroundColor Yellow
$pythonCmd = Get-Command python -ErrorAction SilentlyContinue
if ($pythonCmd) {
    Write-Host "✓ Python is installed" -ForegroundColor Green
    
    # Check if ML models exist
    if (Test-Path ml_models\random_forest_model.pkl) {
        Write-Host "✓ ML models found" -ForegroundColor Green
    } else {
        Write-Host "⚠ ML models not found. Training models..." -ForegroundColor Yellow
        Push-Location ml_scripts
        python quick_train_models.py
        Pop-Location
        Write-Host "✓ ML models trained" -ForegroundColor Green
    }
} else {
    Write-Host "✗ Python not found. Please install Python 3.8+" -ForegroundColor Red
}

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Setup completed successfully!" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "To start the application:" -ForegroundColor Yellow
Write-Host ""
Write-Host "  1. Start Laravel server:" -ForegroundColor White
Write-Host "     php artisan serve" -ForegroundColor Cyan
Write-Host ""
Write-Host "  2. Start queue worker (in another terminal):" -ForegroundColor White
Write-Host "     php artisan queue:work" -ForegroundColor Cyan
Write-Host ""
Write-Host "  3. Start frontend dev server (optional):" -ForegroundColor White
Write-Host "     npm run dev" -ForegroundColor Cyan
Write-Host ""
Write-Host "  4. Access the application:" -ForegroundColor White
Write-Host "     http://localhost:8000" -ForegroundColor Cyan
Write-Host ""
Write-Host "Default credentials:" -ForegroundColor Yellow
Write-Host "  Email: admin@example.com"
Write-Host "  Password: Check database seeder"
Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
