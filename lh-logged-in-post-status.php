<?php
/**
 * Plugin Name: LH Logged in Post status
 * Plugin URI: https://lhero.org/portfolio/lh-logged-in-post-status/
 * Description: Simple plugin to make posts accessible only to logged in users
 * Author: Peter Shaw
 * Text Domain: lh_logged_in_post_status
 * Domain Path: /languages
 * Version: 1.09
 * Author URI: https://shawfactor.com/
 * License: http://www.gnu.org/copyleft/gpl.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('WP_Statuses')) {
    
    include_once("includes/wp-statuses/wp-statuses.php");
    
}

/**
* LH Logged in post status plugin class
*/


if (!class_exists('LH_logged_in_post_status_plugin')) {


class LH_logged_in_post_status_plugin {

    private static $instance;

    static function return_plugin_namespace(){
    
        return 'lh_logged_in_post_status';
    
    }
    
    static function plugin_name(){
        
        return 'LH Logged in post status';
        
    }
    
    static function return_new_status_name(){
    
        return 'logged_in';
    
    }
    
    static function return_new_status_label(){
    
    return __('logged In', self::return_plugin_namespace());
    
    }

    static function curpageurl() {
    	$pageURL = 'http';
    
    	if ((isset($_SERVER["HTTPS"])) && ($_SERVER["HTTPS"] == "on")){
    	    
    		$pageURL .= "s";
    		
    	}   
    
    	$pageURL .= "://";
    
    	if (($_SERVER["SERVER_PORT"] != "80") and ($_SERVER["SERVER_PORT"] != "443")){
    	    
    		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    
    	} else {
    	    
    		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    
        }
    
    	return $pageURL;
    	
    }



    static function current_user_can_view() {
    
        if (is_user_logged_in()){
            
            return true;
            
        } else {
            
            return false;

        }
    
    }

    public function register_status() {
        
        $posttypes = get_post_types( array('public'   => true ), 'names' );
        $public = self::current_user_can_view();
        
        register_post_status( self::return_new_status_name(),
            array(
    		    'label'                     => _x( 'Logged In', 'post status label', self::return_plugin_namespace() ),
    		    'public'                    => $public,
    		    'label_count'               => _n_noop( 'Logged In <span class="count">(%s)</span>', 'Logged In <span class="count">(%s)</span>', self::return_plugin_namespace()  ),
    		    'post_type'                 => $posttypes, // Define one or more post types the status can be applied to.
    		    'show_in_admin_all_list'    => true,
    		    'show_in_admin_status_list' => true,
    		    'show_in_metabox_dropdown'  => true,
    		    'show_in_inline_dropdown'   => true,
    		    'dashicon'                  => 'dashicons-no',
    	    )
        );

    }

    public function display_post_state( $states, $post ) {
    
         $arg = get_query_var( 'post_status' );
         
         if($arg != self::return_new_status_name()){
             
              if(!empty($post->post_status) && ($post->post_status == self::return_new_status_name())){
                  
                   return array(ucwords(self::return_new_status_label()));
                   
              }
         }
         
        return $states;
        
    }


    public function pre_get_posts( &$query ) {
    
        if ( !is_admin()) {
        
            if (self::current_user_can_view()){
        
                if ( !empty( $query->query_vars['post_status'] ) ) {
        
                    if (is_array($query->query_vars['post_status'])){
        
                        $statuses = $query->query_vars['post_status'];     
        
                    } else {
        
                        $statuses = array($query->query_vars['post_status']);    
        
                    }
    
    
                    $statuses[] = self::return_new_status_name();
                    $query->set( 'post_status', $statuses );
    
                }
    
            }
            
        }
    
    }


    public function lh_private_content_login_status_filter($statuses){
    
        if (!in_array(self::return_new_status_name(), $statuses)){
        
            $statuses[] = self::return_new_status_name();
    
        }
    
        return $statuses;
    
    }






    public function template_redirect() {
        
        global $wp_query, $wpdb;
        
        if (is_404() && !is_user_logged_in()) {
          
            $row = $wpdb->get_row($wp_query->request);
    
            if (isset($row) and isset($row->post_status) and ($row->post_status == self::return_new_status_name() )) {
        
                $location = add_query_arg( self::return_plugin_namespace().'-login_required', 'true', wp_login_url(self::curpageurl()));
        
                wp_redirect($location, 302, self::plugin_name()); exit;
        
            }
    
        }
      
      
    }

    public function display_login_message($message){
        
        if (!empty($_GET[self::return_plugin_namespace().'-login_required']) && ($_GET[self::return_plugin_namespace().'-login_required'] == 'true')){
          
            $message = 'In order to access this content you need to be logged in';
          
        }
        
        return $message;   
        
    }

    public function plugin_init(){
        
        //load the translations, both plugin specific and the wp-statuses library
        load_plugin_textdomain( self::return_plugin_namespace(), false, basename( dirname( __FILE__ ) ) . '/languages' );
        load_plugin_textdomain( 'wp-statuses', false, basename( dirname( __FILE__ ) ) . '/includes/wp-statuses/languages' );
    
        //display a label on the listing screen
        add_filter( 'display_post_states', array($this,'display_post_state'),10,2);
        
        //put the logged in status in the loop if the user can view
        add_action( 'pre_get_posts', array($this,'pre_get_posts'),10000,1);
        
        //redirect to no access page if vistor is not logged in
        add_action('template_redirect', array($this,'template_redirect'), 11);
        
        //filter to add the role to the LH Private Content Login statuses
        add_filter('lh_private_content_login_status_filter', array($this,'lh_private_content_login_status_filter'), 10, 1);
        
       //display a message explaining the need to login
        add_filter( 'login_message', array($this,'display_login_message'),10,1);
    
    }

	 /**
     * Gets an instance of our plugin.
     *
     * using the singleton pattern
     */
    public static function get_instance(){
        
        if (null === self::$instance) {
            
            self::$instance = new self();
            
        }
 
        return self::$instance;
        
    }
    



    public function __construct() {

        //run whatever on plugins loaded (currently just translations)
        add_action( 'plugins_loaded', array($this,'plugin_init'), 10);
        
        //register the post status
        add_action( 'init', array($this,'register_status'), 1000 );
        
    }

}


$lh_logged_in_post_status_instance = LH_logged_in_post_status_plugin::get_instance();


}



?>