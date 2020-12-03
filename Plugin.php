<?php

namespace Q\Bootstrap;

// import classes ##
use Q\Bootstrap;
use Q\Bootstrap\Helper\Log as Log;
use Q\Bootstrap\Plugin as Plugin;

// If this file is called directly, Bulk!
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// autoloader ##
require_once __DIR__ . '/autoload.php';

/*
* Main Plugin Class
*/
final class Plugin {

    /**
     * @var     Array   $props
     */
    protected $props = [
        'debug'		    => \WP_DEBUG, // boolean --> control debugging / minification etc ##
    ];

    /**
     * Plugin version 
     *
     * @var    Constant     version
    */
    const version = '0.0.1';

    /**
     * Instance
     *
     * @var     Object      $instance
     */
    private static $instance;

    /**
     * Initiator
     *
     * @since   0.0.2
     * @return  Object    
     */
    public static function get_instance() {

        // object defined once --> singleton ##
        if ( 
            isset( self::$instance ) 
            && NULL !== self::$instance
        ){

            return self::$instance;

        }

        // create an object, if null ##
        self::$instance = new self;

        // store instance in filter, for potential external access ##
        \add_filter( __NAMESPACE__.'/instance', function() {

            return self::$instance;
            
        });

        // return the object ##
        return self::$instance; 

    }

    /**
     * Class constructor to define object props 
     * 
     * @since   0.0.1
     * @return  void
    */
    private function __construct() {

        // retrieve args from filter - allowing for manipulation ##
        $args = \apply_filters( __NAMESPACE__.'/args', NULL );

        // store passed args and merge with default class props ##
        $this->props = array_merge( $this->props, $args );

        // Log::write( $this->props );

    }

    /**
     * Get stored object property
     * 
     * @param   $key    string
     * @since   0.0.2
     * @return  Mixed
    */
    public function get( $key = null ) {

        // return all, if no key set ##
        if( is_null( $key ) ){

            // return false;
            return $this->props;

        }
        
        // return if isset ##
        return $this->props[$key] ?? false ;

    }

    /**
     * Set stored object properties 
     * 
     * @param   $key    string
     * @param   $value  Mixed
     * @since   0.0.2
     * @return  Mixed
    */
    public function set( $key = null, $value = null ) {

        // sanity ##
        if( 
            is_null( $key ) 
        ){

            return false;

        }

        // sanitize -- required ?? ##
        // $key = \sanitize_key( $key );
        // $value = ( is_array( $value ) || is_object( $value ) ) ? $value = array_map( 'sanitize_title', $value ) : \sanitize_key( $value );

        // Log::write( 'prop->set: '.$key.' -> '.$value );

        // set new value ##
        $this->props[ $key ] = $value;

    }
	
	/**
     * callback method for class instantiation
     *
     * @since   0.0.2
     * @return  void
     */
	public function hooks() {

        // set text domain on init hook ##
        \add_action( 'init', [ $this, 'load_plugin_textdomain' ], 1 );

    }

    /**
     * Load Text Domain for translations
     *
     * @since       0.0.1
     * @return      Void
     */
    public function load_plugin_textdomain(){

        // The "plugin_locale" filter is also used in load_plugin_textdomain()
        $locale = apply_filters( 'plugin_locale', \get_locale(), 'q-websites' );

        // try from global WP location first ##
        \load_textdomain( 'q-websites', WP_LANG_DIR.'/plugins/websites-'.$locale.'.mo' );

        // try from plugin last ##
        \load_plugin_textdomain( 'q-websites', FALSE, \plugin_dir_path( __FILE__ ).'src/languages/' );

    }

    /**
     * Plugin activation
     *
     * @since   0.0.1
     * @return  void
     */
    public static function activation_hook(){

        Log::write( 'Plugin Activated..' );

        // check user caps ##
        if ( ! \current_user_can( 'activate_plugins' ) ) {
            
            return;

        }

        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        \check_admin_referer( "activate-plugin_{$plugin}" );

        // store data about the current plugin state at activation point ##
        $config = [
            'configured'            => true , 
            'version'               => self::version ,
            'wp'                    => \get_bloginfo( 'version' ) ?? null ,
			'timestamp'             => time(),
			// add custom props here ##
        ];

        // Log::write( $config );

        // activation running, so update configuration flag ##
        \update_option( 'q_bootstrap', $config, true );

        // flush permalinks to enable new rewrite rules to work ##
        \flush_rewrite_rules();
        
    }

    /**
     * Plugin deactivation
     *
     * @since   0.0.1
     * @return  void
     */
    public static function deactivation_hook(){

        Log::write( 'Plugin De-activated..' );

        // check user caps ##
        if ( ! \current_user_can( 'activate_plugins' ) ) {
        
            return;
        
        }

        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        \check_admin_referer( "deactivate-plugin_{$plugin}" );

        // de-configure plugin ##
        \delete_option('q_bootstrap');

        // clear rewrite rules ##
        \flush_rewrite_rules();

    }

    /**
     * Get Plugin URL
     *
     * @since       0.1
     * @param       string      $path   Path to plugin directory
     * @return      string      Absoulte URL to plugin directory
     */
    public function get_url( $path = '' ){

        return \plugins_url( $path, __FILE__ );

    }

    /**
     * Get Plugin Path
     *
     * @since       0.1
     * @param       string      $path   Path to plugin directory
     * @return      string      Absoulte URL to plugin directory
     */
    public function get_path( $path = '' ){

        return \plugin_dir_path( __FILE__ ).$path;

    }
    
}
