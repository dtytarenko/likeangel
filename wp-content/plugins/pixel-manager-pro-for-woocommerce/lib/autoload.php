<?php

/**
 * Example:
 * Class name: SomeThing
 * File name: class-something.php
 *
 * Class name: Some_Thing
 * File name: class-some-thing.php
 *
 * Namespaces must reflect the folder structure exactly
 *
 * https://github.com/tommcfarlin/simple-autoloader-for-wordpress/
 */

$plugin_class_name = 'WCPM';

spl_autoload_register(function ( $filename ) use ( $plugin_class_name ) {

//	error_log( 'autoload: ' . $filename );

	// First, separate the components of the incoming file.
	$file_path = explode('\\', $filename);

	/**
	 * - The first index will always be the namespace since it's part of the plugin.
	 * - All but the last index will be the path to the file.
	 * - The final index will be the filename. If it doesn't begin with 'I' then it's a class.
	 */

	// Get the last index of the array. This is the class we're loading.
	$file_name = '';

	// If the first value of the $file_path array is not "WCPM" then return.
	if ($plugin_class_name !== $file_path[0]) {
		return;
	}

	if (isset($file_path[count($file_path) - 1])) {

//		error_log( 'file_path: ' . print_r( $file_path, true ) );
//		error_log( 'file_path: ' . $file_path[count($file_path) - 1] );

		$file_name = strtolower(
			$file_path[count($file_path) - 1]
		);

		$file_name       = str_ireplace('_', '-', $file_name);
		$file_name_parts = explode('-', $file_name);

		/**
		 * Interface and Trait support
		 * Handle both Interface_Foo or Foo_Interface (or Trait_Foo or Foo_Trait)
		 *
		 * File name format: interface-foo.php (or trait-foo.php)
		 * Class name format: class Interface_Foo {} (or class Trait_Foo {})
		 */

		$index_interface = array_search('interface', $file_name_parts);
		$index_trait     = array_search('trait', $file_name_parts);

		if (false !== $index_interface) {
			// Remove the 'interface' part.
			unset($file_name_parts[$index_interface]);

			// Rebuild the file name.
			$file_name = implode('-', $file_name_parts);

			$file_name = "interface-{$file_name}.php";
//            error_log('interface: ' . $file_name);

		} elseif (false !== $index_trait) {
			// Remove the 'trait' part.
			unset($file_name_parts[$index_trait]);

			// Rebuild the file name.
			$file_name = implode('-', $file_name_parts);

			$file_name = "trait-{$file_name}.php";
//            error_log('trait: ' . $file_name);
		} else {
			$file_name = "class-$file_name.php";
		}
	}

	/**
	 * Find the fully qualified path to the class file by iterating through the $file_path array.
	 * We ignore the first index since it's always the top-level package. The last index is always
	 * the file so we append that at the end.
	 */
	$fully_qualified_path = trailingslashit(
		dirname(
			__DIR__
		)
	);

	for ($i = 1; $i < count($file_path) - 1; $i++) {

		$dir                  = strtolower($file_path[$i]);
		$fully_qualified_path .= trailingslashit($dir);
	}
	$fully_qualified_path .= $file_name;

	// Now include the file.
	if (stream_resolve_include_path($fully_qualified_path)) {
		include_once $fully_qualified_path;
	}
});
