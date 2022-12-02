<?php
/**
* Plugin Name: List of Entires by Shortcode
* Plugin URI: 
* Description: This is a plugin to list the entries by using shortcode.
* Version: 1.0.0
* Requires PHP: 7.4
* Author: Jairo Poveda
* Author URI: 
**/


///////////// Ajax Rest API /////////////////

add_action( 'wp_enqueue_scripts', 'ajax_restAPI_pagination' );

function ajax_restAPI_pagination() {
    wp_enqueue_script( 'pagination-script', plugin_dir_url( __FILE__ ).'_inc/entries-list.js', array( 'jquery' ), false, true );
    wp_localize_script( 'pagination-script', 'pageObj', array(
        'restURL' => rest_url(),
        'restNonce' => wp_create_nonce( 'wp_rest' )
    ) );
}

// register custom rest endpoint
add_action('rest_api_init', function() {
    register_rest_route( 'baseUrl/v1/baseEndPoint', '/list/', array(
        'methods' => 'POST',
        'callback' => 'restAPI_pagination_callback'
    ) );
});

// callback function -- processing the ajax data
function restAPI_pagination_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix.'feedback';

    if( $_POST['btn_event'] == 'item' ){
        
        $id = $_POST['id'];
        $list_item = ( $wpdb->get_results( "SELECT * FROM ".$table_name." where id=".$id ) )[0];

        return json_encode( $list_item );

    }else{
        $pg_num = $_POST['value'];
        $lists_per_page = $_POST['lists_per_page'];                                   
    
        $pg_data= [];    
        $start_num = ( $pg_num - 1 ) * $lists_per_page + 1; 
        
        for( $i = $start_num; $i < ( $start_num + $lists_per_page ); $i++ ){
            
            if( $wpdb->get_var( "SELECT count(*) FROM ".$table_name." where id=".$i ) ){    // if list exists with ID

                $id = $wpdb->get_var( "SELECT id FROM ".$table_name." where id=".$i );
                $first_name = $wpdb->get_var( "SELECT first_name FROM ".$table_name." where id=".$i );
                $last_name = $wpdb->get_var( "SELECT last_name FROM ".$table_name." where id=".$i );
                $user_email = $wpdb->get_var( "SELECT user_email FROM ".$table_name." where id=".$i );
                $subject = $wpdb->get_var( "SELECT subject FROM ".$table_name." where id=".$i );
    
                array_push( $pg_data, ['id'=>$id, 'first_name'=>$first_name, 'last_name'=>$last_name, 'user_email'=>$user_email, 'subject'=>$subject] );
            }            
        }
        
        return json_encode( $pg_data );
    }    
}


//////////// List of Entries Shortcode ///////////////
function entries_list_shortcode() {
    // CSS
    wp_register_style( 'entries-list-css', plugin_dir_url( __FILE__ ).'_inc/entries-list.css' );    
    wp_enqueue_style( 'entries-list-css' );

    // check user - admin
    if(current_user_can( 'administrator' )) {

        global $wpdb;
        $table_name = $wpdb->prefix.'feedback';
        $count_query = "SELECT count( * ) FROM $table_name";
        
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name || $wpdb->get_var( $count_query ) < 1 ) {      // if there is not existed the dababase of entry       
            ?>
                <div>There is no data of list of entries.</div>
            <?php
        } else {
            
            $counts = $wpdb->get_var( $count_query ); 

            // Can test for lists & Pagination with change of two values in the below.
            // One is count of lists in a page, another is count of buttons in pagination.
            $lists_per_page = 10;                                 //  count of lists in a page
            $pageBtns_counts = 4;                                 //  count of buttons in pagination

            ///////////////

            $pages = intdiv( $counts, $lists_per_page );
            if( ( $counts % $lists_per_page ) != 0 ) {              
                $pages += 1;                                      //  number of total pages
            }
        ?>
            <div id="entries-list">            
                <div class="list-categories">
                    <div class="list-cat">First Name</div>
                    <div class="list-cat">last Name</div>
                    <div class="list-cat">Email</div>
                    <div class="list-cat">Subject</div>
                </div>
                <div class="list-block">
                <?php
                    if( $lists_per_page <= $counts ) { 
                        $bound = $lists_per_page;
                    } else {
                        $bound = $counts;
                    }
                    for( $l = 0; $l < $bound; $l++ ) {
                        $buf = $wpdb->get_results( 'SELECT * FROM '.$table_name.' where id='.( $l + 1 ) );
                        ?>
                        <div class="item-<?php echo $buf[0]->id;?> list-values">
                            <div class="list-val"><?php echo $buf[0]->first_name; ?></div>
                            <div class="list-val"><?php echo $buf[0]->last_name; ?></div>
                            <div class="list-val"><?php echo $buf[0]->user_email; ?></div>
                            <div class="list-val"><?php echo $buf[0]->subject; ?></div>
                        </div>
                        <?php
                    }
                ?>            
                </div>
                <div class="pagination-btns">                
                    <a href="javascript:void(0)" class="go-firstpage"> << </a>
                    <a href="javascript:void(0)" class="prev-page"> < </a>
                    <div class="page-nums">                    
                        <?php                        
                            if( $pages > $pageBtns_counts ) {
                                $bound = $pageBtns_counts;
                            } else {
                                $bound = $pages;
                            }
                            for( $p = 0; $p < $bound; $p++ ) { 
                                ?>
                                <a href="javascript:void(0)" class="<?php echo $p + 1; ?> <?php if( $p==0 ){ ?>active<?php } ?>"><?php echo $p + 1; ?></a>
                                <?php
                            }
                        ?>                                  
                    </div>
                    <a href="javascript:void(0)" class="next-page"> > </a>
                    <a href="javascript:void(0)" class="go-lastpage"> >> </a>
                </div>
                <input type="text" class="page-amount" value="<?php echo $pages; ?>" hidden>
                <input type="text" class="pageBtns-counts" value="<?php echo $pageBtns_counts; ?>" hidden>
                <input type="text" class="lists-per-page" value="<?php echo $lists_per_page; ?>" hidden>
            </div>
        <?php 
        }
    } else { 
    ?>
        <div>You are not authorized to view the content of this page.</div>
    <?php
    }
}

add_shortcode( 'entries-list', 'entries_list_shortcode' );