<?php

// namespace ##
namespace Q\Bootstrap\View;

// import ##
use Q\Bootstrap;
use Q\Bootstrap\Admin;
use Q\Bootstrap\Plugin as Plugin;
use Q\Bootstrap\Helper\Log as Log;

class Render {

    /**
     * Plugin Instance
     *
     * @var     Object      $plugin
     */
    private $plugin;

    /**
     * Construct Render class
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
            || false === $this->plugin->get( 'title' )
        ){

            Log::write( 'Error in params passed to '.__CLASS__ );
            
            return false;

        }

        // check query_var for matching slug ##
        \add_action( 'template_include', [ $this, 'render_check' ], 10 );

        // render form -- if query_var matches ##
        \add_filter( 'template_include', [ $this, 'template_include' ], 20 );

        // add scripts ##
        \add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 10 );

        // add styles ##
        \add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_style' ], 10 );

        // filter the title ##
        \add_filter( 'document_title_parts', [ $this, 'document_title_parts' ], 5 );

    }


    /**
     * Check if "$this->plugin->get( 'slug' )" is set in get_query_var 
     * and set the class prop $render t depending on result - boolean value
     * 
     * @param   $template   String
     * @since   0.0.1
     * @return  void
    */
    public function render_check( $template ):string{

        // Log::write( \get_query_var( $this->plugin->get( 'slug' ) ) );

        // set class property ( bool ) ##
        $this->render = (bool) \get_query_var( $this->plugin->get( 'slug' ) );

        // return $template ##
        return $template;

    }


    /**
     * Return a boolean to determine if the websites UI should render or return assets
     * 
     * @since   0.0.1
     * @return  Boolean
    */
    protected function should_render() :bool{

        return $this->render;

    }



    /**
     * Filter that maps the query variable to a template
     * 
     * @param   $template   String
     * @since   0.0.1
     * @return  void
     */
    function template_include( $template ):string {

        // sanity - respect other rules and return $template if no match found ##
        if ( ! $this->should_render() ) {

            // Log::write( 'Stop here..' );

            return $template;

            exit;

        }

        // barebones theme ##
        \get_header();

        // render ##
        $this->render();

        // barebones theme ##
        \get_footer();

        // exit ##
        exit;

    }

    
    /**
     * Render form
     * 
     * @todo    separate template markup
     * @since   0.0.1
     * @return  string
    */
    protected function render():string {

        ob_start();

?>
<div class="container">
    <h1>Submit a Website</h1>
    <?php $this->feedback(); ?>
    <form id="q_websites_form" class="needs-validation" method="post" novalidate>
        <div class="form-group">
            <label class="" for="websites_name">Your Name</label>
            <input type="text" class="form-control mb-2" id="websites_name" name="websites_name" placeholder="Jane Doe" value="<?php $this->value( 'name' ); ?>" required>
            <div class="valid-feedback">
                Great name :)
            </div>
            <div class="invalid-feedback">
                Please provide your name.
            </div>
            <small id="nameHelp" class="form-text text-muted">You can invent a name, if you are shy...</small>
        </div>
        <div class="form-group">
            <label class="" for="websites_url">Your Website URL</label>
            <input type="text" class="form-control" id="websites_url" name="websites_url" placeholder="google.com"  value="<?php $this->value( 'url' ); ?>" required>
            <div class="valid-feedback">
                Nice URL :)
            </div>
            <div class="invalid-feedback">
                Please provide a valid URL.
            </div>
            <small id="urlHelp" class="form-text text-muted">You should include the protocol also.</small>
        </div>
        <div class="form-group mb-5">
            <input type="hidden" name="action" value="q_websites_create" />
            <input type="hidden" name="q_websites_nonce" value="<?php echo \wp_create_nonce( 'q_websites_nonce' ); ?>" />
            <button type="submit" class="btn btn-primary btn-lg mt-4">Submit</button>
        </div>
    </form>
</div>
<?php

        // grab string from buffer ##      
        $string = ob_get_clean();

        // filter ##
        $string = \apply_filters( 'q/websites/render/markup', $string );

        // echo string ##
        echo $string;

        // kick string back, in case ##
        return $string;

    }


    /**
     * Render feedback to the user
     * 
     * @since   0.0.1
     * @return  string
    */
    protected function feedback(){

        // feedback should only happen if the $_GET string includes 'q_code' param ##
        if(
            ! isset( $_GET )
            || ! isset( $_GET['q_code'] )
        ){

            return false;

        }

        // sanitize ##
        $code = \sanitize_text_field( $_GET['q_code'] );

        switch ( $code ){

            default :
            case 'error':

                $string = \__( 'An unknown error happened...', 'q-websites' );

            break ;

            case 'success':

                $string = \__( 'Your submission was saved', 'q-websites' );

            break ;

            case 'non_unique':

                $string = \__( 'You have already submitted that URL', 'q-websites' );

            break ;

            case 'bad_url':

                $string = \__( 'There is a problem with the format of the URL', 'q-websites' );

            break ;

            case 'security':

                $string = \__( 'There was a problem with the form submission.', 'q-websites' );

            break ;

            // @todo - add more ##

        }

        // filter ##
        $string = \apply_filters( 'q/websites/render/feedback', $string );

        // wrap string in markup - ugly, but effective ##
        $markup = sprintf( 
            '<div class="row feedback"><div class="col-12 py-5"><div class="p-3 bg-info text-white">%s</div></div></div>',
            $string
        );

        // test ##
        // Log::write( $markup );

        // echo value ##
        echo $markup;
        
        // done ##
        return true;

    }


    /**
     * Get some random names, for testing
     * 
     * @since   0.0.2
     * @return  Array
    */
    private function get_test_name(){

        $array = [
            'Paulos', 'Petros', 'Gabreal', 'Giorgis', 'Yonas'
        ];

        // get random value ##
        $k = array_rand($array);

        // return ##
        return $array[$k];

    }

    /**
     * Get some random domains, for testing
     * 
     * @since   0.0.2
     * @return  Array
    */
    private function get_test_url(){

        $array = [
            'google.com', 'youtube.com', 'tri.be', 'stackoverflow.com', 'github.com'
        ];

        // get random value ##
        $k = array_rand($array);

        // return with protocal prefix ##
        return 'https://'.$array[$k];

    }


    /**
     * Display url or name if returned from error check
     * Also generates random values is debugging to speed up testing
     * 
     * @since   0.0.1
     * @return  string
    */
    protected function value( $type = 'url', $len = 10 ){

        // check if q_url or q_name is set and sanitize ##
        $url = isset( $_GET['q_url'] ) ? \esc_url_raw( $_GET['q_url'] ) : '' ;
        $name = isset( $_GET['q_name'] ) ? \sanitize_text_field( $_GET['q_name'] ) : '' ;

        switch( $type ) {

            case 'url':

                // url set - so use it ##
                if ( $url ) {
                    
                    echo \esc_url( $url );

                    break;

                }

                // if debugging, echo random url - else nothing ##
                echo $this->plugin->get( 'debug' ) ? \esc_url( $this->get_test_url() ) : '' ;

            break ;

            case 'name':

                // name set - so use it ##
                if ( $name ) {
                    
                    echo \esc_attr( $name );

                    break;

                }

                // if debugging, echo random name - else nothing ##
                echo $this->plugin->get( 'debug' ) ? \esc_attr( $this->get_test_name() ) : '' ;

            break ;

        }

        // done ##
        return true;

    }



    /**
     * Manipuate html title tag
     * 
     * @param   $title_parts    Array
     * @since   0.0.1
     * @return  Array
    */
    public function document_title_parts( $title_parts ){

        // Log::write( 'here: '.$title_parts['title'] );

        // if we are not on the plugin slug, stop here #
        if ( ! $this->should_render() ) {

            // Log::write( 'Stop here..' );

            return $title_parts;

        }

        // filter title -- backup in case class property is empty ##
        $title_parts['title'] = $this->plugin->get( 'title' ) ?? $title_parts['title'];

        // return array ##
        return $title_parts;

    }



    /**
     * Enqueue JS scripts
     * 
     * @since   0.0.1
     * @return  void
    */
    public function wp_enqueue_scripts() {

        // if we are not on the plugin slug, stop here #
        if ( ! $this->should_render() ) {

            // Log::write( 'Stop here..' );

            return false;

        }

        // Log::write( $this->get_url( 'asset/scripts.js' ) );

        // define min from debug boolean ##
        $min = $this->plugin->get( 'debug' ) ? '' : '.min' ;

        // bootstrap ##
        \wp_enqueue_script( 'q-bootstrap-js', $this->plugin->get_url( 'asset/bootstrap'.$min.'.js' ), [ 'jquery' ], $this->plugin::version, true );

        // plugin scripts - for validation ##
        \wp_enqueue_script( 'q-websites-js', $this->plugin->get_url( 'asset/scripts.js' ), [ 'jquery' ], $this->plugin::version, true );

        // localize feedback ##
        $translation_array = array(
            'ajaxurl'           => \admin_url( 'admin-ajax.php' ), // q_websites.ajaxurl
            'debug'             => $this->plugin->get( 'debug' ), // q_websites.debug
            'nonce'             => \wp_create_nonce( 'q_websites_nonce' ), // q_websites.nonce
            'invalid_name'      => \__( 'Please enter a valid name', 'q-websites' ),
            'invalid_url'       => \__( 'Please enter a valid URL', 'q-websites' ),
        );
        \wp_localize_script( 'q-websites-js', 'q_websites', $translation_array );

    }


    /**
     * Enqueue CSS assets
     * 
     * @since   0.0.1
     * @return  void
    */
    public function wp_enqueue_style() {

        // if we are not on the q_websites slug, stop here #
        if ( ! $this->should_render() ) {

            // Log::write( 'Stop here..' );

            return false;

        }

        // Log::write( $this->get_url( 'asset/style.css' ) );

        // define min from debug boolean ##
        $min = $this->plugin->get( 'debug' ) ? '' : '.min' ;

        // bootstrap ##
        \wp_enqueue_style( 'q-bootstrap-css', $this->plugin->get_url( 'asset/bootstrap'.$min.'.css' ), [], $this->plugin::version, 'all' );

        // plugin styles -- optional ##
        // \wp_register_style( 'q-websites-css', $this->get_url( 'asset/style.css' ), '', self::version, 'all' );
        // \wp_enqueue_style( 'q-websites-css' );

    }

}
