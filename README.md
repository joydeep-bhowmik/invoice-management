# Invoice Management System

A comprehensive invoice management application built with Laravel, Livewire, and Tailwind CSS. This system allows businesses to manage invoices, products, warehouses, and company settings efficiently.

## Features

### Invoice Management
- Create and edit invoices with detailed seller and client information
- Automatic invoice numbering with customizable series and sequences
- Support for multiple currencies (default: EUR)
- Tax calculations and custom charges
- Invoice status tracking (paid/unpaid)
- PDF generation and download
- Barcode generation for invoices

### Product Management
- Manage product catalog
- Track product details and pricing

### Warehouse Management
- Organize inventory across multiple warehouses
- Track stock levels and locations

### Company Management
- Multi-company support
- Company profile and settings
- User roles and permissions

### User Management
- User authentication with Laravel Fortify
- Two-factor authentication
- Profile management
- Password reset functionality

### Additional Features
- Notes system for additional documentation
- Media library for file attachments
- Responsive design with Tailwind CSS
- Real-time updates with Livewire

## Requirements

- PHP ^8.2
- Composer
- Node.js & npm
- MySQL/PostgreSQL database

## Installation

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd invoice-management
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies:**
   ```bash
   npm install
   ```

4. **Environment Setup:**
   ```bash
   cp .env.example .env
   ```

   Update the `.env` file with your database credentials and other configuration settings.

5. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

6. **Run database migrations and seeders:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

   Or for a fresh installation with seeders:
   ```bash
   php artisan migrate:fresh --seed
   ```

## Running the Application

### Development Mode
1. **Start the development server:**
   ```bash
   php artisan serve
   ```

2. **Compile assets for development:**
   ```bash
   npm run dev
   ```

   The application will be available at `http://localhost:8000`

### Production Build
1. **Build assets for production:**
   ```bash
   npm run build
   ```

2. **Configure your web server** to serve the `public` directory.

## Using Laravel Sail (Docker)

If you prefer using Docker, this project includes Laravel Sail:

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Start Sail:**
   ```bash
   ./vendor/bin/sail up
   ```

3. **Run migrations:**
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

## Configuration

### Invoice Settings
Configure invoice settings in `config/invoices.php`:
- Date formats
- Payment due dates
- Serial number formatting
- Currency settings

### Permissions
The application uses Spatie Laravel Permission package for role-based access control.

## Testing

Run the test suite with Pest:
```bash
./vendor/bin/pest
```

## License

This project is licensed under the MIT License.