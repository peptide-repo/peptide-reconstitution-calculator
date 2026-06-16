<?php
/**
 * Unit tests for PRC_Rest_Controller.
 *
 * Tests the calculate() method callback using the WP_REST_Request stub.
 * The get_presets() / get_preset() methods are thin wrappers over
 * PRC_Preset_Provider, which is covered by PresetProviderTest.
 *
 * @package PRC\Tests\Unit
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests for PRC_Rest_Controller REST callback methods.
 */
class RestControllerTest extends TestCase {

	private PRC_Rest_Controller $controller;

	protected function setUp(): void {
		$this->controller = new PRC_Rest_Controller();
	}

	// ── /calculate callback ───────────────────────────────────────────

	/**
	 * calculate() returns a WP_REST_Response with status 200.
	 */
	public function test_calculate_returns_200_response(): void {
		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'vial_mg', '5' );
		$request->set_param( 'water_ml', '2' );
		$request->set_param( 'desired_dose', '250' );
		$request->set_param( 'dose_unit', 'mcg' );

		$response = $this->controller->calculate( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * calculate() returns the correct BPC-157 math results.
	 *
	 * 5 mg / 2 mL / 250 mcg → concentration 2.5, syringe units 10.
	 */
	public function test_calculate_bpc157_correct_values(): void {
		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'vial_mg', '5' );
		$request->set_param( 'water_ml', '2' );
		$request->set_param( 'desired_dose', '250' );
		$request->set_param( 'dose_unit', 'mcg' );

		$response = $this->controller->calculate( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertEqualsWithDelta( 2.5, $data['concentration_mg_per_ml'], 0.0001 );
		$this->assertEqualsWithDelta( 10.0, $data['syringe_units'], 0.1 );
		$this->assertSame( 20.0, $data['doses_per_vial'] );
	}

	/**
	 * calculate() accepts mg dose unit correctly.
	 *
	 * 5 mg / 2 mL / 0.5 mg → syringe units 20.
	 */
	public function test_calculate_mg_dose_unit(): void {
		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'vial_mg', '5' );
		$request->set_param( 'water_ml', '2' );
		$request->set_param( 'desired_dose', '0.5' );
		$request->set_param( 'dose_unit', 'mg' );

		$response = $this->controller->calculate( $request );
		$data     = $response->get_data();

		$this->assertEqualsWithDelta( 20.0, $data['syringe_units'], 0.1 );
		$this->assertSame( 10.0, $data['doses_per_vial'] );
	}

	/**
	 * calculate() returns all expected keys in the response data.
	 */
	public function test_calculate_response_has_expected_keys(): void {
		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'vial_mg', '5' );
		$request->set_param( 'water_ml', '2' );
		$request->set_param( 'desired_dose', '250' );
		$request->set_param( 'dose_unit', 'mcg' );

		$data = $this->controller->calculate( $request )->get_data();

		$expected_keys = [
			'concentration_mg_per_ml',
			'concentration_mcg_per_unit',
			'injection_volume_ml',
			'syringe_units',
			'doses_per_vial',
			'dose_unit',
		];

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $data, "Missing key: $key" );
		}
	}

	/**
	 * get_presets() returns a 200 response with an array.
	 */
	public function test_get_presets_returns_200_with_array(): void {
		$request  = new WP_REST_Request( 'GET' );
		$response = $this->controller->get_presets( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
	}

	/**
	 * get_preset() returns a WP_Error for an unknown slug.
	 */
	public function test_get_preset_unknown_slug_returns_wp_error(): void {
		$request = new WP_REST_Request( 'GET' );
		$request->set_param( 'slug', 'nonexistent-peptide-xyz' );

		$result = $this->controller->get_preset( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'prc_preset_not_found', $result->get_error_code() );
	}

	/**
	 * get_preset() returns a 200 response for a known slug.
	 */
	public function test_get_preset_known_slug_returns_200(): void {
		$request = new WP_REST_Request( 'GET' );
		$request->set_param( 'slug', 'bpc-157' );

		$result = $this->controller->get_preset( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$this->assertSame( 200, $result->get_status() );

		$data = $result->get_data();
		$this->assertSame( 'bpc-157', $data['slug'] );
	}
}
