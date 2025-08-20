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
- ⚡ **Modern Stack** - Built with Laravel, Pest testing, and modern PHP 8.3+

> **Requires [PHP 8.3+](https://php.net/releases/)**

## 🚀 Quick Start

Install via Composer:

```bash
composer require ige/channel-lister
```

Publish the package assets and configuration:

```bash
php artisan vendor:publish --provider="IGE\ChannelLister\ChannelListerServiceProvider"
```

Run the migrations:

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

## 📄 License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Credits

- Originally evolved from the Channel Advisor Master Lister (CAML)
- Built with [Laravel](https://laravel.com) framework
- Testing powered by [Pest PHP](https://pestphp.com)
- Package structure based on [Laravel Package Skeleton](https://github.com/spatie/package-skeleton-laravel) by [Spatie](https://spatie.be)

## 📞 Support

For support, issues, or feature requests, please [open an issue](../../issues) on GitHub.
