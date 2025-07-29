# 🛒 Cart Seeder Plugin for Shopware 6

A powerful development tool for generating realistic fake customers and shopping carts in Shopware 6.

![Shopware 6](https://img.shields.io/badge/Shopware-6.6-blue?logo=shopware)
![Plugin Version](https://img.shields.io/badge/Version-1.0.0-green)
![License](https://img.shields.io/badge/License-MIT-blue)
![PHP](https://img.shields.io/badge/PHP-8.3-purple)

---

## 🎯 Purpose

This plugin is designed **exclusively for development and testing environments**. It provides developers with:

- 🧑‍🤝‍🧑 **Realistic Customer Data**: Generate customers with proper addresses, payment methods, and profiles
- 🛍️ **Populated Shopping Carts**: Create carts with random products and configurable item counts
- ⏰ **Aged Cart Simulation**: Make carts appear older for testing abandoned cart scenarios
- 🧹 **Easy Cleanup**: All generated data is marked for simple identification and removal
- 📊 **Batch Processing**: Generate hundreds or thousands of records with progress tracking

---

## ⚠️ Important Warning

**🚨 FOR DEVELOPMENT USE ONLY - NEVER USE IN PRODUCTION! 🚨**

This plugin creates fake customer data and should only be used in development/testing environments.

---

## 🚀 Installation

### Step 0: Get the Plugin Code

Clone this repository into your Shopware installation's `custom/plugins` directory:

```bash
cd /path/to/your/shopware/custom/plugins
git clone <repository-url> shopware-6-cart-seeder
```

Or download and extract the plugin into `custom/plugins/shopware-6-cart-seeder`.

### Step 1: Install Dependencies

```bash
# Navigate to your Shopware root directory
cd /path/to/your/shopware

# Install Faker library (required dependency)
composer require fakerphp/faker
```

### Step 2: Activate Plugin

```bash
# Refresh plugin list
bin/console plugin:refresh

# Install and activate the plugin
bin/console plugin:install --activate MaxSeeligCartSeeder

# Clear cache
bin/console cache:clear
```

---

## 📋 Usage

### Basic Commands

```bash
# Generate default amounts (50 customers, 100 carts)
bin/console cart-seeder:seed

# Generate custom amounts
bin/console cart-seeder:seed --customers=100 --carts=200

# Control cart contents
bin/console cart-seeder:seed --min-items=2 --max-items=10

# Clean existing test data first
bin/console cart-seeder:seed --clean

# Complete example with all options
bin/console cart-seeder:seed \
  --customers=500 \
  --carts=1000 \
  --min-items=1 \
  --max-items=8 \
  --clean
```

### Command Options

| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--customers` | `-c` | `50` | Number of fake customers to create |
| `--carts` | | `100` | Number of fake carts to create |
| `--min-items` | | `1` | Minimum items per cart |
| `--max-items` | | `5` | Maximum items per cart |
| `--clean` | | `false` | Remove existing seeded data before generating new data |

---

## 🔧 Features

### 👥 Realistic Customer Generation

- **Names & Demographics**: Uses Faker to generate realistic first/last names
- **Contact Information**: Unique email addresses and phone numbers
- **Addresses**: Complete shipping/billing addresses with real-looking street names, cities, and postal codes
- **Account Setup**: Proper customer groups, payment methods, and salutations
- **Identification**: All customers get `SEED-` prefixed customer numbers for easy cleanup

### 🛍️ Smart Cart Creation

- **Product Selection**: Randomly selects products from your existing catalog
- **Configurable Contents**: Control minimum and maximum items per cart
- **Realistic Quantities**: Random quantities (1-3) per line item
- **Aged Carts**: Carts are backdated randomly (1-7 days) to simulate real usage patterns
- **Customer Assignment**: Each cart is linked to a generated customer

### 📊 Developer-Friendly Features

- **Progress Tracking**: Visual progress bars for large datasets
- **Error Handling**: Graceful error handling with detailed error messages
- **Memory Efficient**: Processes data in batches to avoid memory issues
- **Safe Cleanup**: Easy identification and removal of test data

---

## 🧹 Data Management

### Cleanup Generated Data

```bash
# Remove all seeded customers and carts
bin/console cart-seeder:seed --clean --customers=0 --carts=0

# Or generate fresh data (cleans old data first)
bin/console cart-seeder:seed --clean --customers=100 --carts=200
```

### Data Identification

- **Customers**: All generated customers have customer numbers starting with `SEED-`
- **Carts**: Generated carts can be identified by their creation dates and customer associations
- **Database Safe**: The cleanup process only removes data created by this plugin

---

## 📝 Example Output

```
Cart Seeder - Development Tool
==============================

Cleaning existing seeded data...
✅ Cleaned 45 customers and 89 carts

Generating fake data...
-----------------------

 100/100 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%
Created 100 fake customers

 200/200 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%
Created 200 fake carts

✅ Successfully created 100 customers and 200 carts!
```

---

## 🛠️ Technical Details

### Requirements

- **Shopware**: 6.6.*
- **PHP**: 8.3
- **Dependencies**: `fakerphp/faker` library
- **Extensions**: `ext-json`

### Plugin Structure

```
src/
├── MaxSeeligCartSeeder.php          # Main plugin class
├── Command/
│   └── SeedCartsCommand.php         # Console command implementation
├── Service/
│   └── CartSeederService.php        # Core seeding logic
└── Resources/
    └── config/
        └── services.xml             # Dependency injection configuration
```

### Performance Considerations

- **Memory Usage**: For large datasets (1000+ records), ensure adequate PHP memory limit
- **Processing Time**: Generation time scales with the number of products in your catalog
- **Database Load**: Consider running during off-peak hours for very large datasets

---

## 🚀 Use Cases

### Development Scenarios

- **Feature Testing**: Test cart-related functionality with realistic data
- **UI/UX Testing**: Populate interfaces with varied cart contents
- **Performance Testing**: Load test shopping cart operations
- **Demo Preparation**: Create convincing demo data for presentations

### Testing Scenarios

- **Abandoned Cart Recovery**: Test email campaigns and recovery flows
- **Customer Segmentation**: Test customer grouping and targeting features
- **Checkout Flows**: Test various cart configurations through checkout
- **Analytics**: Generate data for testing reporting and analytics features

---

## 📊 Best Practices

### Recommended Usage

```bash
# For development work (small dataset)
bin/console cart-seeder:seed --customers=25 --carts=50

# For feature testing (medium dataset)
bin/console cart-seeder:seed --customers=100 --carts=200

# For load testing (large dataset)
bin/console cart-seeder:seed --customers=500 --carts=1000

# For abandoned cart testing (varied cart ages)
bin/console cart-seeder:seed --customers=50 --carts=150 --min-items=1 --max-items=10
```

### Environment Setup

1. **Use a dedicated development database**
2. **Ensure you have sample products in your catalog**
3. **Set up proper customer groups and payment methods**
4. **Configure adequate PHP memory and execution time limits**

---

## 🤝 Contributing

Yes! Issues and pull requests are welcome.

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🔗 Related Resources

- [Shopware 6 Documentation](https://docs.shopware.com/)
- [Faker PHP Documentation](https://fakerphp.github.io/)
- [Shopware Plugin Development](https://developer.shopware.com/docs/guides/plugins/)

---

**⚠️ Remember: This plugin is for development purposes only. Always use a separate development environment and never run this on production data!**
