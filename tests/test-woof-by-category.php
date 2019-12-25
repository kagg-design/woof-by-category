<?php

class Woof_By_Category_Test extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->class_instance = new Woof_By_Category( dirname( __DIR__ ) . 'woof-by-category.php', '1.5' );
	}

	public function test_add_settings_link() {
		$links    = $this->class_instance->add_settings_link( array(), '' );
		$expected = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=woof-by-category' ) . '" aria-label="' . esc_attr__( 'View WOOF by Category settings', 'woof-by-category' ) . '">' . esc_html__( 'Settings', 'woof-by-category' ) . '</a>',
		);

		$this->assertEquals( $expected, $links );
	}

	private function sample_settings() {
		$test_options = array(
			0 =>
				array(
					'category' => 'sub-assumenda',
					'filters'  =>
						array(
							0 => 'product_cat',
							1 => 'pa_material',
						),
				),
			1 =>
				array(
					'category' => 'assumenda',
					'filters'  =>
						array(
							0 => 'product_cat',
							1 => 'pa_color',
							2 => 'pa_material',
						),
				),
			2 =>
				array(
					'category' => 'sub-sub-assumenda',
					'filters'  =>
						array(
							0 => 'product_cat',
							1 => 'pa_color',
						),
				),
			3 =>
				array(
					'category' => 'quisquam',
					'filters'  =>
						array(
							0 => 'product_cat',
							1 => 'pa_size',
							2 => 'pa_weight',
						),
				),
			4 =>
				array(
					'category' => '/',
					'filters'  =>
						array(
							0 => 'product_cat',
						),
				),
			5 =>
				array(
					'category' => '',
					'filters'  =>
						array(),
				),
		);
	}
}
