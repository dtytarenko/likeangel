<?php

namespace WCPM\Classes\Pixels;

use WCPM\Classes\Options;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Optimizely {

	public static function enqueue_scripts() {

		wp_enqueue_script(
			'optimizely',
			'https://cdn.optimizely.com/js/' . Options::get_optimizely_project_id() . '.js',
			[ 'jquery' ],
			PMW_CURRENT_VERSION,
			false
		);
	}
}
