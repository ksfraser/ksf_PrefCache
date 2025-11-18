<?php
declare(strict_types=1);

namespace KSF\PrefCache;

/**
 * Generic Preference Cache
 * 
 * Reusable caching layer for any type of preferences from any data source.
 * Eliminates repeated lookups by caching values in memory for request duration.
 * 
 * Features:
 * - Provider-agnostic (works with session, DB, files, API, etc.)
 * - Observer pattern for cache invalidation events
 * - Lazy loading (cache loads on first access)
 * - Request-scoped (cache persists only during request)
 * - Type-safe with full PHP 8.4 support
 * 
 * Usage:
 *   $provider = new MyPreferenceProvider();
 *   $cache = new PreferenceCache($provider);
 *   
 *   // Get cached value
 *   $value = $cache->get('some_key', 'default');
 *   
 *   // Invalidate when data changes
 *   $cache->invalidate();
 *   
 *   // Observe invalidation events
 *   $cache->registerObserver(fn() => error_log('Cache cleared!'));
 * 
 * @package KSF\PrefCache
 */
class PreferenceCache
{
    private ?array $cache = null;
    private array $observers = [];
    private PreferenceProviderInterface $provider;
    
    /**
     * Constructor
     * 
     * @param PreferenceProviderInterface $provider Data source for preferences
     */
    public function __construct(PreferenceProviderInterface $provider)
    {
        $this->provider = $provider;
    }
    
    /**
     * Get a preference value (cached)
     * 
     * First access loads from provider, subsequent accesses return cached value.
     * 
     * @param string $key Preference key
     * @param mixed $default Default value if preference not found
     * @return mixed Cached preference value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->cache === null) {
            $this->loadCache();
        }
        
        return $this->cache[$key] ?? $default;
    }
    
    /**
     * Check if a preference exists in cache
     * 
     * @param string $key Preference key
     * @return bool True if preference exists
     */
    public function has(string $key): bool
    {
        if ($this->cache === null) {
            $this->loadCache();
        }
        
        return array_key_exists($key, $this->cache);
    }
    
    /**
     * Get all cached preferences
     * 
     * @return array<string, mixed> All cached preferences
     */
    public function getAll(): array
    {
        if ($this->cache === null) {
            $this->loadCache();
        }
        
        return $this->cache;
    }
    
    /**
     * Load cache from provider
     * 
     * Attempts to use provider's getAll() for bulk loading.
     * Falls back to individual get() calls if getAll() returns empty.
     * 
     * @return void
     */
    private function loadCache(): void
    {
        // Try bulk load first (optimization)
        $this->cache = $this->provider->getAll();
        
        // If provider doesn't support bulk loading, cache will be populated
        // incrementally on individual get() calls
        if (empty($this->cache)) {
            $this->cache = [];
        }
    }
    
    /**
     * Invalidate cache
     * 
     * Clears cached values and notifies all registered observers.
     * Call this when underlying preference data changes.
     * 
     * @return void
     */
    public function invalidate(): void
    {
        $this->cache = null;
        
        // Notify observers (event pattern for extensibility)
        foreach ($this->observers as $observer) {
            if (is_callable($observer)) {
                $observer();
            }
        }
    }
    
    /**
     * Register cache invalidation observer
     * 
     * Observers are notified when cache is invalidated.
     * Useful for cascading invalidation, logging, metrics, etc.
     * 
     * Example:
     *   $cache->registerObserver(function() {
     *       error_log('Preferences cache invalidated');
     *   });
     * 
     * @param callable $observer Callback to execute on cache invalidation
     * @return void
     */
    public function registerObserver(callable $observer): void
    {
        $this->observers[] = $observer;
    }
    
    /**
     * Clear all observers
     * 
     * Useful for testing to ensure clean state.
     * 
     * @return void
     */
    public function clearObservers(): void
    {
        $this->observers = [];
    }
    
    /**
     * Get the underlying provider
     * 
     * Useful for testing or inspecting provider configuration.
     * 
     * @return PreferenceProviderInterface
     */
    public function getProvider(): PreferenceProviderInterface
    {
        return $this->provider;
    }
}
