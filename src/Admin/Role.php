<?php

namespace Q\Bootstrap\Admin;

// import ##
use Q\Bootstrap;
use Q\Bootstrap\Admin;
use Q\Bootstrap\Plugin as Plugin;
use Q\Bootstrap\Helper\Log as Log;

/**
 * A helper class for user roles
 */
class Role {

    /**
     * Plugin Instance
     *
     * @var     Object      $plugin
     */
    private $plugin;

    /**
     * Construct CPT class
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
     * Check if a given role exists
     * 
     * @param   $role   string
     * @since   0.0.2
     * @return  Boolean
    */
    function role_exists( $role = null ){

        return \wp_roles()->is_role( $role );

    }

    /**
     * Edit role object
     * 
     * @since   0.0.2
     * @return  Mixed
    */
    function add_cap( $role = null, Array $caps = [] ) {

        if ( ! \current_user_can( 'edit_others_posts' ) ) {

            Log::write( 'Current user role does not allow access to edit other users' );

            return false;

        }

        // sanity ##
        if(
            is_null( $role )
            || empty( $caps )
        ){

            Log::write( 'Error in params passed to '.__FUNCTION__ );

            return false;

        }

        // check if role exists ##
        if(
            ! $this->role_exists( $role )
        ){

            Log::write( 'Error: unknown role passed: '.$role );

            return false;

        }

        // get role object ##
        $get_role = \get_role( $role );

        foreach( $caps as $cap ) {

            // Log::write( $role .' added:  '.$cap );

            // add cap to role ##
            $get_role->add_cap( \sanitize_key( $cap ), true );

        }
            
    }

    /**
     * Edit role object
     * 
     * @since   0.0.2
     * @return  Mixed
    */
    function remove_cap( $role = null, Array $caps = [] ) {

        if ( ! \current_user_can( 'edit_others_posts' ) ) {

            Log::write( 'Current user role does not allow access to edit other users' );

            return false;

        }

        // sanity ##
        if(
            is_null( $role )
            || empty( $caps )
        ){

            Log::write( 'Error in params passed to '.__FUNCTION__ );

            return false;

        }

        // check if role exists ##
        if(
            ! $this->role_exists( $role )
        ){

            Log::write( 'Error: unknown role passed: '.$role );

            return false;

        }

        // get role object ##
        $get_role = \get_role( $role );

        foreach( $caps as $cap ) {

            // Log::write( $role .' remove:  '.$cap );

            // add cap to role ##
            $get_role->remove_cap( \sanitize_key( $cap ), true );

        }
            
    }

}
