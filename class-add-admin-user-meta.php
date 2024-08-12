
<?php

class UserMeta_Admin_Fields {
    private $fields = [];

    public function __construct() {
        add_action('show_user_profile', [$this, 'render_user_meta_fields']);
        add_action('edit_user_profile', [$this, 'render_user_meta_fields']);
        add_action('personal_options_update', [$this, 'save_user_meta_fields']);
        add_action('edit_user_profile_update', [$this, 'save_user_meta_fields']);
    }

    public function field($args) {
        $defaults = [
            'type'        => 'text',
            'label'       => '',
            'name'        => '',
            'id'          => '',
            'class'       => '',
            'description' => '',
            'value'       => '',
            'options'     => []
        ];

        $args = wp_parse_args($args, $defaults);

        // Handle dynamic_select options
        if ($args['type'] === 'dynamic_select' && is_string($args['options']) || $args['type'] === 'dynamic_multiselect') {
            $args['options'] = $this->get_dynamic_options($args['options']);
        }

        $this->fields[] = $args;
    }

    private function get_dynamic_options($data_type) {
        $options = [];

        switch ($data_type) {
            case 'all_users':
                $users = get_users();
                foreach ($users as $user) {
                    $options[$user->ID] = $user->display_name;
                }
                break;

            case 'all_custom_post_types':
                $post_types = get_post_types(['_builtin' => false], 'objects');
                foreach ($post_types as $post_type) {
                    $options[$post_type->name] = $post_type->label;
                }
                break;

            case 'all_post_types':
                $post_types = get_post_types(['public' => true], 'objects');
                foreach ($post_types as $post_type) {
                    $options[$post_type->name] = $post_type->label;
                }
                break;

            case 'all_taxonomies':
                $taxonomies = get_taxonomies(['public' => true], 'objects');
                foreach ($taxonomies as $taxonomy) {
                    $options[$taxonomy->name] = $taxonomy->label;
                }
                break;

            case 'all_posts':
                $posts = get_posts(['numberposts' => -1]);
                foreach ($posts as $post) {
                    $options[$post->ID] = $post->post_title;
                }
                break;

            case 'all_terms':
                $terms = get_terms(['taxonomy' => get_taxonomies(), 'hide_empty' => false]);
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
                $post_types = get_post_types(['public' => true], 'objects');
                foreach ($post_types as $post_type) {
                    $posts = get_posts(['post_type' => $post_type->name, 'numberposts' => -1]);
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

    public function render() {
        echo '<h3>Additional User Information</h3>';
        echo '<table class="form-table">';
        foreach ($this->fields as $field) {
            echo '<tr>';
            echo '<th><label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . '</label></th>';
            echo '<td>';
            switch ($field['type']) {
                case 'text':
                case 'email':
                case 'number':
                    echo '<input type="' . esc_attr($field['type']) . '" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field['value']) . '" class="regular-text" />';
                    break;
                case 'textarea':
                    echo '<textarea id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="large-text" rows="5">' . esc_textarea($field['value']) . '</textarea>';
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
                        echo '<label><input type="checkbox" name="' . esc_attr($field['name']) . '[]" value="' . esc_attr($value) . '"' . checked(in_array($value, (array) $field['value']), true, false) . '> ' . esc_html($label) . '</label><br>';
                    }
                    break;
                case 'dynamic_multiselect':
                    foreach ($field['options'] as $label => $value) {
                        echo '<label><input type="checkbox" name="' . esc_attr($field['name']) . '[]" value="' . esc_attr($value) . '"' . checked(in_array($value, (array) $field['value']), true, false) . '> ' . esc_html($label) . '</label><br>';
                    }
                    break;
                case 'select':
                case 'dynamic_select':
                    echo '<select id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '">';
                    echo '<option value="">-- Select --</option>';
                    foreach ($field['options'] as $value => $label) {
                        echo '<option value="' . esc_attr($value) . '"' . selected($field['value'], $value, false) . '>' . esc_html($label) . '</option>';
                    }
                    echo '</select>';
                    break;
                case 'colorpicker':
                    echo '<input type="text" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field['value']) . '" class="color-picker" data-default-color="' . esc_attr($field['placeholder']) . '" />';
                    break;
                case 'upload':
                    echo '<input type="text" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['id']) . '" value="' . esc_attr($field['value']) . '" />';
                    echo '<input type="button" id="' . esc_attr($field['id']) . '_button" value="Upload" class="button-primary upload_button" />';
                    echo '<div id="' . esc_attr($field['id']) . '_preview">';
                    if ($field['value']) {
                        foreach (explode(',', $field['value']) as $file) {
                            echo '<div class="uploaded-file"><img src="' . esc_url($file) . '" style="max-width:100px;" /><span style="cursor:pointer" class="remove-file" data-file="' . esc_attr($file) . '">Remove</span></div>';
                        }
                    }
                    echo '</div>';
                    break;
                case 'date':
                    echo '<input type="date" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field['value']) . '" />';
                    break;
            }
            echo '</td><td>' . esc_html($field['description']) . '</td></tr>';
        }
        echo '</table>';
    }

    public function render_user_meta_fields($user) {
        foreach ($this->fields as &$field) {
            $field['value'] = get_user_meta($user->ID, $field['id'], true);
        }
        $this->render();
    }

    public function save_user_meta_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        foreach ($this->fields as $field) {
            if (isset($_POST[$field['id']])) {
                update_user_meta($user_id, $field['id'], $_POST[$field['id']]);
            } else {
                delete_user_meta($user_id, $field['id']);
            }
        }
    }
}
