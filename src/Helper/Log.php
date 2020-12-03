<?php

namespace Q\Bootstrap\Helper;

class Log {

    private static 
        $empty  = false,
        $file   = \WP_CONTENT_DIR."/debug.log"
    ;

    
	/**
     * Empty Log
     * 
     */
    public static function empty( $args = null ){

		// empty once  ##
		if( 
            self::$empty 
            || ! defined( 'WP_DEBUG' )
            || false === WP_DEBUG // not if debugging is disabled ##
        ) { 

            return false; 

        }

		// empty dedicated log file ##
		$f = @fopen( self::$file, "r+" );
		if ( $f !== false ) {
			
			ftruncate($f, 0);
			fclose($f);

			// track ##
			self::$empty == true;

        }
        
        self::write( 'Emptied..' );

	}

    /**
     * Write to WP Error Log directly
     *
     * @since       0.0.1
     * @return      void
     */
    public static function write( $args = null ){

        // check WP debug status ##
		if( 
            ! defined( 'WP_DEBUG' )
            || false === WP_DEBUG // not if debugging is disabled ##
        ) { 

            return false; 

        }
        
        // error_log( $args );

        // sanity ##
        if ( is_null( $args ) ) { 
            
            error_log( 'Nothing passed to log(), so bailing..' );

            return false; 
        
        }

        // $args can be a string or an array - so fund out ##
        if (  
            is_string( $args )
        ) {

            // default ##
            $log = $args;

        } elseif ( 
            is_array( $args ) 
            && isset( $args['log_string'] )	
        ) {

            // error_log( 'log_string => from $args..' );
            $log = $args['string'];

        } else {
            
            $log = $args;

        } 

        // debugging is on in WP, so write to error_log ##
        // if ( true === WP_DEBUG ) {

        if ( is_array( $log ) || is_object( $log ) ) {
        
            error_log( print_r( $log, true ) );
        
        } else {

            error_log( $log );

        }

        // }
        
        // done ##
        return true;

    }

}
