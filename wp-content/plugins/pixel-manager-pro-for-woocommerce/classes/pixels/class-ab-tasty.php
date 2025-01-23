<?php

namespace WCPM\Classes\Pixels;

use WCPM\Classes\Options;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class AB_Tasty {

	public static function inject_script() {

		// @formatter:off
		?>

		<script type="text/javascript"
				src="https://try.abtasty.com/<?php esc_html_e(Options::get_ab_tasty_account_id()); ?>.js">
		</script>

		<?php
		// @formatter:on
	}
}
