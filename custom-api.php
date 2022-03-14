<?php
 /**
    * @wordpress-plugin
    * Plugin Name: Custom API response
    * Description: All functions which is used in mobile app.
    * Version: 1.0
    * Author: Cms Developer
    */
    
 function custom_error(){
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// require_once('stripe-php/init.php');
 
add_action( 'rest_api_init', function() {
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
    add_filter( 'rest_pre_serve_request', function( $value ) {
        header( 'Access-Control-Allow-Origin: *' );
        header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
        header( 'Access-Control-Allow-Credentials: true' );
        header('Content-Type: multipart/form-data' );
        return $value;
    });
},15 );

add_action('rest_api_init', function () {
    register_rest_route('mobileapi/v1', '/searchMedia', array(
        'methods'   => 'POST',
        'callback'  => 'searchMedia',
    ));
});
   /*---------------Search musics by title and categories--------------------*/
      
 function searchMedia($request){
    
    global $wpdb;
    
    $data  = array(
        "status" => "ok",
       // "errormsg" => "",
        'error_code' => ""
    );
   $param = $request->get_params();
    
    $search = $param['search'];
    $taxonomy = $param['tags'];
    $meta_query_arr =  array();
    $search_query_arr =  array();
    
    $user_data = get_userdata($user_id);
    $user_email =  $user_data->user_email;
    $user_roles = $user_data->roles;
    
       if($taxonomy == 'title'  ) { 
         $args = array(
                     'search_tax_query' => true,
                     'posts_per_page' => 6,
                     'post_type'   => 'music',
                     'post_status'   => 'publish',
                     'orderby' => 'publish_date',
                     'order' => 'DESC',
                     's' => $search,
                     'meta_key' => 'file_type',
                     'meta_value' => 'Audio',
                   /*'tax_query' => array(
                        'relation' => 'OR',
                        array(
                            'taxonomy' => 'albums',
                            'field' => 'name',
                            'terms' => $search
                        ),
                         array(
                            'taxonomy' => 'geners',
                            'field' => 'name',
                            'terms' => $search
                        )
                    )*/
                  );
       } else {
           
            if($taxonomy == 'artists'  ) { 
             $artist_id =  get_user_by('login', $search);
              $args = array(
                     'search_tax_query' => true,
                     'posts_per_page' => 6,
                     'post_type'   => 'music',
                     'post_status'   => 'publish',
                     'orderby' => 'publish_date',
                     'order' => 'DESC',
                     'meta_query' => array(
                         'relation' => 'AND',
                		array(
                			'key' => 'file_type',
                			'value' => 'Audio',
                			'compare' => '=='
                		),
                		array(
                			'key' => 'purchasable',
                			'value' => '1',
                			'compare' => '=='
                		
                		),
                		array(
                			'key' => 'artists',
                			'value' => $artist_id->id,
                			'compare' => 'LIKE'
                		
                		)
                	) ,
                  );  
            } else {
         $args = array(
                     'search_tax_query' => true,
                     'posts_per_page' => 6,
                     'post_type'   => 'music',
                     'post_status'   => 'publish',
                     'orderby' => 'publish_date',
                     'order' => 'DESC',
                     //'s' => $search,
                     'meta_key' => 'file_type',
                     'meta_value' => 'Audio',
                   'tax_query' => array(
                        array(
                            'taxonomy' => $taxonomy,
                            'field' => 'name',
                            'terms' => $search
                        )
                    )
                  );   
       }
       }
 if(!empty($search)) {
        $myposts = $wpdb->get_results( $wpdb->prepare("SELECT * FROM wp_posts
LEFT JOIN wp_postmeta ON (wp_posts.ID = wp_postmeta.post_id)
LEFT JOIN wp_term_relationships ON(wp_posts.ID = wp_term_relationships.object_id)
LEFT JOIN wp_term_taxonomy as term_taxonomy1 ON(wp_term_relationships.term_taxonomy_id = term_taxonomy1.term_taxonomy_id)
LEFT JOIN wp_term_taxonomy as term_taxonomy2 ON(wp_term_relationships.term_taxonomy_id = term_taxonomy2.term_taxonomy_id)
LEFT JOIN wp_terms as tax1_term1 ON(term_taxonomy1.term_id = tax1_term1.term_id)
LEFT JOIN wp_terms as tax2_term1 ON(term_taxonomy2.term_id = tax2_term1.term_id)
WHERE 
( wp_postmeta.meta_key = 'file_type' AND wp_postmeta.meta_value = 'Audio') 
 AND
(
    (
        (term_taxonomy1.taxonomy = 'albums') 
        AND 
        (tax1_term1.name LIKE '%$search%' )
    )
    OR
    (
        (term_taxonomy2.taxonomy = 'geners') 
        AND 
        (tax2_term1.name LIKE '%$search%' )
    )
)
OR
(
   wp_posts.post_title LIKE '%$search%'
)
AND
(
   wp_posts.post_type = 'music'
)
OR
(
   wp_posts.post_date LIKE '%$search%'
)

GROUP By wp_posts.ID") );
        
       
        $data['mypost'] = $artist_id->id;
        $data['musicslist'] = array();
        $query = new WP_Query( $args );
        //$musicslist = $myposts;
        $musicslist = $query->posts;
        if(count($musicslist) != 0){
        
        foreach($musicslist as $tempjobkey => $tempjob){
            
              $track_id = $tempjob->ID; 
            $auther_id = $tempjob->post_author; 
            
            $albums = get_the_terms( $track_id, 'albums');
           $artists = get_the_terms( $track_id, 'artist');
           $artists_data = get_post_meta( $track_id, 'artists', true);
           $artist_info = implode("",$artists_data);
             $artistName = get_userdata($artist_info);
            $file_type = get_post_meta($track_id,'file_type',true);
        /*   $data['musicslist'][$tempjobkey]->video_url = get_post_meta($track_id,'music',true);
           $data['musicslist'][$tempjobkey]->video_embed = wp_oembed_get( get_post_meta($track_id,'video_url',true) );
          */
        
            $data['musicslist'][$tempjobkey]->id = $track_id;
              $data['musicslist'][$tempjobkey]->title = $tempjob->post_title; 
            //  $data['list'][$tempjobkey]->content = $tempjob->post_content;
              $data['musicslist'][$tempjobkey]->author = $tempjob->post_author;
            $image = wp_get_attachment_url( get_post_thumbnail_id($track_id) );
            
            if($image !=''){
              
               $data['musicslist'][$tempjobkey]->artwork = $image;
            }else{
               $data['musicslist'][$tempjobkey]->artwork = site_url().'/wp-content/uploads/2020/05/iTunes-Logo-Header-1280x720-1.jpg';
            }
            
          $data['musicslist'][$tempjobkey]->file_type = $file_type;
            $file_id = get_post_meta($track_id,'music',true);
            $filefromurl = get_post_meta($track_id,'file_url',true);
            if($file_id !=''){
                
               $data['musicslist'][$tempjobkey]->url = wp_get_attachment_url($file_id); 
              
            }else{
                if($filefromurl !=''){
                  $data['musicslist'][$tempjobkey]->url = $filefromurl;
                } else {
               $data['musicslist'][$tempjobkey]->url = site_url().'/wp-content/uploads/2020/05/iTunes-Logo-Header-1280x720-1.jpg';
                }
            }
            
            $data['musicslist'][$tempjobkey]->albums = $albums;
          if($artistName) {
         $data['musicslist'][$tempjobkey]->artist = $artistName->display_name;
          } else {
              
             $data['musicslist'][$tempjobkey]->artist = "";
          }
              
            $purchasable = get_post_meta($track_id,'purchasable',true);
           if($purchasable) {
                $data['musicslist'][$tempjobkey]->isPurchasable = "true";
           } else {
                $data['musicslist'][$tempjobkey]->isPurchasable = "false";
           } 
           
            $data['musicslist'][$tempjobkey]->duration = "15";
        }
       
        return new WP_REST_Response($data, 200);
        
       
        }else{
            
             $data  = array(
                            "status" => "error",
                            "errormsg" => "No Musics Found ",
                            "msg" => "No Musics Found",
                            'error_code' => "no_music"
                        );
        return new WP_REST_Response($data, 403);
            
        }
    } else {
             $data  = array(
                            "status" => "error",
                            "errormsg" => "Search Keyword not found",
                            "msg" => "Search Keyword not found",
                            'error_code' => "no_music"
                        );
        return new WP_REST_Response($data, 403);
        }

   } 
   