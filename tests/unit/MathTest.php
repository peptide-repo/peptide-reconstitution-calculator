<?php
/**
 * Unit tests for PRC_Math — the pure reconstitution calculation engine.
 *
 * Covers the full public API of PRC_Math: concentration, injection volume,
 * syringe units, doses per vial, and the top-level calculate() orchestrator.
 * All expected values were computed manually from the formulas in class-prc-math.php.
 *
 * Math reference:
 *   concentration_mg_per_ml = vial_mg / water_ml
 *   concentration_mcg_per_unit = (vial_mg * 1000) / (water_ml * 100)
 *   injection_volume_ml = dose_mg / concentration_mg_per_ml
 *   syringe_units = injection_volume_ml * 100
 *   doses_per_vial = vial_mg / dose_mg  (floor in calculate())
 *
 * @package PRC\Tests\Unit
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests for PRC_Math static methods.
 */
class MathTest extends TestCase {

	// ── concentration_mg_per_ml ───────────────────────────────────────

	/**
	 * Standard BPC-157 vial: 5 mg peptide in 2 mL water → 2.5 mg/mL.
	 */
	public function test_concentration_mg_per_ml_standard(): void {
		$result = PRC_Math::concentration_mg_per_ml( 5.0, 2.0 );
		$this->assertEqualsWithDelta( 2.5, $result, 0.0001 );
	}

	/**
	 * 10 mg peptide in 2 mL water → 5.0 mg/mL.
	 */
	public function test_concentration_mg_per_ml_high_dose(): void {
		$result = PRC_Math::concentration_mg_per_ml( 10.0, 2.0 );
		$this->assertEqualsWithDelta( 5.0, $result, 0.0001 );
	}

	/**
	 * 2 mg peptide in 1 mL water → 2.0 mg/mL.
	 */
	public function test_concentration_mg_per_ml_small_vial(): void {
		$result = PRC_Math::concentration_mg_per_ml( 2.0, 1.0 );
		$this->assertEqualsWithDelta( 2.0, $result, 0.0001 );
	}

	/**
	 * 30 mg peptide in 3 mL water → 10.0 mg/mL.
	 */
	public function test_concentration_mg_per_ml_large_vial(): void {
		$result = PRC_Math::concentration_mg_per_ml( 30.0, 3.0 );
		$this->assertEqualsWithDelta( 10.0, $result, 0.0001 );
	}

	/**
	 * Zero water volume → returns 0 (division guard).
	 */
	public function test_concentration_mg_per_ml_zero_water_returns_zero(): void {
		$result = PRC_Math::concentration_mg_per_ml( 5.0, 0.0 );
		$this->assertSame( 0.0, $result );
	}

	/**
	 * Negative water volume is treated as zero (guard condition uses <=).
	 */
	public function test_concentration_mg_per_ml_negative_water_returns_zero(): void {
		$result = PRC_Math::concentration_mg_per_ml( 5.0, -1.0 );
		$this->assertSame( 0.0, $result );
	}

	/**
	 * Zero vial mg with valid water → 0 mg/mL.
	 */
	public function test_concentration_mg_per_ml_zero_peptide(): void {
		$result = PRC_Math::concentration_mg_per_ml( 0.0, 2.0 );
		$this->assertSame( 0.0, $result );
	}

	// ── concentration_mcg_per_unit ────────────────────────────────────

	/**
	 * 5 mg / 2 mL: (5 * 1000) / (2 * 100) = 5000 / 200 = 25.0 mcg/unit.
	 */
	public function test_concentration_mcg_per_unit_standard(): void {
		$result = PRC_Math::concentration_mcg_per_unit( 5.0, 2.0 );
		$this->assertEqualsWithDelta( 25.0, $result, 0.0001 );
	}

	/**
	 * 10 mg / 2 mL: (10 * 1000) / (2 * 100) = 10000 / 200 = 50.0 mcg/unit.
	 */
	public function test_concentration_mcg_per_unit_10mg_2ml(): void {
		$result = PRC_Math::concentration_mcg_per_unit( 10.0, 2.0 );
		$this->assertEqualsWithDelta( 50.0, $result, 0.0001 );
	}

	/**
	 * 2 mg / 1 mL: (2 * 1000) / (1 * 100) = 2000 / 100 = 20.0 mcg/unit.
	 */
	public function test_concentration_mcg_per_unit_2mg_1ml(): void {
		$result = PRC_Math::concentration_mcg_per_unit( 2.0, 1.0 );
		$this->assertEqualsWithDelta( 20.0, $result, 0.0001 );
	}

	/**
	 * Zero water → returns 0 (guard).
	 */
	public function test_concentration_mcg_per_unit_zero_water_returns_zero(): void {
		$result = PRC_Math::concentration_mcg_per_unit( 5.0, 0.0 );
		$this->assertSame( 0.0, $result );
	}

	// ── injection_volume_ml ───────────────────────────────────────────

	/**
	 * 250 mcg dose at 2.5 mg/mL: dose_mg = 0.25 → 0.25 / 2.5 = 0.1 mL.
	 */
	public function test_injection_volume_ml_standard(): void {
		$result = PRC_Math::injection_volume_ml( 0.25, 2.5 );
		$this->assertEqualsWithDelta( 0.1, $result, 0.0001 );
	}

	/**
	 * 500 mcg dose at 2.5 mg/mL: 0.5 / 2.5 = 0.2 mL.
	 */
	public function test_injection_volume_ml_higher_dose(): void {
		$result = PRC_Math::injection_volume_ml( 0.5, 2.5 );
		$this->assertEqualsWithDelta( 0.2, $result, 0.0001 );
	}

	/**
	 * 1 mg dose at 5 mg/mL: 1.0 / 5.0 = 0.2 mL.
	 */
	public function test_injection_volume_ml_mg_dose(): void {
		$result = PRC_Math::injection_volume_ml( 1.0, 5.0 );
		$this->assertEqualsWithDelta( 0.2, $result, 0.0001 );
	}

	/**
	 * Zero concentration → returns 0 (division guard).
	 */
	public function test_injection_volume_ml_zero_concentration_returns_zero(): void {
		$result = PRC_Math::injection_volume_ml( 0.25, 0.0 );
		$this->assertSame( 0.0, $result );
	}

	/**
	 * Negative concentration → returns 0 (guard condition uses <=).
	 */
	public function test_injection_volume_ml_negative_concentration_returns_zero(): void {
		$result = PRC_Math::injection_volume_ml( 0.25, -1.0 );
		$this->assertSame( 0.0, $result );
	}

	// ── syringe_units ─────────────────────────────────────────────────

	/**
	 * 0.1 mL × 100 = 10 units.
	 */
	public function test_syringe_units_0_1_ml(): void {
		$result = PRC_Math::syringe_units( 0.1 );
		$this->assertEqualsWithDelta( 10.0, $result, 0.0001 );
	}

	/**
	 * 0.2 mL × 100 = 20 units.
	 */
	public function test_syringe_units_0_2_ml(): void {
		$result = PRC_Math::syringe_units( 0.2 );
		$this->assertEqualsWithDelta( 20.0, $result, 0.0001 );
	}

	/**
	 * 1 mL × 100 = 100 units (full syringe).
	 */
	public function test_syringe_units_full_ml(): void {
		$result = PRC_Math::syringe_units( 1.0 );
		$this->assertEqualsWithDelta( 100.0, $result, 0.0001 );
	}

	/**
	 * 0 mL → 0 units.
	 */
	public function test_syringe_units_zero(): void {
		$result = PRC_Math::syringe_units( 0.0 );
		$this->assertSame( 0.0, $result );
	}

	/**
	 * Fractional: 0.05 mL × 100 = 5.0 units.
	 */
	public function test_syringe_units_fractional(): void {
		$result = PRC_Math::syringe_units( 0.05 );
		$this->assertEqualsWithDelta( 5.0, $result, 0.0001 );
	}

	// ── doses_per_vial ────────────────────────────────────────────────

	/**
	 * 5 mg vial at 0.25 mg dose → 20 doses.
	 */
	public function test_doses_per_vial_standard(): void {
		$result = PRC_Math::doses_per_vial( 5.0, 0.25 );
		$this->assertEqualsWithDelta( 20.0, $result, 0.0001 );
	}

	/**
	 * 10 mg vial at 0.5 mg dose → 20 doses.
	 */
	public function test_doses_per_vial_large_vial(): void {
		$result = PRC_Math::doses_per_vial( 10.0, 0.5 );
		$this->assertEqualsWithDelta( 20.0, $result, 0.0001 );
	}

	/**
	 * 5 mg vial at 0.3 mg dose → 16.666... (floor returns 16 in calculate()).
	 */
	public function test_doses_per_vial_non_integer(): void {
		$result = PRC_Math::doses_per_vial( 5.0, 0.3 );
		// Raw float — floor applied in calculate().
		$this->assertGreaterThan( 16.0, $result );
		$this->assertLessThan( 17.0, $result );
	}

	/**
	 * Zero dose → returns 0 (guard against division by zero).
	 */
	public function test_doses_per_vial_zero_dose_returns_zero(): void {
		$result = PRC_Math::doses_per_vial( 5.0, 0.0 );
		$this->assertSame( 0.0, $result );
	}

	/**
	 * Negative dose → returns 0 (guard condition uses <=).
	 */
	public function test_doses_per_vial_negative_dose_returns_zero(): void {
		$result = PRC_Math::doses_per_vial( 5.0, -0.25 );
		$this->assertSame( 0.0, $result );
	}

	// ── calculate() — full orchestrator ──────────────────────────────

	/**
	 * BPC-157 typical scenario: 5 mg vial, 2 mL BAC water, 250 mcg dose.
	 *
	 * Expected:
	 *   concentration_mg_per_ml    = 5 / 2 = 2.5
	 *   concentration_mcg_per_unit = (5000) / (200) = 25.0
	 *   injection_volume_ml        = 0.25 / 2.5 = 0.1
	 *   syringe_units              = 0.1 * 100 = 10
	 *   doses_per_vial             = floor(5 / 0.25) = 20
	 */
	public function test_calculate_bpc157_250mcg_dose(): void {
		$result = PRC_Math::calculate( 5.0, 2.0, 250.0, 'mcg' );

		$this->assertIsArray( $result );
		$this->assertEqualsWithDelta( 2.5, $result['concentration_mg_per_ml'], 0.0001 );
		$this->assertEqualsWithDelta( 25.0, $result['concentration_mcg_per_unit'], 0.01 );
		$this->assertEqualsWithDelta( 0.1, $result['injection_volume_ml'], 0.0001 );
		$this->assertEqualsWithDelta( 10.0, $result['syringe_units'], 0.1 );
		$this->assertSame( 20.0, $result['doses_per_vial'] );
		$this->assertSame( 5.0, $result['total_peptide_mg'] );
		$this->assertSame( 2.0, $result['water_ml'] );
		$this->assertEqualsWithDelta( 0.25, $result['desired_dose_mg'], 0.0001 );
		$this->assertSame( 250.0, $result['desired_dose_display'] );
		$this->assertSame( 'mcg', $result['dose_unit'] );
	}

	/**
	 * Semaglutide scenario: 5 mg vial, 2 mL BAC water, 0.5 mg dose (mg unit).
	 *
	 * Expected:
	 *   concentration_mg_per_ml    = 5 / 2 = 2.5
	 *   concentration_mcg_per_unit = 25.0
	 *   injection_volume_ml        = 0.5 / 2.5 = 0.2
	 *   syringe_units              = 0.2 * 100 = 20
	 *   doses_per_vial             = floor(5 / 0.5) = 10
	 */
	public function test_calculate_semaglutide_0_5mg_dose(): void {
		$result = PRC_Math::calculate( 5.0, 2.0, 0.5, 'mg' );

		$this->assertEqualsWithDelta( 2.5, $result['concentration_mg_per_ml'], 0.0001 );
		$this->assertEqualsWithDelta( 25.0, $result['concentration_mcg_per_unit'], 0.01 );
		$this->assertEqualsWithDelta( 0.2, $result['injection_volume_ml'], 0.0001 );
		$this->assertEqualsWithDelta( 20.0, $result['syringe_units'], 0.1 );
		$this->assertSame( 10.0, $result['doses_per_vial'] );
		$this->assertSame( 'mg', $result['dose_unit'] );
	}

	/**
	 * TB-500 high-dose scenario: 10 mg vial, 2 mL water, 2000 mcg dose.
	 *
	 * Expected:
	 *   concentration_mg_per_ml    = 10 / 2 = 5.0
	 *   concentration_mcg_per_unit = (10000) / (200) = 50.0
	 *   injection_volume_ml        = 2.0 / 5.0 = 0.4
	 *   syringe_units              = 0.4 * 100 = 40
	 *   doses_per_vial             = floor(10 / 2) = 5
	 */
	public function test_calculate_tb500_2000mcg_dose(): void {
		$result = PRC_Math::calculate( 10.0, 2.0, 2000.0, 'mcg' );

		$this->assertEqualsWithDelta( 5.0, $result['concentration_mg_per_ml'], 0.0001 );
		$this->assertEqualsWithDelta( 50.0, $result['concentration_mcg_per_unit'], 0.01 );
		$this->assertEqualsWithDelta( 0.4, $result['injection_volume_ml'], 0.0001 );
		$this->assertEqualsWithDelta( 40.0, $result['syringe_units'], 0.1 );
		$this->assertSame( 5.0, $result['doses_per_vial'] );
	}

	/**
	 * MK-677 mg-dose scenario: 30 mg vial, 3 mL water, 25 mg dose.
	 *
	 * Expected:
	 *   concentration_mg_per_ml    = 30 / 3 = 10.0
	 *   concentration_mcg_per_unit = (30000) / (300) = 100.0
	 *   injection_volume_ml        = 25 / 10 = 2.5
	 *   syringe_units              = 2.5 * 100 = 250
	 *   doses_per_vial             = floor(30 / 25) = 1
	 */
	public function test_calculate_mk677_25mg_dose(): void {
		$result = PRC_Math::calculate( 30.0, 3.0, 25.0, 'mg' );

		$this->assertEqualsWithDelta( 10.0, $result['concentration_mg_per_ml'], 0.0001 );
		$this->assertEqualsWithDelta( 100.0, $result['concentration_mcg_per_unit'], 0.01 );
		$this->assertEqualsWithDelta( 2.5, $result['injection_volume_ml'], 0.0001 );
		$this->assertEqualsWithDelta( 250.0, $result['syringe_units'], 0.1 );
		$this->assertSame( 1.0, $result['doses_per_vial'] );
	}

	/**
	 * Minimum BPC-157 dose: 200 mcg from a 5 mg / 2 mL vial.
	 *
	 * Expected:
	 *   injection_volume_ml = 0.2 / 2.5 = 0.08
	 *   syringe_units       = 0.08 * 100 = 8.0
	 *   doses_per_vial      = floor(5 / 0.2) = 25
	 */
	public function test_calculate_bpc157_200mcg_minimum_dose(): void {
		$result = PRC_Math::calculate( 5.0, 2.0, 200.0, 'mcg' );

		$this->assertEqualsWithDelta( 0.08, $result['injection_volume_ml'], 0.0001 );
		$this->assertEqualsWithDelta( 8.0, $result['syringe_units'], 0.1 );
		$this->assertSame( 25.0, $result['doses_per_vial'] );
	}

	/**
	 * Very small dose: 100 mcg from 5 mg / 2 mL.
	 *
	 * Expected:
	 *   injection_volume_ml = 0.1 / 2.5 = 0.04
	 *   syringe_units       = 4.0
	 *   doses_per_vial      = floor(5 / 0.1) = 50
	 */
	public function test_calculate_ipamorelin_100mcg_dose(): void {
		$result = PRC_Math::calculate( 5.0, 2.0, 100.0, 'mcg' );

		$this->assertEqualsWithDelta( 0.04, $result['injection_volume_ml'], 0.0001 );
		$this->assertEqualsWithDelta( 4.0, $result['syringe_units'], 0.1 );
		$this->assertSame( 50.0, $result['doses_per_vial'] );
	}

	/**
	 * Non-integer doses-per-vial: 5 mg vial, 300 mcg dose → 16.666 → floor → 16.
	 */
	public function test_calculate_doses_per_vial_is_floored(): void {
		$result = PRC_Math::calculate( 5.0, 2.0, 300.0, 'mcg' );

		$this->assertSame( 16.0, $result['doses_per_vial'] );
	}

	/**
	 * mcg/unit output is rounded to 2 decimal places.
	 */
	public function test_calculate_concentration_mcg_per_unit_rounded_to_two_decimals(): void {
		// 3 mg / 2 mL: mcg_per_unit = 3000 / 200 = 15.0 — exact, check rounding still works.
		$result = PRC_Math::calculate( 3.0, 2.0, 150.0, 'mcg' );
		// 15.0 rounded to 2 dp = 15.0.
		$this->assertEqualsWithDelta( 15.0, $result['concentration_mcg_per_unit'], 0.005 );
	}

	/**
	 * concentration_mg_per_ml output is rounded to 4 decimal places.
	 *
	 * 7 mg / 3 mL = 2.333... → rounded to 4 dp = 2.3333.
	 */
	public function test_calculate_concentration_rounded_to_four_decimals(): void {
		$result = PRC_Math::calculate( 7.0, 3.0, 100.0, 'mcg' );
		$this->assertEqualsWithDelta( 2.3333, $result['concentration_mg_per_ml'], 0.00005 );
	}

	/**
	 * Result array has the expected keys.
	 */
	public function test_calculate_returns_expected_keys(): void {
		$result = PRC_Math::calculate( 5.0, 2.0, 250.0, 'mcg' );

		$expected_keys = [
			'concentration_mg_per_ml',
			'concentration_mcg_per_unit',
			'injection_volume_ml',
			'syringe_units',
			'doses_per_vial',
			'total_peptide_mg',
			'water_ml',
			'desired_dose_mg',
			'desired_dose_display',
			'dose_unit',
		];

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $result, "Missing key: $key" );
		}
	}

	/**
	 * mcg unit: desired_dose_mg is divided by 1000.
	 * 500 mcg → 0.5 mg stored as desired_dose_mg.
	 */
	public function test_calculate_mcg_dose_unit_conversion(): void {
		$result = PRC_Math::calculate( 10.0, 2.0, 500.0, 'mcg' );

		$this->assertEqualsWithDelta( 0.5, $result['desired_dose_mg'], 0.0001 );
		$this->assertSame( 500.0, $result['desired_dose_display'] );
		$this->assertSame( 'mcg', $result['dose_unit'] );
	}

	/**
	 * mg unit: desired_dose_mg equals the raw input value.
	 */
	public function test_calculate_mg_dose_unit_no_conversion(): void {
		$result = PRC_Math::calculate( 10.0, 2.0, 1.5, 'mg' );

		$this->assertEqualsWithDelta( 1.5, $result['desired_dose_mg'], 0.0001 );
		$this->assertSame( 1.5, $result['desired_dose_display'] );
		$this->assertSame( 'mg', $result['dose_unit'] );
	}
}
