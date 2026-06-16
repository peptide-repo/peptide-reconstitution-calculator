<?php
/**
 * Unit tests for PRC_Preset_Provider (standalone/no-PR-Core path).
 *
 * Tests the provider in the state where PR Core is NOT active (no
 * PR_CORE_VERSION constant), exercising the default-preset fallback,
 * cache read/write, and cache invalidation.
 *
 * @package PRC\Tests\Unit
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests for PRC_Preset_Provider without PR Core.
 */
class PresetProviderTest extends TestCase {

	protected function setUp(): void {
		// Ensure PR_CORE_VERSION is not defined (test environment default).
		// The provider checks PRC_Calculator::is_pr_core_active() which checks
		// defined('PR_CORE_VERSION'). We cannot un-define, so tests assume
		// the constant is absent (which it is in a clean test run).
		global $_test_transients;
		$_test_transients = [];
	}

	// ── Fallback behavior (no PR Core) ───────────────────────────────

	/**
	 * get_all_presets() returns a non-empty array when PR Core is absent.
	 */
	public function test_get_all_presets_returns_defaults_without_pr_core(): void {
		$provider = new PRC_Preset_Provider();
		$presets  = $provider->get_all_presets();

		$this->assertIsArray( $presets );
		$this->assertGreaterThan( 0, count( $presets ) );
	}

	/**
	 * get_all_presets() matches PRC_Default_Presets::get_all() when PR Core absent.
	 */
	public function test_get_all_presets_matches_defaults(): void {
		$provider = new PRC_Preset_Provider();
		$actual   = $provider->get_all_presets();
		$expected = PRC_Default_Presets::get_all();

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * All returned presets have a slug key.
	 */
	public function test_all_presets_have_slug(): void {
		$provider = new PRC_Preset_Provider();
		$presets  = $provider->get_all_presets();

		foreach ( $presets as $preset ) {
			$this->assertArrayHasKey( 'slug', $preset );
			$this->assertNotEmpty( $preset['slug'] );
		}
	}

	// ── Cache invalidation ────────────────────────────────────────────

	/**
	 * invalidate_cache() removes the 'prc_presets_all' transient.
	 */
	public function test_invalidate_cache_removes_transient(): void {
		global $_test_transients;

		// Seed the transient manually.
		$_test_transients['prc_presets_all'] = [ 'cached-data' ];

		$provider = new PRC_Preset_Provider();
		$provider->invalidate_cache();

		$this->assertArrayNotHasKey( 'prc_presets_all', $_test_transients );
	}

	/**
	 * Multiple invalidate_cache() calls are safe (idempotent).
	 */
	public function test_invalidate_cache_is_idempotent(): void {
		$provider = new PRC_Preset_Provider();

		// Should not throw, even if transient was never set.
		$provider->invalidate_cache();
		$provider->invalidate_cache();

		$this->addToAssertionCount( 1 ); // If we got here, no exception.
	}
}
