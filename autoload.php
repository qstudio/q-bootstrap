<?php

// pull in Helper, this needs to be loaded before the autoloader
require_once __DIR__ . '/src/Helper/Log.php';
use Q\Bootstrap\Helper\Log as Log;

/**
 * Autoload classes within the namespace `Q\\Bootstrap`
 */
spl_autoload_register( function( $class ) {

	// Log::write( 'Autoload Class: '.$class );

	// project-specific namespace prefix
	$prefix = 'Q\\Bootstrap\\';

	/**
	 * Does the class being called use the namespace prefix?
	 *
	 *  - Compare the first {$len} characters of the class name against our prefix
	 *  - If no match, move to the next registered autoloader
	 */

	// character length of our prefix
	$len = strlen( $prefix );

	// if the first {$len} characters don't match
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {

		// Log::write( 'Autoload Class Rejected, as outside namespace: '.$class );

		return;

	}

	// base directory where our class files and folders live
	$base_dir = __DIR__ . '/src/';

	/**
	 * Perform normalizing operations on the requested class string
	 *
	 * - Remove the prefix from the class name (so that Q\Bootstrap\Plugin looks at src/plugin.php)
	 * - Replace namespace separators with directory separators in the class name
	 * - Prepend the base directory
	 * - Append with .php
	 * - Convert to lower case
	 */
	$class_name = str_replace( $prefix, '', $class );

	// Log::write( 'Class Name: '.$class_name );

	$possible_file = strtolower( $base_dir . str_replace('\\', '/', $class_name ) . '.php' );

	// require the file if it exists
	if( file_exists( $possible_file ) ) {

		// Log::write( 'Loading: '.$possible_file );

		require $possible_file;

	}

});
