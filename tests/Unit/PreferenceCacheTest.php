<?php
declare(strict_types=1);

namespace KSF\PrefCache\Tests\Unit;

use KSF\PrefCache\PreferenceCache;
use KSF\PrefCache\PreferenceProviderInterface;
use PHPUnit\Framework\TestCase;

final class PreferenceCacheTest extends TestCase
{
    public function testGetLoadsFromProviderGetAllOnce(): void
    {
        $provider = new FakeProvider(['a' => 1], ['a' => true]);
        $cache = new PreferenceCache($provider);

        $this->assertSame(1, $cache->get('a'));
        $this->assertSame(1, $cache->get('a'));

        $this->assertSame(1, $provider->getAllCalls);
        $this->assertSame(0, $provider->getCalls);
    }

    public function testGetReturnsDefaultWhenMissing(): void
    {
        $provider = new FakeProvider(['a' => 1], ['a' => true]);
        $cache = new PreferenceCache($provider);

        $this->assertSame('d', $cache->get('missing', 'd'));
    }

    public function testHasLoadsCacheAndUsesArrayKeyExists(): void
    {
        $provider = new FakeProvider(['a' => null], ['a' => true]);
        $cache = new PreferenceCache($provider);

        $this->assertTrue($cache->has('a'));
        $this->assertFalse($cache->has('b'));
    }

    public function testGetAllReturnsArray(): void
    {
        $provider = new FakeProvider(['a' => 1, 'b' => 2], ['a' => true, 'b' => true]);
        $cache = new PreferenceCache($provider);

        $this->assertSame(['a' => 1, 'b' => 2], $cache->getAll());
    }

    public function testInvalidateClearsCacheAndNotifiesObservers(): void
    {
        $provider = new FakeProvider(['a' => 1], ['a' => true]);
        $cache = new PreferenceCache($provider);

        $cache->get('a');

        $called = 0;
        $cache->registerObserver(function () use (&$called): void { $called++; });
        $cache->registerObserver(function () use (&$called): void { $called++; });

        $cache->invalidate();
        $this->assertSame(2, $called);

        // Loads again after invalidation.
        $cache->get('a');
        $this->assertSame(2, $provider->getAllCalls);
    }

    public function testClearObserversPreventsNotification(): void
    {
        $provider = new FakeProvider(['a' => 1], ['a' => true]);
        $cache = new PreferenceCache($provider);

        $called = 0;
        $cache->registerObserver(function () use (&$called): void { $called++; });
        $cache->clearObservers();

        $cache->invalidate();
        $this->assertSame(0, $called);
    }

    public function testGetProviderReturnsSameInstance(): void
    {
        $provider = new FakeProvider(['a' => 1], ['a' => true]);
        $cache = new PreferenceCache($provider);

        $this->assertSame($provider, $cache->getProvider());
    }
}

final class FakeProvider implements PreferenceProviderInterface
{
    /** @var array<string, mixed> */
    private array $all;

    /** @var array<string, bool> */
    private array $exists;

    public int $getCalls = 0;
    public int $getAllCalls = 0;

    /** @param array<string, mixed> $all @param array<string, bool> $exists */
    public function __construct(array $all, array $exists)
    {
        $this->all = $all;
        $this->exists = $exists;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->getCalls++;
        return $this->all[$key] ?? $default;
    }

    public function getAll(): array
    {
        $this->getAllCalls++;
        return $this->all;
    }

    public function has(string $key): bool
    {
        return $this->exists[$key] ?? false;
    }
}
