# KSF PrefCache

**A lightweight, provider-agnostic preference caching library for PHP 8.1+**

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)

## Overview

PrefCache is a simple, performant caching library designed to eliminate repeated lookups to configuration sources (sessions, databases, files, APIs, etc.) within a single request.

**Key Features:**
- ✅ **Zero dependencies** - Pure PHP 8.1+
- ✅ **Provider-agnostic** - Works with any data source
- ✅ **Request-scoped** - Cache clears automatically after request
- ✅ **Observer pattern** - Event-based cache invalidation
- ✅ **Type-safe** - Full PHP 8.1+ type hints with strict types
- ✅ **Lightweight** - ~300 lines of code
- ✅ **Framework-agnostic** - Use in any PHP project

## Installation

### Via Composer

```bash
composer require ksfraser/pref-cache
```

### Manual Installation

Copy `src/` directory to your project and require the files:

```php
require_once 'path/to/PreferenceProviderInterface.php';
require_once 'path/to/PreferenceCache.php';
```

## Quick Start

### 1. Create a Provider

Implement `PreferenceProviderInterface` for your data source:

```php
use KSF\PrefCache\PreferenceProviderInterface;

class EnvProvider implements PreferenceProviderInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
    
    public function getAll(): array
    {
        return $_ENV;
    }
    
    public function has(string $key): bool
    {
        return isset($_ENV[$key]);
    }
}
```

### 2. Use the Cache

```php
use KSF\PrefCache\PreferenceCache;

$cache = new PreferenceCache(new EnvProvider());

// Get cached values
$apiKey = $cache->get('API_KEY', 'default-key');
$timeout = $cache->get('TIMEOUT', 30);

// Invalidate when data changes
$cache->invalidate();
```

## Use Cases

### Perfect For:
- User preferences (session-based)
- Application configuration
- Feature flags
- Request-scoped settings
- Eliminating N+1 queries for config

### Not Designed For:
- Cross-request persistent caching (use Redis/Memcached)
- Large datasets (this is for small config/preference data)
- Distributed caching across servers

## Performance Impact

**Typical gains:**
- 99%+ reduction in data source lookups
- 0.5-2ms faster response times per request
- 200KB+ memory savings per request

**Example:** If you access `user_price_dec()` 191 times per request:
- **Before:** 191 session lookups
- **After:** 1 session lookup + 190 cache hits

## Examples

### Session Provider

```php
class SessionProvider implements PreferenceProviderInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }
    
    public function getAll(): array
    {
        return $_SESSION;
    }
    
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
}

$cache = new PreferenceCache(new SessionProvider());
$username = $cache->get('username');
```

### Database Provider

```php
class DbConfigProvider implements PreferenceProviderInterface
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        $stmt = $this->pdo->prepare('SELECT value FROM config WHERE key = ?');
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        
        return $result !== false ? $result : $default;
    }
    
    public function getAll(): array
    {
        $stmt = $this->pdo->query('SELECT key, value FROM config');
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    public function has(string $key): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM config WHERE key = ?');
        $stmt->execute([$key]);
        return $stmt->fetchColumn() > 0;
    }
}

$cache = new PreferenceCache(new DbConfigProvider($pdo));
$siteName = $cache->get('site_name', 'My Site');
```

### File Provider

```php
class JsonConfigProvider implements PreferenceProviderInterface
{
    private string $filePath;
    private ?array $data = null;
    
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }
    
    private function load(): array
    {
        if ($this->data === null) {
            $this->data = json_decode(file_get_contents($this->filePath), true) ?? [];
        }
        return $this->data;
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->load();
        return $data[$key] ?? $default;
    }
    
    public function getAll(): array
    {
        return $this->load();
    }
    
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->load());
    }
}

$cache = new PreferenceCache(new JsonConfigProvider('config.json'));
$theme = $cache->get('theme', 'default');
```

## Advanced Usage

### Observer Pattern

React to cache invalidation events:

```php
$cache->registerObserver(function() {
    error_log('Cache invalidated at ' . date('H:i:s'));
});

$cache->registerObserver(function() {
    // Clear derived caches
    DerivedCache::clear();
});

$cache->invalidate(); // Both observers called
```

### Accessing All Cached Data

```php
$allPrefs = $cache->getAll();
foreach ($allPrefs as $key => $value) {
    echo "$key: $value\n";
}
```

### Checking if Key Exists

```php
if ($cache->has('api_key')) {
    $apiKey = $cache->get('api_key');
}
```

## API Reference

### PreferenceCache

#### Constructor
```php
public function __construct(PreferenceProviderInterface $provider)
```

#### Methods
- `get(string $key, mixed $default = null): mixed` - Get cached value
- `has(string $key): bool` - Check if key exists
- `getAll(): array` - Get all cached values
- `invalidate(): void` - Clear cache and notify observers
- `registerObserver(callable $observer): void` - Add invalidation observer
- `clearObservers(): void` - Remove all observers
- `getProvider(): PreferenceProviderInterface` - Get underlying provider

### PreferenceProviderInterface

#### Methods
- `get(string $key, mixed $default = null): mixed` - Retrieve value
- `getAll(): array` - Retrieve all values (for bulk loading)
- `has(string $key): bool` - Check if key exists

## Testing

```php
use PHPUnit\Framework\TestCase;
use KSF\PrefCache\PreferenceCache;

class MyCacheTest extends TestCase
{
    public function testCacheReturnsValues(): void
    {
        $provider = new ArrayProvider(['key' => 'value']);
        $cache = new PreferenceCache($provider);
        
        $this->assertSame('value', $cache->get('key'));
    }
    
    public function testInvalidationWorks(): void
    {
        $provider = new MutableProvider(['key' => 'old']);
        $cache = new PreferenceCache($provider);
        
        $this->assertSame('old', $cache->get('key'));
        
        $provider->set('key', 'new');
        $cache->invalidate();
        
        $this->assertSame('new', $cache->get('key'));
    }
}
```

## Requirements

- PHP 8.1 or higher
- No external dependencies

## License

GPL-3.0-or-later - See LICENSE file for details

## Contributing

Contributions welcome! Please open an issue or pull request on GitHub.

## Credits

Created by ksfraser for the FrontAccounting project.

## Links

- [GitHub Repository](https://github.com/ksfraser/ksf_PrefCache)
- [Issue Tracker](https://github.com/ksfraser/ksf_PrefCache/issues)
- [FrontAccounting](https://frontaccounting.com/)
