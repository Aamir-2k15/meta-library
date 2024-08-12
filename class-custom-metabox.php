<?php

class Custom_Metabox {
    private $fields = [];
    private $options;

    public function __construct($id, $title, $post_type, $fields = array(), $options = array()) {
        $this->id = $id;
        $this->title = $title;
        $this->post_type = $post_type;
        $this->fields = $fields;
        $this->name = isset($name)?$name:$id;

        add_action('add_meta_boxes', array($this, 'register_metabox'));
        add_action('save_post', array($this, 'save_metabox_data'));
        add_action('admin_head', array($this, 'enqueue_metabox_css'));
    }

    public function register_metabox() {
        add_meta_box(
            $this->id,
            $this->title,
            array($this, 'metabox_callback'),
            $this->post_type,
            'normal',
            'high'
        );
    }

    private function get_dynamic_options($data_type) {
        $options = array();

        switch ($data_type) {
            case 'all_users':
                $users = get_users();
                foreach ($users as $user) {
                    $options[$user->ID] = $user->display_name;
                }
                break;

            case 'all_custom_post_types':
                $post_types = get_post_types(array('_builtin' => false), 'objects');
                foreach ($post_types as $post_type) {
                    $options[$post_type->name] = $post_type->label;
                }
                break;

            case 'all_post_types':
                $post_types = get_post_types(array('public' => true), 'objects');
                foreach ($post_types as $post_type) {
                    $options[$post_type->name] = $post_type->label;
                }
                break;

            case 'all_taxonomies':
                $taxonomies = get_taxonomies(array('public' => true), 'objects');
                foreach ($taxonomies as $taxonomy) {
                    $options[$taxonomy->name] = $taxonomy->label;
                }
                break;

            case 'all_posts':
                $posts = get_posts(array('numberposts' => -1));
                foreach ($posts as $post) {
                    $options[$post->ID] = $post->post_title;
                }
                break;

            case 'all_terms':
                $terms = get_terms(array('taxonomy' => get_taxonomies(), 'hide_empty' => false));
                foreach ($terms as $term) {
                    $options[$term->term_id] = $term->name;
                }
                break;

            case 'all_pages':
                $pages = get_pages();
                foreach ($pages as $page) {
                    $options[$page->ID] = $page->post_title;
                }
                break;

            case 'all_post_type_titles':
                $post_types = get_post_types(array('public' => true), 'objects');
                foreach ($post_types as $post_type) {
                    $posts = get_posts(array('post_type' => $post_type->name, 'numberposts' => -1));
                    foreach ($posts as $post) {
                        $options[$post->ID] = $post->post_title;
                    }
                }
                break;

            default:
                break;
        }

        return $options;
    }

    public function field($args) {
    $defaults = array(
        'type'        => 'text',
        'label'       => '',
        'name'        => '',
        'id'          => '',
        'class'       => '',
        'description' => '',
        'value'       => '',
        'options'     => array()
    );

    $args = wp_parse_args($args, $defaults);

    // Handle dynamic_select and dynamic_multiselect options
    if (($args['type'] === 'dynamic_select' || $args['type'] === 'dynamic_multiselect') && is_string($args['options'])) {
        $args['options'] = $this->get_dynamic_options($args['options']);
    }

    $this->fields[] = $args;
}

    
    

    public function metabox_callback($post) {
        wp_nonce_field($this->id . '_nonce', $this->id . '_nonce_field');
    
        echo '<table class="form-table">';
        foreach ($this->fields as $field) {
            $field['value'] = get_post_meta($post->ID, $field['id'], true) ?? $field['value'];
            echo '<tr>';
            echo '<th scope="row"><label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . '</label></th>';
            echo '<td>';
            switch ($field['type']) {
                case 'text':
                case 'email':
                case 'number':
                    echo '<input class="wp-form-builder-field field-' . $field['type'] . '" type="' . esc_attr($field['type']) . '" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field['value']) . '">';
                    break;
                case 'textarea':
                    echo '<textarea class="wp-form-builder-field field-' . $field['type'] . '" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '">' . esc_textarea($field['value']) . '</textarea>';
                    break;
                case 'wysiwyg':
                    wp_editor($field['value'], $field['id'], ['textarea_name' => $field['name']]);
                    break;
                case 'radio':
                case 'checkbox':
                    foreach ($field['options'] as $label => $value) {
                        echo '<label><input type="' . esc_attr($field['type']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '"' . checked($field['value'], $value, false) . '> ' . esc_html($label) . '</label><br>';
                    }
                    break;
                case 'checkbox_multiselect':
                    foreach ($field['options'] as $label => $value) {
                        echo '<label>
                        <input type="checkbox" name="' . esc_attr($field['name']) . '[]" value="' . esc_attr($value) . '"' . (in_array($value, (array)$field['value']) ? 'checked' : '') . '> '
                            . esc_html($label) . 
                        '</label>
                        <br>';
                    }
                    break;
                    case 'dynamic_multiselect':
                        if (is_array($field['options'])) {
                            foreach ($field['options'] as $label => $value) {
                                echo '<label>
                                <input type="checkbox" name="' . esc_attr($field['name']) . '[]" value="' . esc_attr($value) . '"' . (in_array($value, (array)$field['value']) ? 'checked' : '') . '> '
                                    . ucwords($label) . 
                                '</label>
                                <br>';
                            }
                        } else {
                            echo '<p>' . esc_html__('No options available.', 'text-domain') . '</p>';
                        }
                        break;
                    
                    case 'dynamic_select':
                        echo '<select class="wp-form-builder-field field-' . $field['type'] . '" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '">';
                        echo '<option value="">-- Select --</option>';
                        if (is_array($field['options'])) {
                            foreach ($field['options'] as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '"' . selected($field['value'], $value, false) . '>' . ucwords($label) . '</option>';
                            }
                        } else {
                            echo '<option value="">' . esc_html__('No options available.', 'text-domain') . '</option>';
                        }
                        echo '</select>';
                        break;
                    
                case 'colorpicker':
                    echo '<input type="text" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="color-picker" value="' . esc_attr($field['value']) . '">';
                    break;
                case 'upload':
                    $value = $field['value'];
                    echo '<input type="text" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" />';
                    echo '<input type="button" id="' . esc_attr($field['id']) . '_button" value="Upload" class="button button-primary wp-upload-button" />';
                    echo '<div id="' . esc_attr($field['id']) . '_preview">';
                    if ($value) {
                        foreach (explode(',', $value) as $file) {
                            echo '<div class="uploaded-file"><img src="' . esc_url($file) . '" style="max-width:100px;" /><span style="cursor:pointer" class="remove-file" data-file="' . esc_attr($file) . '">Remove</span></div>';
                        }
                    }
                    echo '</div>';
                    break;
                case 'date':
                    echo '<input class="wp-form-builder-field field-' . $field['type'] . '" type="date" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field['value']) . '">';
                    break;
            }
            echo '</td><td>' . (isset($field['description']) && !empty($field['description']) ? '<span class="description">' . $field['description'] . '</span>' : '') . '</td></tr>';
        }
        echo '</table>';
    }
    


    

    public function save_metabox_data($post_id) {
        if (!isset($_POST[$this->id . '_nonce_field']) || !wp_verify_nonce($_POST[$this->id . '_nonce_field'], $this->id . '_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        foreach ($this->fields as $field) {
            $value = isset($_POST[$field['id']]) ? $_POST[$field['id']] : '';
            update_post_meta($post_id, $field['id'], $value);
        }
    }

    public function enqueue_metabox_css() {
        ?>
        <style>
            .metabox-table,
            table {
                width: 100%;
            }

            .metabox-table td {
                padding: 5px;
            }

            .metabox-table td label {
                font-weight: 500;
            }

            .metabox-table input[type=text],
            .metabox-table textarea,
            .metabox-table select,
            .metabox-table .wysiwyg,
            .metabox-table .upload {
                width: 100%;
                margin: 10px 0;
                padding: 4px 6px;
            }

            .remove-file {
                cursor: pointer;
            }
        </style>
        <?php
    }
}
?>
