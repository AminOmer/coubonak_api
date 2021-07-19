<?php
add_action('init', function(){
    if(!isset($_GET['meta']))return;
    print_r(get_post_meta($_GET['meta']));
    exit;
});

add_action('init', function(){
    if(!isset($_GET['review_post']))return;
    $dealstore_list = wp_get_post_terms( $_GET['review_post'], 'dealstore', array( 'fields' => 'all' ) );
    print_r($dealstore_list);
    exit;
}, 999);
register_activation_hook( __FILE__, 'rooh_activate' );

function rooh_activate(){
    rooh::create_table();
}
if(isset($_GET['activate'])){
    rooh::create_table();
    echo 'done';
    exit;
}

add_action( 'save_post', 'rooh_save_post', 10, 3 );
function rooh_save_post( $post_ID, $post, $update ) {
    if ( get_post_status ( $post_ID ) != 'publish' ) return;
    $args = array(
        'type'      => $post->post_type,
        'type_id'   => $post_ID,
        'action'    => 'updated',
    );
    rooh::set_update($args);
}

add_action('before_delete_post','rooh_delete_post');
function rooh_delete_post( $post_ID ){
    $args = array(
        'type'      => get_post_type($post_ID),
        'type_id'   => $post_ID,
        'action'    => 'removed',
    );
    rooh::set_update($args);
}

add_action( 'wp_trash_post', 'rooh_wp_trash_post' );
function rooh_wp_trash_post( $post_ID ){
    $args = array(
        'type'      => get_post_type($post_ID),
        'type_id'   => $post_ID,
        'action'    => 'trashed',
    );
    rooh::set_update($args);
}

add_action( 'untrash_post', 'rooh_wp_untrash_post' );
function rooh_wp_untrash_post( $post_ID ){
    $args = array(
        'type'      => get_post_type($post_ID),
        'type_id'   => $post_ID,
        'action'    => 'untrashed',
    );
    rooh::set_update($args);
}


add_action('create_category', 'rooh_wp_create_category');
function rooh_wp_create_category($term_id, $taxonomy_term_id){
    $args = array(
        'type'      => 'category',
        'type_id'   => $term_id,
        'action'    => 'updated',
    );
    rooh::set_update($args);
}

add_action ( 'edited_category', 'rooh_wp_edited_category');
function rooh_wp_edited_category( $category_id ){
    $args = array(
        'type'      => 'category',
        'type_id'   => $category_id,
        'action'    => 'updated',
    );
    rooh::set_update($args);
}