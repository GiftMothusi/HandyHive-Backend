# HandyHive Backend API

## Overview
HandyHive's backend API is built with Laravel 11, providing a robust and scalable service layer for connecting South African households with verified domestic service providers.

## Tech Stack
- Laravel 11
- MySQL/PostgreSQL
- Laravel Sanctum
- Laravel Echo
- Laravel Socialite
- Laravel Queue
- Laravel Cashier
- PHPUnit
- Laravel Horizon
- Laravel Telescope
- Laravel Nova

## Prerequisites
- PHP 8.2 or higher
- Composer 2.x
- MySQL 8.0 or PostgreSQL 14+
- Redis
- Git

## Installation

1. Clone the repository:
```bash
git clone git@github.com:your-organization/handyhive-backend.git
cd handyhive-backend
```

2. Install dependencies:
```bash
composer install
```

3. Set up environment variables:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Set up the database:
```bash
php artisan migrate
php artisan db:seed
```

6. Start the development server:
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## Development

### Available Commands
- `php artisan serve` - Start development server
- `php artisan test` - Run tests
- `php artisan migrate` - Run database migrations
- `php artisan queue:work` - Start queue worker
- `php artisan horizon` - Start Horizon
- `php artisan telescope` - Access Telescope dashboard

### Required Services
- Redis (for caching and queues)
- MySQL/PostgreSQL (for database)
- Pusher (for real-time features)

## Testing
We maintain a minimum of 90% test coverage. Run tests with:
```bash
php artisan test --coverage
```

## Project Structure
```
app/
├── Console/          # Console commands
├── Exceptions/       # Exception handlers
├── Http/
│   ├── Controllers/ # API controllers
│   ├── Middleware/  # HTTP middleware
│   └── Requests/    # Form requests
├── Models/          # Eloquent models
├── Providers/       # Service providers
├── Services/        # Business logic
├── Repositories/    # Data access layer
└── Jobs/           # Queue jobs
```

## API Documentation
API documentation is available at `/api/documentation` when the application is running.

### API Versioning
All API endpoints are versioned and prefixed with `/api/v1/`

### Authentication
We use Laravel Sanctum for API authentication. Include the authentication token in the Authorization header:
```
Authorization: Bearer your-token-here
```

## Performance Requirements
- API response time: < 200ms
- Real-time updates: < 500ms latency
- Concurrent users: 20,000+
- 99.95% uptime

## Security
- POPI Act compliant
- Data encryption: AES-256 for data at rest
- TLS 1.3 for data in transit
- Regular security audits
- Rate limiting and DDOS protection

## Contributing

1. Create a feature branch from `develop`:
```bash
git checkout -b feature/your-feature-name
```

2. Commit your changes:
```bash
git commit -m "feat: add your feature"
```

3. Push to your branch:
```bash
git push origin feature/your-feature-name
```

4. Create a Pull Request to `develop`

### Branch Naming Convention
- Feature: `feature/descriptive-name`
- Bugfix: `bugfix/descriptive-name`
- Hotfix: `hotfix/descriptive-name`

### Code Style
We follow PSR-12 coding standards. Run code style checks:
```bash
./vendor/bin/phpcs
```

### Commit Message Convention
We follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:
- feat: New feature
- fix: Bug fix
- docs: Documentation changes
- style: Code style changes
- refactor: Code refactoring
- test: Test changes
- chore: Build process or auxiliary tool changes

## Deployment
The application is configured for deployment on Digital Ocean/AWS:
1. Set up server environment
2. Configure environment variables
3. Run migrations
4. Set up queue workers
5. Configure supervisor
6. Set up monitoring

## Monitoring
- Laravel Telescope for local debugging
- Laravel Horizon for queue monitoring
- NewRelic for performance monitoring
- CloudWatch for logs
- Sentry for error tracking

## Documentation
- [API Documentation](./docs/api.md)
- [Database Schema](./docs/database.md)
- [Queue System](./docs/queues.md)
- [Security Guidelines](./docs/security.md)
- [Testing Guide](./docs/testing.md)

## Support
For support, contact the development team at [dev@handyhive.co.za](mailto:dev@handyhive.co.za)

## License
[MIT License](./LICENSE)
