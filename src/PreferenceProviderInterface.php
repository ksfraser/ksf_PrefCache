<?php
declare(strict_types=1);

namespace KSF\PrefCache;

/**
 * Preference Provider Interface
 * 
 * Contract for data sources that supply preference values.
 * Implementations can read from sessions, databases, config files, APIs, etc.
 * 
 * Example implementations:
 * - SessionPreferenceProvider - Read from $_SESSION
 * - DatabasePreferenceProvider - Read from database table
 * - FilePreferenceProvider - Read from .ini or .json files
 * - ApiPreferenceProvider - Fetch from remote API
 * 
 * @package KSF\PrefCache
 */
interface PreferenceProviderInterface
{
    /**
     * Get a preference value by key
     * 
     * @param string $key Preference key (e.g., 'price_dec', 'qty_dec')
     * @param mixed $default Default value if preference not found
     * @return mixed Preference value
     */
    public function get(string $key, mixed $default = null): mixed;
    
    /**
     * Get all preferences at once
     * 
     * Optional optimization to fetch all preferences in one operation.
     * If not supported, return empty array and cache will call get() per key.
     * 
     * @return array<string, mixed> All preferences keyed by name
     */
    public function getAll(): array;
    
    /**
     * Check if a preference exists
     * 
     * @param string $key Preference key
     * @return bool True if preference exists
     */
    public function has(string $key): bool;
}
