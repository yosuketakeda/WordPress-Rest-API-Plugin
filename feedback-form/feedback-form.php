<?php
/**
* Plugin Name: Feedback Form by Shortcode
* Plugin URI: 
* Description: This is a plugin to add the feedback form to the page by using shortcode.
* Version: 1.0.0
* Requires PHP: 7.4
* Author: Jario Poveda
* Author URI: 
**/


//////////////////  Create Table in DB when plugin is activated. ////////////////////////
// use add_option during the hook processes

function feedback_form_activate() {
    add_option( 'Activated_Plugin', 'Plugin-Slug' );  
}
register_activation_hook( __FILE__, 'feedback_form_activate' );
  
function load_plugin() {  
    if ( is_admin() && get_option( 'Activated_Plugin' ) == 'Plugin-Slug' ) {

        delete_option( 'Activated_Plugin' );
        
        global $wpdb;
        $table_name = $wpdb->prefix.'feedback';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {

            //table not in database. Create new table
            $charset_collate = $wpdb->get_charset_collate();
        
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                first_name text NOT NULL,
                last_name text NOT NULL,
                user_email text NOT NULL,
                subject text NOT NULL,
                message text NOT NULL,            
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY id (id)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            
            dbDelta( $sql );
        }
    }
}
add_action( 'admin_init', 'load_plugin' );

///////////// Ajax Rest API /////////////////

add_action( 'wp_enqueue_scripts', 'ajax_restAPI' );

function ajax_restAPI() {
    wp_enqueue_script( 'feedbackform-script', plugin_dir_url( __FILE__ ).'_inc/feedback-form.js', array( 'jquery' ),false, true );
    wp_localize_script( 'feedbackform-script', 'formObj', array(
        'restURL' => rest_url(),
        'restNonce' => wp_create_nonce( 'wp_rest' )
    ) );
}

// register custom rest endpoint
add_action( 'rest_api_init', function() {
    register_rest_route( 'baseUrl/v1/baseEndPoint', '/feedback/', array(
        'methods' => 'POST',
        'callback' => 'restAPI_endpoint_callback'
    ) );
});

// callback function -- processing the ajax data
function restAPI_endpoint_callback() {
    parse_str( $_POST['values'], $dataArray );

    global $wpdb;
    $table_name = $wpdb->prefix.'feedback';

    $result_check = $wpdb->insert( $table_name, array(
            'first_name' => $dataArray['firstname'],
            'last_name'=> $dataArray['lastname'], 
            'user_email' => $dataArray['email'], 
            'subject' => $dataArray['subject'], 
            'message' => $dataArray['message']
    ) ); 
    
    if ( $result_check ) {
        $return_val = 'successed';
    } else {
        $return_val = 'failed';
    }
    return json_encode( ['res' => $return_val] );
}

//////////// Form Shortcode ///////////////
function feedback_form_shortcode() {
    // CSS
    wp_register_style( 'feedback_form_css', plugin_dir_url( __FILE__ ).'_inc/feedback-form.css' );    
    wp_enqueue_style( 'feedback_form_css' );

    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
    }
    ?>

    <form id="feedback-form" class="feedback-form" method="POST">
        <h2>Submit your feedback</h2>
        <div class="form-group">
            <div class="firstname-div">
                <label>First Name</label><br>
                <input type="text" id="firstname" name="firstname" value="<?php if ( is_user_logged_in() ) echo get_user_meta( $current_user->id, 'first_name', true ); ?>" required>
            </div>
            <div class="lastname-div">
                <label>Last Name</label><br>
                <input type="text" id="lastname" name="lastname" value="<?php if ( is_user_logged_in() ) echo get_user_meta( $current_user->id, 'last_name', true ); ?>" required>
            </div>
        </div>
        <div class="form-group">
            <div class="sub-div">
                <label>Email</label><br>
                <input type="email" id="email" name="email" value="<?php if ( is_user_logged_in() ) echo $current_user->user_email; ?>" required>
            </div>
        </div>
        <div class="form-group">
            <div class="sub-div">
                <label>Subject</label><br>
                <input type="text" id="subject" name="subject" required>
            </div>
        </div>
        <div class="form-group">
            <div class="sub-div">
                <label>Message</label><br>
                <textarea type="text" id="message" name="message" rows="7" required></textarea>
            </div>
        </div>
        <div class="form-group">
            <button type="submit" class="submit-btn">Submit</button>
        </div>
    </form>

    <?php
}

add_shortcode( 'feedback-form', 'feedback_form_shortcode' );
