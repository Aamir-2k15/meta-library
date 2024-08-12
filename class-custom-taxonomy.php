<?php
class CustomTaxonomy {
    private $taxonomy;
    private $postTypes;
    private $args;

    public function __construct($taxonomy, $postTypes = array(), $args = array()) {
        $this->taxonomy = $taxonomy;
        $this->postTypes = is_array($postTypes) ? $postTypes : array($postTypes);
        $this->args = $args;

        add_action('init', array($this, 'register_taxonomy'));
    }

    public function register_taxonomy() {
        $defaultArgs = array(
            'labels' => array(
                'name'              => _x( ucfirst($this->taxonomy), 'taxonomy general name', 'textdomain' ),
                'singular_name'     => _x( ucfirst($this->taxonomy), 'taxonomy singular name', 'textdomain' ),
                'search_items'      => __( 'Search ' . ucfirst($this->taxonomy), 'textdomain' ),
                'all_items'         => __( 'All ' . ucfirst($this->taxonomy), 'textdomain' ),
                'parent_item'       => __( 'Parent ' . ucfirst($this->taxonomy), 'textdomain' ),
                'parent_item_colon' => __( 'Parent ' . ucfirst($this->taxonomy) . ':', 'textdomain' ),
                'edit_item'         => __( 'Edit ' . ucfirst($this->taxonomy), 'textdomain' ),
                'update_item'       => __( 'Update ' . ucfirst($this->taxonomy), 'textdomain' ),
                'add_new_item'      => __( 'Add New ' . ucfirst($this->taxonomy), 'textdomain' ),
                'new_item_name'     => __( 'New ' . ucfirst($this->taxonomy) . ' Name', 'textdomain' ),
                'menu_name'         => __( ucfirst($this->taxonomy), 'textdomain' ),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => $this->taxonomy ),
        );

        $args = wp_parse_args($this->args, $defaultArgs);

        register_taxonomy($this->taxonomy, $this->postTypes, $args);
    }
}

//EXAMPLE USAGE

// $genreTaxonomy = new CustomTaxonomy('genre', array('post', 'page'));
/*
// Create a 'writer' taxonomy for 'page' post type with custom arguments
$writerTaxonomy = new CustomTaxonomy('writer', 'page', array(
    'hierarchical' => true,//cate & tag diff : true & false
    'rewrite'      => array( 'slug' => 'book-writer' ),
));
*/