<?php

function create_cpt($args = array()) {
    // Ensure required arguments are provided
    if (!isset($args['post_type']) || !isset($args['post_type_name']) || !isset($args['icon'])) {
        return new WP_Error('missing_required_args', 'Required arguments: post_type, post_type_name, and icon');
    }

    // Extract required arguments
    $post_type = $args['post_type'];
    $post_type_name = $args['post_type_name'];
    $icon = $args['icon'];
    $menu_position = $args['menu_position'];
    // $parent_menu = isset($args['parent_menu']) ? $args['parent_menu'] : null;

    // Default labels
    $labels = array(
        'name' => __( ucfirst($post_type_name) . 's' ),
        'singular_name' => __( $post_type_name ),
        'menu_name' => __( ucfirst($post_type_name) . 's' ),
        'name_admin_bar' => __( $post_type_name ),
        'add_new' => __( 'Add New' ),
        'add_new_item' => __( 'Add New ' . $post_type_name ),
        'new_item' => __( 'New ' . $post_type_name ),
        'edit_item' => __( 'Edit ' . $post_type_name ),
        'view_item' => __( 'View ' . $post_type_name ),
        'all_items' => __( ucfirst($post_type_name) . 's' ),
        'search_items' => __( 'Search ' . ucfirst($post_type_name) . 's' ),
        'parent_item_colon' => __( 'Parent ' . ucfirst($post_type_name) . 's:' ),
        'not_found' => __( 'No ' . strtolower($post_type_name) . 's found.' ),
        'not_found_in_trash' => __( 'No ' . strtolower($post_type_name) . 's found in Trash.' ),
    );

    // Default arguments
    $default_args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        // 'show_in_menu' => $parent_menu ? $parent_menu : true,
        'query_var' => true,
        'rewrite' => array( 'slug' => $post_type ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => $menu_position ?? null,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'show_in_rest' => true, // Enable Gutenberg support
        'menu_icon' => 'dashicons-'.$icon, // Custom menu icon
    );

    // Merge with user-defined args
    $args = wp_parse_args($args, $default_args);

    // Register the custom post type
    register_post_type($post_type, $args);
}

// usage example
/* 
create_cpt(array(
    'post_type' => 'book',
    'post_type_name' => 'Book',
    'icon' => 'book',
    'menu_position' => 20,
    // 'parent_menu' => 'edit.php?post_type=library'
));
*/