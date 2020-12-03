<?php

/**
 * @package   Q Bootstrap
 * @author    Q Studio <social@qstudio.us>
 * @copyright 2020
 * @license   GPL 3
 * @link      qstudio.us
 *
 * Plugin Name:     Q Bootstrap Plugin
 * Description:     Default starter plugin base
 * Version:         0.0.1
 * Author:          Q Studio
 * Text Domain:     q-bootstrap
 * Domain Path:     /languages
 * Requires PHP:    7.0
 */

// namespace plugin ##
namespace Q\Bootstrap;

// import classes ##
use Q\Bootstrap;
use Q\Bootstrap\Helper\Log as Log;
use Q\Bootstrap\Plugin as Plugin;

// If this file is called directly, Bulk!
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Plugin class ##
require_once __DIR__ . '/Plugin.php';

// plugin activation hook to store current application and plugin state ##
\register_activation_hook( __FILE__, [ '\\Q\\Bootstrap\\Plugin', 'activation_hook' ] );

// plugin deactivation hook - clear stored data ##
\register_deactivation_hook( __FILE__, [ '\\Q\\Bootstrap\\Plugin', 'deactivation_hook' ] );

// clear log file - only run if debugging ##
// \add_action( 'plugins_loaded', [ new Helper\Log(), 'empty' ], 0 );

// define plugin config ##
$args = [
    'prop'	=> false
];

// add plugin args to filter ~~ allow for manipulation before instatiation ##
\add_filter( 'Q\Bootstrap/args', function() use ( $args ) {
    return $args;
} );

// get plugin instance ##
$plugin = Plugin::get_instance();

// validate instance ##
if( ! ( $plugin instanceof Plugin ) ) {

    Log::write( 'Error in Plugin instance' );

    // nothing else to do here ##
    exit;

}

// check instance ##
// Log::write( \apply_filters( 'Q\Bootstrap/instance', NULL ) );

// main plugin hooks - translations etc ## 
\add_action( 'init', [ $plugin, 'hooks' ], 1 );

// Add rewrite rule ###
\add_action( 'init', [ new Admin\Rewrite( $plugin ), 'hooks' ], 5 );

// Add role caps - this is only run once, as role changes are stored in the db ##
\add_action( 'init', function() use ( $plugin ){

    $config = \get_option( $plugin->get( 'config' ), false );
    // Log::write( $config );

    // define role caps once ##
    if ( $config ) {

        if( 
            isset( $config['role_caps'] )
            && true === $config['role_caps'] 
        ){

            Log::write( 'Updating user roles...' );

            // instatiate role object ##
            $role = new Admin\Role( $plugin );

            // edit editor role ##
            $role->add_cap(
                'editor',
                $plugin->get( 'role' )['add']
            );

            // edit editor role ##
            $role->remove_cap(
                'editor',
                $plugin->get( 'role' )['remove']
            );

            // edit administrator role ##
            $role->add_cap(
                'administrator',
                $plugin->get( 'role' )['add']
            );

            // edit administrator role ##
            $role->remove_cap(
                'administrator',
                $plugin->get( 'role' )['remove']
            );

            // set flag ##
            $config['role_caps'] = false;

            // Log::write( $config );

            // save option with updated flag to avoid setting rules again ##
            \update_option( $plugin->get( 'config' ), $config );

        }

    }
    
}, 5 );

// Setup CPT ##
\add_action( 'init', [ new Admin\CPT( $plugin ), 'hooks' ], 5 );

// Add API routes and extend returned object ##
// Note that the endpoint is accessible via '/wp-json/websites/v1/get'
\add_action( 'rest_api_init', [ new Rest\Endpoint( $plugin ), 'hooks' ], 5 );

// Set-up CPT post saving hooks ##
\add_action( 'init', [ new Admin\Manage( $plugin ), 'hooks' ], 5 );

// Render front-end view ##
\add_action( 'setup_theme', [ new View\Render( $plugin ), 'hooks' ], 5 );
