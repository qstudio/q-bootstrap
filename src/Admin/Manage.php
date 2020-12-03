<?php

namespace Q\Bootstrap\Admin;

// import ##
use Q\Bootstrap;
use Q\Bootstrap\Admin;
use Q\Bootstrap\Plugin as Plugin;
use Q\Bootstrap\Helper\Log as Log;

class Manage {

    /**
     * Plugin Instance
     *
     * @var     Object      $plugin
     */
    private $plugin;

    /**
     * Construct Manage class
     * 
     * @param   $plugin    Object   instance of Plugin Class
     * @since   0.0.2
     * @return  void
     */
    public function __construct( $plugin = null ) {

        // grab passed plugin object ## 
        $this->plugin = $plugin;

        // Log::write( $plugin );

        // get all plugin props ##
        $this->props = $this->plugin->get();

        // test props ##
        // Log::write( $this->props );

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
            ! isset( $this->props['slug'] ) 
            || ! isset( $this->props['path'] ) 
            || ! isset( $this->props['meta'] ) 
            || ! is_array( $this->props['meta'] ) 
        ){

            Log::write( 'Error in params passed to '.__CLASS__ );
            
            return false;

        }

        // check query_var for matching slug ##
        \add_action( 'init', [ $this, 'save' ], 10 );

    }


    /**
     * Save posted data from front-end form
     * 
     * @since   0.0.1
     * @return  Mixed
    */
    public function save(){

        // check if posted data matches execpted action and format
        if ( 
            'POST' != $_SERVER['REQUEST_METHOD'] 
            || empty( $_POST['action'] ) 
            || $_POST['action'] != "q_websites_create"
        ) {

            // Log::write( 'Not our form..' );

            // nothing cooking ##
            return false;

        }

        // validate nonce ##
        $nonce = $_POST['q_websites_nonce'];

        if ( 
            ! wp_verify_nonce( $nonce, 'q_websites_nonce' ) 
        ){

            // Log::write( 'Nonce failed' );

            // nothing cooking ##
            $this->redirect( 'security' );

        }

        // validate if name + url were submitted ##
        if (
            ! isset( $_POST['websites_name'] )
            || ! isset( $_POST['websites_url'] )
        ){

            // Log::write( 'Rejected for missing data' );

            $this->redirect( 'missing_data' );

        }

        // ok, check for required post data ##
        $name = $_POST['websites_name'];
        $url = $_POST['websites_url'];

        // Log::write( 'name: '.$name.' -- url: '.$url );
    
        // sanitize input data ##
        $name = \sanitize_text_field( $name );
        $url = \esc_url_raw( $url );

        // store variables in class props ##
        $this->props['url'] = $url;
        $this->props['name'] = $name;

        // validate URL is genuine --note this has notable weeknesses - it would be better to request headers and check against 200 code ##
        if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {

            // Log::write( 'Rejected for bad URL' );

            $this->redirect( 'bad_url' );

        }

        // quick check of URL headers ##
        if( false === strpos( @get_headers( $url )[0], '200' ) ){

            Log::write( 'Rejected for bad URL @ header lookup' );

            $this->redirect( 'bad_url' );

        }

        // now, we need to validate if the submission is unique ##
        if ( ! $this->is_unique() ){

            // Log::write( 'Rejected for non-unique data' );

            $this->redirect( 'non_unique' );

        }

        // create a post with the new data ##
        $new_post = [
            'post_title'    => $this->props['url'].' by '.$this->props['name'], // concat url + name to form post title - odd, but easy
            // 'post_content'  => $this->props['name'],
            'post_status'   => 'publish',          
            'post_type'     => $this->props['slug'] // define post type ##
        ];
            
        // insert the post into database by passing $new_post to wp_insert_post ##
        if ( false === $this->wp_insert_post( $new_post ) ){

            Log::write( 'Error inserting new post object' );

            // optional debug feedback ##
            // Log::write( $wp_insert_post );

            // stop here ##
            return false;

        }
            
        // store the raw url posted -- in case of failures in scrapping source ##
        \add_post_meta( $this->props['post_id'], $this->props[ 'meta' ]['url'], $this->props['url'], true );

        // store the name posted ##
        \add_post_meta( $this->props['post_id'], $this->props[ 'meta' ]['name'], $this->props['name'], true );

        // set source meta field to false ##
        \add_post_meta( $this->props['post_id'], $this->props[ 'meta' ]['source'], false, true ); 

        // save websites map ##
        $this->set_map();

        // now, try and scrape source and update post_meta with results ##
        $source = $this->get_source();

        // finished, redirect with success code ##
        $this->redirect( 'success' );

    }

    /**
     * Validate creation of new WP post
     * 
     * @param   $new_post     Array   post args
     * @since   0.0.2       
     * @return  Mixed|Boolean|Integer
    */
    protected function wp_insert_post( Array $new_post = null ){

        // sanity ##
        if( is_null( $new_post ) ){

            return false;

        }

        // run insert routine - define return to wp_error ##
        $wp_insert_post = \wp_insert_post( $new_post, true ); 

        if( \is_wp_error( $wp_insert_post ) ) {

            return false;

        }

        // Log::write( 'wp_insert_post: '.$wp_insert_post );

        // set post_id prop ##
        $this->plugin->set( 'post_id', $wp_insert_post );

        // update local prop ##
        $this->props['post_id'] = $wp_insert_post;

        // all good ##
        return true;

    }



    /**
     * redirect back to form, with extra vars in querystring
     * 
     * @since   0.0.1
     * @return  void
    */
    protected function redirect( $code = 'bad_url' ){

        // bust cache ##
        \nocache_headers();

        // redirect with extra query arg ##
        \wp_redirect( 
            // \esc_url( 
                \add_query_arg( 
                    [
                        'q_code'    => $code,
                        'q_name'    => $this->props['name'],
                        'q_url'     => $this->props['url']
                    ], 
                    \home_url( 
                        $this->props['path'] 
                    ) 
                ) 
            // ) 
        );
            
        // stop ##
        exit;

    }


    /**
     * Get stored data array for wp_options table
     * 
     * @since   0.0.1
     * @return  Mixed
    */
    protected function get_map(){

        // cache ##
        if( false !== $this->props['map'] ) { 

            // Log::write( 'Using cached map' );
            
            return $this->props['map']; 

        }

        // get ##
        $option = \get_option( $this->props['option'], false );

        // Log::write( 'Loading map from DB' );
        // Log::write( $option );

        // grab from DB, set class prop and return -- note defaults to return bool false if key is not found ##
        return $this->props['map'] = $option;

    }


    /**
     * If a website url is saved, we need to update the store map to include it
     * 
     * @since   0.0.1
     * @return  boolean
    */
    protected function set_map(): bool {

        // sanity checks ##
        if (
            ! $this->props['url']
            || ! $this->props['name']
        ){

            Log::write( 'Error in passed params: '.__FUNCTION__ );

            return false;

        }

        // get the stored map ##
        $this->get_map();

        // validate stored data ##
        if ( 
            ! $this->props['map'] 
            || ! is_array( $this->props['map'] )
            // || empty( $this->props['map'] )
        ){

            Log::write( 'Stored map is empty, perhaps no data is stored yet, so creating empty array' );

            $this->props['map'] = [];

        } 

        // now, see if the user already exists in the map ##
        if(
            ! array_key_exists( $this->props['name'], $this->props['map'] )
            || ! is_array( $this->props['map'][$this->props['name']] )
        ){

            // create a new array ##
            $this->props['map'][ $this->props['name'] ] = [];

        }

        // add url to array map for user ##
        $this->props['map'][ $this->props['name'] ][] = $this->props['url'];

        // store option ##
        $return = \update_option( $this->props['option'], $this->props['map'] );

        // check ##
        // Log::write( 'updated options: '.$return );

        // done ##
        return $return;

    }


    /**      
     * Check if the submitted url and name are unique
     * 
     * @since   0.0.1
     * @return  Mixed
     *  
    */
    protected function is_unique():bool {

        // sanity checks ##
        if (
            ! $this->props['url']
            || ! $this->props['name']
        ){

            Log::write( 'Error in passed params'.__FUNCTION__ );

            return false;

        }

        // names + urls are stored in one field in wp_options - so grab ##
        $this->get_map();

        /*
        // data model ##
        $this->props['map'] = [
            'one'   => [ 
                'https://google.com',
                'https://domain.com'
            ],
            'two'   => [
                'https://domain.com'
            ]
        ];
        */

        // check values in array ##
        // Log::write( $this->props['map'] );
        
        // validate stored data ##
        if ( 
            ! $this->props['map'] 
            || ! is_array( $this->props['map'] )
            || empty( $this->props['map'] )
        ){

            Log::write( 'Stored map is empty, perhaps no data is stored yet, so returning true' );

            return true;

        } 

        // let's see if we find a matching name ( key ) and url ( value ) in the array ##
        // note - that domains can be repeated, if added by different users and users can add multiple domains ##

        // first - check if the user name exists ( key ) ##
        if(
            ! array_key_exists( $this->props['name'], $this->props['map'] )
        ){

            Log::write( 'The user: '.$this->props['name'].' has no websites stored' );

            return true;

        }

        // now, check if the user has an array of stored websites and if the url already exists in their array ##
        if(
            ! is_array( $this->props['map'][$this->props['name']] )
            || ! in_array( $this->props['url'], $this->props['map'][$this->props['name']] )
        ){

            Log::write( 'The url '.$this->props['url'].' is not in the array of user: '.$this->props['name'] );

            return true;

        }

        Log::write( 'The url '.$this->props['url'].' IS in the array of user: '.$this->props['name'] );

        // the user has the website stored, so reject ##
        return false;

    }


    /**
     * Get source HTML from URL
     * 
     * @todo    potential issues with timeout, might need CRON backup to schedule re-run
     * @since   0.0.1
     * @return  Mixed
    */
    function get_source( Array $args = [] ){

        // Log::write( $args );

        // allow url and post_id to be passed, for retroactive scrapping
        if(
            isset( $args['force'] )
            && isset( $args['url'] )
            && isset( $args['post_id'] )
        ){

            Log::write( 'Forcing url to: '.$args['url'] );

            $this->props['url'] = \esc_url_raw( $args['url'] );
            $this->props['post_id'] = \sanitize_text_field( $args['post_id'] );

        }

        // sanity checks ##
        if (
            ! $this->props['url']
            || ! $this->props['post_id']
        ){

            Log::write( 'Error in passed params: '.__FUNCTION__ );

            return false;

        }

        // request data from URL ##
        $response = \wp_remote_get( 
            \esc_url_raw( $this->props['url'] ),
            [
                'headers'           => [
                    'timeout'       => 120,
                    'httpversion'   => '1.1',
                    'referer'       => \home_url() // set referrer ##
                ]
            ]
        );

        // validate return ##
        if ( 
            \is_wp_error( $response ) 
        ) {
            
            Log::write( 'Error in response data, logged to post_meta field' );
            Log::write( $response );

            // storing error, to give feedback inside admin ##
            \update_post_meta( $this->props['post_id'], $this->props[ 'meta' ]['source'], $response->get_error_message() ); 

            // stop --> this will run again if the page is edited in the admin ##
            return false;

        }

        // get response values ##
        // $code = \wp_remote_retrieve_response_code( $response ); // code ##
        $headers = $response['headers']; // array of http header lines ##
        $body = $response['body']; // raw body content -- html ##

        // check ##
        Log::write( 'Body returned - length: '.strlen( $body ) );

        // store results -->unfiltered ##
        \update_post_meta( $this->props['post_id'], $this->props[ 'meta' ]['source'], $body ); 

        // return html ##
        return $body;

    }

}
