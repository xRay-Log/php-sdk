# XRay Log PHP SDK

XRay Log PHP SDK for logging and monitoring applications. This SDK provides a simple way to log various data types with type preservation.

<p align="center">
<a href="https://github.com/XRay-Log/php-sdk/actions"><img src="https://github.com/XRay-Log/php-sdk/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/xray-log/php-sdk"><img src="https://img.shields.io/packagist/dt/xray-log/php-sdk" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/xray-log/php-sdk"><img src="https://img.shields.io/packagist/v/xray-log/php-sdk" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/xray-log/php-sdk"><img src="https://img.shields.io/packagist/l/xray-log/php-sdk" alt="License"></a>
</p>

## Requirements

- PHP >= 7.1
- PHP curl extension
- PHP json extension
- symfony/var-dumper ^5.4

## Installation

You can install the package via composer:

```bash
composer require xray-log/php-sdk
```

## Usage

### Using Logger Class

```php
use XRayLog\XRayLogger;

// Initialize logger
$logger = new XRayLogger('Your Project Name');

// Log messages
$logger->info("User logged in");
$logger->error("Something went wrong");
$logger->debug(['user_id' => 1, 'status' => 'active']);
```

### Using Helper Function

```php
// Set project name (optional)
xray_setup(['project' => 'My Project']);

// Log with default INFO level
xray("Simple message");

// Log with specific level
xray('error', "Something went wrong");
xray('debug', ['user_id' => 1]);
```

## Testing

```bash
composer test
```

## License

The XRay Log PHP SDK is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).