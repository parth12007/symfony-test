# E-Commerce Management System

A Symfony-based web application for managing products with basic CRUD operations.

## Setup Instructions

1. Clone the repository
```bash
git clone <repository-url>
```

2. Install dependencies
```bash
composer install
```

3. Configure the database in `.env`
```bash
DATABASE_URL="mysql://db_user:db_password@localhost:3306/db_name"
```

4. Create database and run migrations
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Start the Symfony development server
```bash
symfony server:start
```

6. Open the application in your browser

The application should now be running at `http://localhost:8000`

## Running Tests

1. Configure the test database in `.env.test`
```bash
DATABASE_URL="mysql://db_user:db_password@localhost:3306/db_name_test"
```

2. Create the test database
```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
```

3. Run tests
```bash
# Run all tests
php bin/phpunit
```

## Static Analysis

Run PHP CS Fixer to ensure code style consistency:
```bash
# Check for code style issues
composer cs

# Fix code style issues
composer cs:fix
```

Run PHPStan for static code analysis:
```bash
# Run PHPStan
composer phpstan
```

## Features Implemented

- Product listing with pagination
- Create new products with validation
- Delete products
- Responsive design for mobile and desktop viewing
- Form validation for product data
- Flash messages for user feedback
- Basic styling with Bootstrap

## Design Decisions & Assumptions

- Used Symfony 6.x as the framework for robust MVC architecture
- Implemented Bootstrap for responsive design and consistent UI
- Chose Twig templating for server-side rendering
- Used Doctrine ORM for database operations
- Assumed product data structure with basic fields (title, description, price)
- Implemented simple validation rules for product data
- Used flash messages for user feedback on actions

### API Design
- RESTful API implementation following JSON:API specification
- Endpoints structured around resource-based URLs
- API versioning through URL prefixing (v1)
- Comprehensive error responses with appropriate HTTP status codes
- **Serialization**: 
  - Used Symfony Serializer component for transforming entities into JSON format.
  - Standardized response structure for success and error responses.

### Testing Strategy
- Implemented unit tests for core functionality
- Used PHPUnit for testing framework

### API Documentation
- OpenAPI/Swagger documentation for API endpoints
- Interactive API documentation at `/api/doc`
- Example requests and responses included
- Authentication flow documented
- Rate limiting details specified
- Error codes and responses documented

## Future Improvements (With More Time)

1. Authentication & Authorization
   - User login system
   - Role-based access control
   - User-specific product management

2. Enhanced Features
   - Product categories/tags
   - Product multiple image upload
   - Bulk operations
   - Product inventory management
   - Product rating and reviews
   - Product SEO optimization
   - Product export and import
   - Product analytics

3. Technical Improvements
   - Cache implementation
   - Better error handling
   - Input sanitization
   - Documentation using PHP Doc
   - Docker containerization
   - Admin dashboard for product management

4. UI/UX Improvements
   - Advanced filtering
   - Better mobile optimization
   - Rich text editor for product descriptions
   - Confirmation dialogs
   - Loading states
   - More detailed validation feedback

## Requirements

- PHP 8.1 or higher
- Composer
- MySQL/MariaDB
- Symfony CLI (optional, for development server)
