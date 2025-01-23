<?php

namespace WCPM\Classes\Pixels\Google;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Google_Analytics extends Google {

	public function __construct( $options ) {
		parent::__construct($options);

		$this->pixel_name = 'google_analytics';
	}
}
