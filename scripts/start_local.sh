#!/bin/bash

echo "========================================="
echo "IDS-IPS Dashboard - Local Setup Script"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}Creating .env file...${NC}"
    cp .env.example .env
    
    # Append ML configuration
    if [ -f .env.ml.example ]; then
        echo "" >> .env
        echo "# ML Configuration" >> .env
        cat .env.ml.example >> .env
    fi
    
    echo -e "${GREEN}✓ .env created${NC}"
else
    echo -e "${GREEN}✓ .env file exists${NC}"
fi

# Install Composer dependencies
echo -e "\n${YELLOW}Installing PHP dependencies...${NC}"
composer install --no-interaction
echo -e "${GREEN}✓ Composer dependencies installed${NC}"

# Install NPM dependencies
echo -e "\n${YELLOW}Installing Node dependencies...${NC}"
npm install
echo -e "${GREEN}✓ NPM dependencies installed${NC}"

# Generate application key
echo -e "\n${YELLOW}Generating application key...${NC}"
php artisan key:generate --ansi
echo -e "${GREEN}✓ Application key generated${NC}"

# Run database migrations
echo -e "\n${YELLOW}Running database migrations...${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Migrations completed${NC}"

# Seed database (optional)
read -p "Seed database with test data? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan db:seed
    echo -e "${GREEN}✓ Database seeded${NC}"
fi

# Create storage symlink
echo -e "\n${YELLOW}Creating storage symlink...${NC}"
php artisan storage:link
echo -e "${GREEN}✓ Storage symlink created${NC}"

# Clear caches
echo -e "\n${YELLOW}Clearing caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

# Build frontend assets
echo -e "\n${YELLOW}Building frontend assets...${NC}"
npm run build
echo -e "${GREEN}✓ Frontend assets built${NC}"

# Check Python and ML models
echo -e "\n${YELLOW}Checking Python environment...${NC}"
if command -v python &> /dev/null || command -v python3 &> /dev/null; then
    echo -e "${GREEN}✓ Python is installed${NC}"
    
    # Check if ML models exist
    if [ -f ml_models/random_forest_model.pkl ]; then
        echo -e "${GREEN}✓ ML models found${NC}"
    else
        echo -e "${YELLOW}⚠ ML models not found. Training models...${NC}"
        cd ml_scripts
        python quick_train_models.py || python3 quick_train_models.py
        cd ..
        echo -e "${GREEN}✓ ML models trained${NC}"
    fi
else
    echo -e "${RED}✗ Python not found. Please install Python 3.8+${NC}"
fi

echo ""
echo "========================================="
echo -e "${GREEN}Setup completed successfully!${NC}"
echo "========================================="
echo ""
echo "To start the application:"
echo ""
echo "  1. Start Laravel server:"
echo "     php artisan serve"
echo ""
echo "  2. Start queue worker (in another terminal):"
echo "     php artisan queue:work"
echo ""
echo "  3. Start frontend dev server (optional):"
echo "     npm run dev"
echo ""
echo "  4. Access the application:"
echo "     http://localhost:8000"
echo ""
echo "Default credentials:"
echo "  Email: admin@example.com"
echo "  Password: Check database seeder"
echo ""
echo "========================================="
