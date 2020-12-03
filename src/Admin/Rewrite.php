<?php

// namespace ##
namespace Q\Bootstrap\Admin;

// import ##
use Q\Bootstrap;
use Q\Bootstrap\Admin;
use Q\Bootstrap\Plugin as Plugin;
use Q\Bootstrap\Helper\Log as Log;

/**
 * A helper class to implement rewrite rules
 */
class Rewrite {

    /**
     * Plugin Instance
     *
     * @var     Object      $plugin
     */
    private $plugin;

    /**
     * Construct Rewrite class
     * 
     * @param   $plugin    Object   instance of Plugin Class
     * @since   0.0.2
     * @return  void
     */
    public function __construct( $plugin = null ) {

        // Log::write( $plugin );

        // grab passed plugin object ## 
        $this->plugin = $plugin;

    }


    /**
     * Filter & Action hook
     * 
     * @since   0.0.1
     * @return  void
     */
    public function hooks() {

        // sanity ##
        if( 
            is_null( $this->plugin )
            || ! ( $this->plugin instanceof Plugin ) 
        ) {

            Log::write( 'Error in object instance passed to '.__CLASS__ );

            return false;
        
        }

         // sanity ##
         if( 
            false === $this->plugin->get( 'path' ) 
            || false === $this->plugin->get( 'query_var' ) 
        ){

            Log::write( 'Error in params passed to '.__CLASS__ );
            
            return false;

        }

        // Log::write( 'rewrite path: '.$this->plugin->get('path') );

        // possibly flush rewrite rules -- only run once ##
        \add_filter( 'init', [ $this, 'perhaps_flush_rewrite_rules' ], 10 );

        // add rewrite rule ##
        \add_filter( 'init', [ $this, 'add_rewrite_rule' ], 20 );

        // add custom query vars ##
        \add_filter( 'query_vars', [ $this, 'query_vars' ], 10 );
        
    }


    /**
     * flush rewrtite rules, if required
     * 
     * @since   0.0.2
     * @return  void
    */
    public function perhaps_flush_rewrite_rules(){

        if ( $option = \get_option( $this->plugin->get( 'config' ), false ) ) {

            // Log::write( $option );

            if( 
                isset( $option['flush_rewrite_rules'] )
                && true === $option['flush_rewrite_rules'] 
            ){

                Log::write( 'Flushing rewrite rules...' );

                // flush rewrite rules ##
                \flush_rewrite_rules();

                // set flag ##
                $option['flush_rewrite_rules'] = false;

                // save option with updated flag ##
                \update_option( $this->plugin->get( 'config' ), $option, true );

            }

        }

    }


    /**
     * Add rewrite rules for websites form
     * 
     * @param   $wp_rewrite     Object
     * @since   0.0.1
     * @return  void
     */
    public function add_rewrite_rule( $wp_rewrite ) {

        // Log::write( 'rewrite path: '.$this->args['path'] );

        // create rule from pass settings ##
        \add_rewrite_rule(
            '^'.$this->plugin->get( 'path' ).'?$', // $path ##
            'index.php?'.$this->plugin->get( 'query_var' ).'=true',
            'top'
        );

    }


    /**
     * Filter that inserts our query variable into the $wp_query
     * 
     * @param   $qvars  Array
     * @since   0.0.1
     * @return  Array
     */
    public function query_vars( $qvars ) {

        $qvars[] = $this->plugin->get( 'query_var' );

        return $qvars;

    }

}
