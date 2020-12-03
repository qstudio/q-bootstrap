<?php

namespace Q\Bootstrap\Admin;

// import ##
use Q\Bootstrap;
use Q\Bootstrap\Admin;
use Q\Bootstrap\Plugin as Plugin;
use Q\Bootstrap\Helper\Log as Log;

class CPT {

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

        // add rewrite rule ##
        \add_action( 'init', [ $this, 'register_post_type' ], 10 );

        // admin only actions ##
        if( \is_admin() ) {

            // add meta box ##
            \add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 10 );

            // remove meta boxes ##
            \add_action( 'admin_menu', [ $this, 'remove_meta_boxes' ], 10 );

            // remove quick edit option ##
            \add_filter( 'post_row_actions', [ $this, 'post_row_actions' ], 10, 2 );

        }

    }


    /**
     * Remove quick edit options
     * 
     * @since   0.0.2
     * @return  Array
    */
    public function post_row_actions( $actions, $post ){

        // Log::write( $actions );

        // don't touch ##
        if ( 'q_website' != $post->post_type ) {

            return $actions;

        }

        // unset quick edit ##
        unset( $actions['inline hide-if-no-js'] );

        // return array to other filters ##
        return $actions;

    }


    /**
     * Remove page attributes meta box
     * 
     * @since   0.0.1
     * @return  Void
    */
    public function remove_meta_boxes() {

        // remove page attrubutes ##
        \remove_meta_box( 'submitdiv', $this->plugin->get( 'slug' ), 'side' );

        // remove custom fields ##
        \remove_meta_box( 'postcustom', $this->plugin->get( 'slug' ), 'normal');
    
    }
    
    
	/**
     * Add REST endpoint
     * 
     * @since   0.0.1
     * @return  void
     */
    public function register_post_type(){

        // Log::write( $this->options );

        \register_post_type(
            $this->plugin->get( 'slug' ),
            array(
                'labels'        => array(
                    'name'               => __('Website', 'q-websites'),
                    'singular_name'      => __('Website', 'q-websites'),
                    'menu_name'          => __('Website', 'q-websites'),
                    'name_admin_bar'     => __('Websites', 'q-websites'),
                    'all_items'          => __('All Websites', 'q-websites'),
                    'add_new'            => _x('Add New Website', 'prefix_portfolio', 'q-websites'),
                    'add_new_item'       => __('Add New Website', 'q-websites'),
                    'edit_item'          => __('Edit Website', 'q-websites'),
                    'new_item'           => __('New Website', 'q-websites'),
                    'view_item'          => __('View Website', 'q-websites'),
                    'search_items'       => __('Search Websites', 'q-websites'),
                    'not_found'          => __('No Websites found.', 'q-websites'),
                    'not_found_in_trash' => __('No Websites found in Trash.', 'q-websites'),
                    'parent_item_colon'  => __('Parent Websites:', 'q-websites'),
                ),
                'public'                => true,
                'show_in_rest'          => $this->plugin->get( 'rest' )['show_in_rest'], // enable REST ##
                'rest_base'             => $this->plugin->get( 'rest' )['rest_base'], // rest base url ##
                // 'rest_controller_class' => 'WP_REST_Posts_Controller',
                'menu_position'         => 5,
                'supports'              => [
                    'title', 
                    'custom-fields' // for REST, but downside is it adds the CF panel to edit views, which we then remove... ##
                ],
                'publicly_queryable'    => false,
                'has_archive'           => false,
                // no need for rewrite rules ##
                'rewrite'               => false,
                // --> manage cpt caps ##
                'capability_type'       => [ 'q_website', 'q_websites' ],
                'capabilities'          => $this->plugin->get( 'caps' ),
                "map_meta_cap"          => false,
            )
        );

    }


    /**
     * Add meta box to show URL source
     * 
     * @since   0.0.1
     * @return  void
     */
    public function add_meta_boxes() {

        // role filtering --> admin only ##
        if ( ! current_user_can( 'administrator' ) ){ return false; }

        \add_meta_box(
            'q_websites_source',            // $id
            'Source Code',                  // $title
            [ $this, 'render_meta_box' ],   // $callback
            $this->plugin->get( 'slug' ),   // $slug
            'normal',                       // $context
            'high'                          // $priority
        );

    }


    /**
     * Render URL source in a non-editable format
     * 
     * @since   0.0.1
     * @return  void
     */
    public function render_meta_box() {

        // get global post object ##
        global $post;

        // get custom field value ##
        $meta = \get_post_meta( $post->ID, $this->plugin->get( 'meta' )['source'], true ); 

        // fallback, if source code is empty, run get_source() again ##
        if ( ! $meta ){

            // Create new Manage object and run get_source method - pass current $plugin object ##
            $manage = new Admin\Manage( $this->plugin );
            $meta = $manage->get_source([
                'force'     => true,
                'url'       => \get_post_meta( $post->ID, 'q_website_url', true ),
                'post_id'   => $post->ID
            ]);

            // Log::write( $meta );

        }

        // echo escaped value to avoid rendering html ##
        echo \esc_html( $meta );

    }

}
