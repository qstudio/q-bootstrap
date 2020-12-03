<?php

namespace Q\Bootstrap\Rest;

// import ##
use Q\Bootstrap;
use Q\Bootstrap\Admin;
use Q\Bootstrap\Plugin as Plugin;
use Q\Bootstrap\Helper\Log as Log;

class Endpoint {

    /**
     * Plugin Instance
     *
     * @var     Object      $plugin
     */
    private $plugin;

    /**
     * Construct Endpoint class
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
            false === $this->plugin->get( 'slug' )
        ){

            Log::write( 'Error in params passed to '.__CLASS__ );
            
            return false;

        }

        // register new REST route for websites date ##
        \add_action( 'rest_api_init', [ $this, 'register_rest_route' ], 10, 1 );

    }


    /**
     * Add additional post meta data to REST output
     * 
     * @param   $wp_rest_server   
     * @since   0.0.2
     * @return  void
    */
    public function register_rest_route( $wp_rest_server ) {

        // Basic endpoint security -- allows admin + editor ##
        if ( 
            ! current_user_can( 'administrator' )
            && ! current_user_can( 'editor' ) 
        ) {

            return;

        }

        \register_rest_route( $this->plugin->get( 'rest' )['namespace'], $this->plugin->get( 'rest' )['route'], [
            'methods'   => 'GET',
            'callback'  => [ $this, 'get_data' ]
        ]);

    }
    
    /**
     * Return data to websites endpoint
     * 
     * @since   0.0.2
     * @return  Mixed
    */
    public function get_data(){

        // get posts ##
        $posts = \get_posts( $this->plugin->get( 'rest' )['query'] );
         
        // validate returned data ##
        if ( empty( $posts ) ) {

            return null;

        }

        // create new return object and hand pick data - bit ugly.. ##
        $return = [];

        // loop posts ##
        foreach( $posts as $post ) {

            // get all post meta in single query - per post ##
            $meta = \get_post_meta( $post->ID );

            // add new array key + values ##
            $return[] = [
                'id'        => $post->ID,
                'title'     => $post->post_title,
                'source'    => $meta[ $this->plugin->get( 'meta' )['source'] ]
            ];
         
        }

        // Log::write( $return );

        // return array to REST ##
        return $return;

    }


}
