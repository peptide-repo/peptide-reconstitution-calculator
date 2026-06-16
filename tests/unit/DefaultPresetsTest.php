<?php
/**
 * Unit tests for PRC_Default_Presets.
 *
 * Verifies that the hardcoded fallback preset list is well-formed:
 * expected slugs are present, required keys exist, numeric ranges
 * are sensible, and no data has been accidentally corrupted.
 *
 * @package PRC\Tests\Unit
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests for PRC_Default_Presets::get_all().
 */
class DefaultPresetsTest extends TestCase {

	/** @var array<int, array<string, mixed>> */
	private array $presets;

	protected function setUp(): void {
		$this->presets = PRC_Default_Presets::get_all();
	}

	// ── Collection shape ──────────────────────────────────────────────

	/**
	 * get_all() returns an array with at least one entry.
	 */
	public function test_get_all_returns_array(): void {
		$this->assertIsArray( $this->presets );
		$this->assertGreaterThanOrEqual( 1, count( $this->presets ) );
	}

	/**
	 * Exactly 8 built-in presets are defined.
	 */
	public function test_get_all_contains_eight_presets(): void {
		$this->assertCount( 8, $this->presets );
	}

	/**
	 * The 8 expected slugs are all present.
	 */
	public function test_expected_slugs_present(): void {
		$slugs = array_column( $this->presets, 'slug' );

		$expected = [
			'bpc-157',
			'tb-500',
			'semaglutide',
			'cjc-1295-dac',
			'ipamorelin',
			'pt-141',
			'mk-677',
			'ghrp-6',
		];

		foreach ( $expected as $slug ) {
			$this->assertContains( $slug, $slugs, "Missing expected slug: $slug" );
		}
	}

	// ── Per-preset required fields ────────────────────────────────────

	/**
	 * Every preset has all required keys.
	 *
	 * @dataProvider preset_provider
	 * @param array<string, mixed> $preset
	 */
	public function test_preset_has_required_keys( array $preset ): void {
		$required = [
			'slug',
			'name',
			'vial_sizes_mg',
			'default_vial_mg',
			'recommended_water_ml',
			'dose_range_min',
			'dose_range_max',
			'dose_unit',
			'typical_frequency',
			'evidence_strength',
			'source',
		];

		foreach ( $required as $key ) {
			$this->assertArrayHasKey( $key, $preset, "Missing key '$key' in preset '{$preset['slug']}'" );
		}
	}

	/**
	 * Every preset's slug is a non-empty string.
	 *
	 * @dataProvider preset_provider
	 * @param array<string, mixed> $preset
	 */
	public function test_preset_slug_is_non_empty_string( array $preset ): void {
		$this->assertIsString( $preset['slug'] );
		$this->assertNotEmpty( $preset['slug'] );
	}

	/**
	 * Every preset's name is a non-empty string.
	 *
	 * @dataProvider preset_provider
	 * @param array<string, mixed> $preset
	 */
	public function test_preset_name_is_non_empty_string( array $preset ): void {
		$this->assertIsString( $preset['name'] );
		$this->assertNotEmpty( $preset['name'] );
	}

	/**
	 * vial_sizes_mg is a non-empty array of positive floats.
	 *
	 * @dataProvider preset_provider
	 * @param array<string, mixed> $preset
	 */
	public function test_preset_vial_sizes_are_positive( array $preset ): void {
		$this->assertIsArray( $preset['vial_sizes_mg'] );
		$this->assertNotEmpty( $preset['vial_sizes_mg'] );

		foreach ( $preset['vial_sizes_mg'] as $size ) {
			$this->assertIsNumeric( $size );
			$this->assertGreaterThan( 0, $size, "Non-positive vial size in preset '{$preset['slug']}'" );
		}
	}

	/**
	 * default_vial_mg is one of the values in vial_sizes_mg.
	 *
	 * @dataProvider preset_provider
	 * @param array<string, mixed> $preset
	 */
	public function test_preset_default_vial_is_in_sizes( array $preset ): void {
		$this->assertContains(
			$preset['default_vial_mg'],
			$preset['vial_sizes_mg'],
			"default_vial_mg not in vial_sizes_mg for preset '{$preset['slug']}'"
		);
	}

	/**
	 * recommended_water_ml is positive.
	 *
	 * @dataProvider preset_provider
	 * @param array<string, mixed> $preset
	 */
	public function test_preset_recommended_water_is_positive( array $preset ): void {
		$this->assertGreaterThan( 0, $preset['recommended_water_ml'] );
	}

	/**
	 * dose_unit is either 'mcg' or 'mg'.
	 *
	 * @dataProvider preset_provider
	 * @param array<string, mixed> $preset
	 */
	public function test_preset_dose_unit_is_valid( array $preset ): void {
		$this->assertContains( $preset['dose_unit'], [ 'mcg', 'mg' ], "Invalid dose_unit in preset '{$preset['slug']}'" );
	}

	/**
	 * dose_range_min < dose_range_max when both are non-null.
	 *
	 * @dataProvider preset_provider
	 * @param array<string, mixed> $preset
	 */
	public function test_preset_dose_range_min_less_than_max( array $preset ): void {
		if ( null === $preset['dose_range_min'] || null === $preset['dose_range_max'] ) {
			$this->addToAssertionCount( 1 ); // Null ranges are acceptable.
			return;
		}

		$this->assertLessThan(
			$preset['dose_range_max'],
			$preset['dose_range_min'],
			"dose_range_min >= dose_range_max in preset '{$preset['slug']}'"
		);
	}

	/**
	 * source field is 'default' for every built-in preset.
	 *
	 * @dataProvider preset_provider
	 * @param array<string, mixed> $preset
	 */
	public function test_preset_source_is_default( array $preset ): void {
		$this->assertSame( 'default', $preset['source'] );
	}

	// ── Spot-check specific known values ──────────────────────────────

	/**
	 * BPC-157: 5 mg default vial, 2 mL water, 500 mcg max dose.
	 */
	public function test_bpc157_specific_values(): void {
		$preset = $this->find_preset( 'bpc-157' );

		$this->assertNotNull( $preset );
		$this->assertSame( 5.0, (float) $preset['default_vial_mg'] );
		$this->assertSame( 2.0, (float) $preset['recommended_water_ml'] );
		$this->assertSame( 500.0, (float) $preset['dose_range_max'] );
		$this->assertSame( 'mcg', $preset['dose_unit'] );
	}

	/**
	 * Semaglutide: dose_unit is mg (not mcg), max dose 2.4 mg.
	 */
	public function test_semaglutide_mg_dose_unit(): void {
		$preset = $this->find_preset( 'semaglutide' );

		$this->assertNotNull( $preset );
		$this->assertSame( 'mg', $preset['dose_unit'] );
		$this->assertEqualsWithDelta( 2.4, (float) $preset['dose_range_max'], 0.01 );
	}

	/**
	 * MK-677: 30 mg default vial, 3 mL water (large-vial path in recommend_water_ml).
	 */
	public function test_mk677_large_vial_water_recommendation(): void {
		$preset = $this->find_preset( 'mk-677' );

		$this->assertNotNull( $preset );
		$this->assertSame( 30.0, (float) $preset['default_vial_mg'] );
		$this->assertSame( 3.0, (float) $preset['recommended_water_ml'] );
	}

	// ── DataProvider ──────────────────────────────────────────────────

	/**
	 * Provides each preset individually.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function preset_provider(): array {
		$presets = PRC_Default_Presets::get_all();
		$cases   = [];
		foreach ( $presets as $preset ) {
			$cases[ $preset['slug'] ] = [ $preset ];
		}
		return $cases;
	}

	// ── Helpers ───────────────────────────────────────────────────────

	/**
	 * Find a preset by slug.
	 *
	 * @param string $slug
	 * @return array<string, mixed>|null
	 */
	private function find_preset( string $slug ): ?array {
		foreach ( $this->presets as $preset ) {
			if ( $preset['slug'] === $slug ) {
				return $preset;
			}
		}
		return null;
	}
}
