<div align="center">
    <img src="public/images/channel_lister.png" alt="Channel Lister Logo" width="200">
    <h1>Channel Lister</h1>
    <p><em>Streamlined Multi-Channel eCommerce Product Listing Tool</em></p>
</div>

---

**Channel Lister** is a modern Laravel package that simplifies multi-channel eCommerce product listing management. Evolved from the Channel Advisor Master Lister (CAML), it provides a guided product creation experience with dynamic marketplace integration, featuring Amazon SP-API support for real-time listing requirements and automated CSV/JSON export generation.

## ✨ Key Features

- 🛒 **Multi-Channel Support** - Create listings for multiple eCommerce marketplaces
- 🔄 **Dynamic Forms** - Auto-generated forms based on real-time marketplace requirements
- 📊 **Amazon SP-API Integration** - Native integration with Amazon's Selling Partner API
- 📝 **Flexible Field System** - Custom and marketplace-specific field definitions
- 📋 **Smart Validation** - Real-time validation with progress tracking
- 📄 **Multiple Export Formats** - CSV and JSON export for various platforms
- 🔍 **Existing Listing Lookup** - Pre-populate forms from existing marketplace data
- 📦 **Shipping Cost Calculator** - Real-time shipping cost calculations with ShipEngine API
- ⚡ **Modern Stack** - Built with Laravel, Pest testing, and modern PHP 8.3+

> **Requires [PHP 8.3+](https://php.net/releases/)**

## 🚀 Quick Start

Install via Composer:

```bash
composer require ige/channel-lister
```

### ⚡ Automatic Installation (Recommended)

Use the install command for automatic setup:

```bash
php artisan channel-lister:install
```

This command will:
- 📦 Publish the service provider 
- 🎨 Publish package assets
- ⚙️ Publish configuration files
- 🗄️ Publish database migrations
- 🔧 Automatically register the service provider (supports Laravel 8/9/10/11+)

After installation, run the migrations:

```bash
php artisan migrate
```

### 🛠️ Manual Installation (Alternative)

Alternatively, you can install manually by publishing the package assets and configuration:

```bash
php artisan vendor:publish --provider="IGE\ChannelLister\ChannelListerServiceProvider"
```

Then run the migrations:

```bash
php artisan migrate
```

## 🛠️ Development Commands

### Code Quality & Testing

🚀 **Run the entire test suite:**

```bash
composer test
```

✅ **Run unit tests using PEST:**

```bash
composer test:unit
```

⚗️ **Run static analysis using PHPStan:**

```bash
composer test:types
```

### Code Formatting & Refactoring

🧹 **Format code with Laravel Pint:**

```bash
composer lint
```

✅ **Refactor code using Rector:**

```bash
composer refacto
```

### Development Server

🌐 **Launch development server:**

```bash
composer serve
```

🗑️ **Clear caches:**

```bash
composer clear
```

📦 **Build assets:**

```bash
composer build
```

## 📊 Amazon SP-API Integration

Channel Lister features comprehensive Amazon Selling Partner API (SP-API) integration for dynamic listing creation. This powerful feature enables:

- Search for Amazon product types
- Fetch dynamic listing requirements from Amazon
- Look up existing listings by identifier (ASIN, GTIN, UPC, EAN, ISBN)
- Generate marketplace-specific forms dynamically

### ⚙️ Configuration

Add the following environment variables to your `.env` file:

```env
# Amazon SP-API Configuration
AMAZON_SP_API_BASE_URL=https://sellingpartnerapi-na.amazon.com
AMAZON_MARKETPLACE_ID=ATVPDKIKX0DER
AMAZON_SP_API_REGION=us-east-1
AMAZON_SP_API_CLIENT_ID=your_client_id_here
AMAZON_SP_API_CLIENT_SECRET=your_client_secret_here
AMAZON_SP_API_REFRESH_TOKEN=your_refresh_token_here

# Shipping Calculator (Optional)
CHANNEL_LISTER_SHIPSTATION_API_KEY=your_shipengine_api_key_here
CHANNEL_LISTER_SHIPSTATION_BASE_URL=https://api.shipengine.com/v1
```

**Note**: The system now uses OAuth 2.0 with automatic token refresh. You only need to provide your client credentials and refresh token - access tokens are managed automatically.

### 📋 Usage

1. **Product Type Search**: Use the Amazon product type search component to find and select the appropriate product type for your listing.

2. **Dynamic Form Generation**: Once a product type is selected, the system will fetch the listing requirements from Amazon SP-API and generate the appropriate form fields.

3. **Existing Listing Lookup**: Enter an identifier (ASIN, GTIN, etc.) to look up and pre-populate form fields from an existing Amazon listing.

### 🔗 API Endpoints

**Product Type & Requirements:**

- `POST /api/amazon-listing/search-product-types` - Search for Amazon product types
- `POST /api/amazon-listing/listing-requirements` - Get listing requirements for a product type
- `POST /api/amazon-listing/existing-listing` - Look up existing listing by identifier

**Form Submission & Management:**

- `POST /api/amazon-listing/submit` - Submit listing form data
- `POST /api/amazon-listing/validate` - Validate a listing
- `POST /api/amazon-listing/generate-file` - Generate CSV/JSON files
- `GET /api/amazon-listing/listings` - Get all listings with pagination
- `GET /api/amazon-listing/listings/{id}` - Get specific listing details
- `GET /api/amazon-listing/listings/{id}/download` - Download generated files

### 🔐 Token Management

The system includes robust SP-API token management with:

- **Automatic token refresh** - Tokens are refreshed automatically before expiration
- **Caching** - Valid tokens are cached to reduce API calls
- **Error handling** - Comprehensive error handling for authentication failures
- **Rate limiting** - Built-in protection against API rate limits
- **Debugging** - Console command for checking token status

#### Debug Token Status

Use the console command to check your Amazon SP-API token status:

```bash
php artisan channel-lister:amazon-token-status

# Force refresh token
php artisan channel-lister:amazon-token-status --refresh
```

#### Authentication Flow

1. **Initial Setup**: Configure your client credentials and refresh token
2. **Automatic Management**: The system automatically obtains and refreshes access tokens
3. **Request Authentication**: All SP-API calls are automatically authenticated
4. **Error Recovery**: Failed authentication attempts trigger automatic token refresh

### 📝 Form Submission Workflow

The system provides a complete workflow for creating Amazon listings:

#### 1. **Product Type Selection**

- Search and select appropriate Amazon product type
- System fetches dynamic listing requirements from SP-API
- Form fields are generated based on Amazon's current requirements

#### 2. **Form Completion**

- Fill out dynamically generated form fields
- Real-time validation feedback
- Progress tracking showing completion percentage
- Support for existing listing lookup and pre-population

#### 3. **Validation & Processing**

- **Save Draft**: Store form data without validation
- **Validate**: Comprehensive validation against Amazon requirements
- **Business Rules**: SKU uniqueness, price validation, dimension consistency
- **Required Fields**: Automatic detection of missing required fields

#### 4. **File Generation**

- **CSV Export**: Amazon-compatible CSV format for bulk upload
- **JSON Export**: Structured data for API integration
- **Field Mapping**: Automatic mapping to Amazon feed column names
- **Data Transformation**: Amazon-specific formatting (prices, dimensions, etc.)

#### 5. **Download & Submission**

- Secure file downloads with proper MIME types
- Files ready for Amazon Seller Central upload
- Persistent storage with listing history

### ✅ Validation Features

- **Amazon Requirements**: Dynamic validation based on current SP-API requirements
- **Field Types**: String, numeric, boolean, enum validation
- **Constraints**: Length limits, regex patterns, value ranges
- **Business Logic**: SKU uniqueness, price consistency, dimension validation
- **Real-time Feedback**: Instant validation results and progress tracking

---

## 📦 Shipping Cost Calculator

Channel Lister includes a powerful shipping cost calculator that integrates with ShipEngine API to provide real-time shipping rates from major carriers (UPS, FedEx, USPS). This feature helps sellers accurately estimate shipping costs for their products, improving pricing decisions and customer experience.

### ✨ Features

- **Real-time Rate Shopping** - Get live rates from multiple carriers simultaneously
- **Dimensional Weight Calculation** - Automatic calculation for different carrier divisors
- **IP Geolocation** - Detect origin location automatically from user's IP
- **Manual Entry Fallback** - Enter shipping costs manually when API is unavailable
- **Smart Integration** - Seamlessly integrated into cost-related form fields
- **Carrier Comparison** - Compare rates across UPS, FedEx, and USPS
- **Auto-fill Dimensions** - Pull package dimensions from existing form fields

### ⚙️ Configuration

Add the following environment variables to your `.env` file:

```env
# ShipEngine API Configuration
CHANNEL_LISTER_SHIPSTATION_API_KEY=your_shipengine_api_key_here
CHANNEL_LISTER_SHIPSTATION_BASE_URL=https://api.shipengine.com/v1
```

**Note**: The system gracefully handles missing API keys by providing manual entry options for shipping costs. ShipEngine API usage incurs costs based on your plan - see [ShipEngine pricing](https://www.shipengine.com/pricing/) for details.

### 📋 Usage

The shipping calculator appears as an integrated button within cost-related form fields:

1. **Automatic Detection**: Click "Calculate Shipping" button on any shipping cost field
2. **Location Setup**: System detects your location via IP or enter ZIP codes manually
3. **Package Details**: Enter dimensions (length, width, height) and weight
4. **Auto-fill**: Use existing form data to populate dimensions automatically
5. **Rate Comparison**: View rates from multiple carriers sorted by price
6. **Selection**: Click desired rate to populate the form field

### 🔗 API Endpoints

**Shipping Calculator Endpoints:**

- `GET /api/shipping/check-api` - Check if ShipEngine API key is available
- `GET /api/shipping/location` - Get user location from IP address
- `POST /api/shipping/calculate` - Calculate shipping rates for package
- `GET /api/shipping/carriers` - Get available carriers from ShipEngine
- `POST /api/shipping/dimensional-weight` - Calculate dimensional weight only

### 📊 Dimensional Weight Calculation

The system automatically calculates dimensional weight using carrier-specific divisors:

- **UPS Commercial**: 139 cubic inches per pound
- **FedEx**: 139 cubic inches per pound
- **USPS**: 166 cubic inches per pound

**Billable Weight**: The greater of actual weight or dimensional weight is used for rate calculation.

### 🎯 Integration Details

The shipping calculator integrates seamlessly with the form system:

- **Field Detection**: Automatically appears on fields with "shipping", "cost", or similar names
- **Modal Interface**: Clean, user-friendly modal with step-by-step workflow
- **Responsive Design**: Works on desktop and mobile devices
- **Error Handling**: Graceful fallbacks when API is unavailable
- **Caching**: Efficient rate caching to minimize API calls

### 💡 Manual Entry Mode

When ShipEngine API is not configured or unavailable:

- System automatically switches to manual entry mode
- Users can enter estimated shipping costs directly
- All functionality remains available without API dependency
- Clear messaging indicates manual entry is required

### 🔧 Development & Testing

The shipping calculator includes comprehensive test coverage and follows Laravel best practices:

- **Service Layer**: Clean separation of concerns with `ShippingCalculatorService`
- **API Integration**: Robust HTTP client with error handling
- **Form Components**: Reusable Blade components with JavaScript integration
- **Validation**: Input validation for dimensions, ZIP codes, and weights
- **Testing**: Full test suite covering happy paths and error conditions

---

## 🔧 Form Field Configuration & Customization

Channel Lister provides a powerful and flexible form field system that allows you to fully customize the listing creation experience. You can manage fields for different marketplaces, add new marketplaces, and customize field behavior through the ChannelListerField model and management interface.

### ⚡ Quick Setup with Default Fields

Use the seeding command to populate your database with pre-configured fields for ChannelAdvisor/Rithum and Amazon:

```bash
php artisan channel-lister:seed-fields
```

**Command Options:**
- `--force` - Force seeding even if fields already exist (overwrites existing fields)

**What this command does:**
- 📦 Seeds 27 default fields for common marketplace operations
- 🏪 Includes fields for ChannelAdvisor/Rithum integration
- 🛒 Includes Amazon-specific product type fields
- ⚙️ Sets up proper field ordering, validation, and grouping
- 🔄 Handles different input types (text, select, currency, etc.)

### 🎛️ Field Management Interface

Access the field management interface via the `channel-lister-field` routes:

**Available Routes:**
```php
// Web interface for field management
Route::resource('/channel-lister-field', ChannelListerFieldController::class)
```

**Management Operations:**
- **📋 List Fields** - `/channel-lister-field` - View all fields with filtering by marketplace
- **➕ Create Field** - `/channel-lister-field/create` - Add new custom fields
- **👁️ View Field** - `/channel-lister-field/{id}` - View field details
- **✏️ Edit Field** - `/channel-lister-field/{id}/edit` - Modify existing fields
- **🗑️ Delete Field** - `/channel-lister-field/{id}` - Remove fields (with confirmation)

### 📊 ChannelListerField Model

The `ChannelListerField` model is the core of the field system:

#### **Model Attributes:**
```php
$field = new ChannelListerField([
    'ordering' => 1,                    // Display order (integer)
    'field_name' => 'Product Title',    // Internal field name
    'display_name' => 'Title',          // User-facing label
    'tooltip' => 'Enter product title', // Help text
    'example' => 'Blue Widget Pro',     // Example value
    'marketplace' => 'amazon',          // Target marketplace
    'input_type' => 'text',            // Field input type
    'input_type_aux' => null,          // Additional input options
    'required' => true,                // Whether field is required
    'grouping' => 'Product Details',   // Form section grouping
    'type' => 'custom',               // Field type (custom/channeladvisor)
]);
```

#### **Available Input Types:**
- **`text`** - Standard text input
- **`textarea`** - Multi-line text input
- **`select`** - Dropdown selection (uses `input_type_aux` for options)
- **`checkbox`** - Boolean checkbox
- **`currency`** - Currency input with formatting
- **`decimal`** - Decimal number input
- **`integer`** - Whole number input
- **`url`** - URL input with validation
- **`comma-separated`** - Multi-value comma-separated input
- **`custom`** - Special custom components (UPC generator, shipping calculator, etc.)
- **`alert`** - Display informational alerts

#### **Field Types:**
- **`custom`** - User-defined fields for specific business needs
- **`channeladvisor`** - Fields designed for ChannelAdvisor/Rithum integration

#### **Useful Scopes:**
```php
// Get fields for specific marketplace
ChannelListerField::forMarketplace('amazon')->get();

// Get only required fields
ChannelListerField::required()->get();

// Get fields by grouping
ChannelListerField::byGrouping('Product Details')->get();

// Get ordered fields
ChannelListerField::ordered()->get(); // ascending
ChannelListerField::ordered('desc')->get(); // descending

// Chain scopes for complex queries
ChannelListerField::forMarketplace('ebay')
    ->required()
    ->ordered()
    ->get();
```

### 🛒 Adding New Marketplaces

You can easily add support for new marketplaces by creating fields with custom marketplace keys:

```php
// Add fields for a new marketplace (e.g., 'etsy')
ChannelListerField::create([
    'ordering' => 1,
    'field_name' => 'Etsy Category',
    'display_name' => 'Product Category',
    'marketplace' => 'etsy',           // New marketplace key
    'input_type' => 'select',
    'input_type_aux' => 'Clothing||Electronics||Home & Garden',
    'required' => true,
    'grouping' => 'Etsy Specific',
    'type' => 'custom',
]);
```

**Marketplace Display Names:**

The system automatically generates display names for marketplaces using the `ChannelLister::marketplaceDisplayName()` method:
- `amazon` → "Amazon"
- `ebay` → "eBay" 
- `walmart` → "Walmart"
- `custom-marketplace` → "Custom Marketplace"

### 🔧 Advanced Field Configuration

#### **Select Field Options:**
For select fields, use the `input_type_aux` field to define options:

```php
// Simple options
'input_type_aux' => 'Small||Medium||Large'

// Key-value pairs
'input_type_aux' => 'sm==Small||md==Medium||lg==Large'

// From code
$field->input_type_aux = ['Option 1', 'Option 2', 'Option 3'];
```

#### **Custom Field Components:**
Special custom components are available via the `custom` input type:

```php
// UPC generator component  
'input_type' => 'custom',
'input_type_aux' => '', // UPC generation logic

// Amazon product type search
'input_type' => 'custom', 
'input_type_aux' => 'amazon-product-type-search',

// Shipping cost calculator
'field_name' => 'Cost Shipping',
'input_type' => 'custom', // Automatically triggers shipping calculator
```

#### **Validation Patterns:**
Use `input_type_aux` for regex validation on text fields:

```php
'input_type' => 'text',
'input_type_aux' => '^[A-Z0-9-]{5,20}$', // SKU format validation
```

### 🎯 Practical Examples

#### **Example 1: Adding Shopify Support**
```php
// Create Shopify-specific fields
$fields = [
    [
        'ordering' => 1,
        'field_name' => 'shopify_handle',
        'display_name' => 'URL Handle', 
        'marketplace' => 'shopify',
        'input_type' => 'text',
        'required' => true,
        'grouping' => 'Shopify Settings',
        'type' => 'custom',
    ],
    [
        'ordering' => 2,
        'field_name' => 'shopify_tags',
        'display_name' => 'Product Tags',
        'marketplace' => 'shopify', 
        'input_type' => 'comma-separated',
        'required' => false,
        'grouping' => 'Shopify Settings',
        'type' => 'custom',
    ],
];

foreach ($fields as $field) {
    ChannelListerField::create($field);
}
```

#### **Example 2: Custom Validation Field**
```php
ChannelListerField::create([
    'ordering' => 10,
    'field_name' => 'model_number',
    'display_name' => 'Model Number',
    'tooltip' => 'Enter manufacturer model number (letters, numbers, hyphens only)',
    'example' => 'ABC-123-XYZ',
    'marketplace' => 'common',
    'input_type' => 'text',
    'input_type_aux' => '^[A-Z0-9-]{3,50}$', // Validation regex
    'required' => true,
    'grouping' => 'Product Information',
    'type' => 'custom',
]);
```

#### **Example 3: Dynamic Select Field**
```php
ChannelListerField::create([
    'ordering' => 5,
    'field_name' => 'condition',
    'display_name' => 'Product Condition',
    'marketplace' => 'common',
    'input_type' => 'select',
    'input_type_aux' => 'new==New||used-like-new==Used - Like New||used-good==Used - Good||used-acceptable==Used - Acceptable',
    'required' => true,
    'grouping' => 'Product Details',
    'type' => 'custom',
]);
```

### 🔍 Field Management Best Practices

1. **📋 Consistent Ordering** - Use meaningful ordering values (10, 20, 30) to allow easy insertion
2. **🏷️ Clear Naming** - Use descriptive `field_name` and `display_name` values
3. **📝 Helpful Tooltips** - Provide context and examples for complex fields
4. **🎯 Logical Grouping** - Group related fields together for better UX
5. **✅ Proper Validation** - Use appropriate input types and validation patterns
6. **🌍 Marketplace Specific** - Use marketplace keys consistently across your application

### 🔧 Programmatic Field Management

```php
// Create fields programmatically
$marketplaceFields = [
    'tiktok' => [
        ['field_name' => 'tiktok_category', 'display_name' => 'TikTok Category'],
        ['field_name' => 'tiktok_hashtags', 'display_name' => 'Hashtags'],
    ]
];

foreach ($marketplaceFields as $marketplace => $fields) {
    foreach ($fields as $index => $fieldData) {
        ChannelListerField::create(array_merge($fieldData, [
            'marketplace' => $marketplace,
            'ordering' => ($index + 1) * 10,
            'input_type' => 'text',
            'required' => false,
            'grouping' => ucfirst($marketplace) . ' Settings',
            'type' => 'custom',
        ]));
    }
}
```

This flexible field system allows you to adapt Channel Lister to any marketplace or business requirement while maintaining a consistent, user-friendly interface.

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Credits

- Originally evolved from the Channel Advisor Master Lister (CAML)
- Built with [Laravel](https://laravel.com) framework
- Testing powered by [Pest PHP](https://pestphp.com)
- Package structure based on [Laravel Package Skeleton](https://github.com/spatie/package-skeleton-laravel) by [Spatie](https://spatie.be)

## 📞 Support

For support, issues, or feature requests, please [open an issue](../../issues) on GitHub.
