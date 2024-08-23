<?php

class Custom_Metabox {
    private $fields = [];
    private $page_slugs = [];
    private $page_templates = [];

    public function __construct($args) {
        // Initialize properties based on the provided arguments
        $this->id = $args['id'];
        $this->title = $args['title'];
        $this->post_type = isset($args['post_type']) ? (array)$args['post_type'] : [];
        $this->fields = $args['fields'];
        $this->name = isset($args['name']) ? $args['name'] : $args['id'];
        $this->page_slugs = isset($args['page_slugs']) ? (array)$args['page_slugs'] : [];
        $this->page_templates = isset($args['page_templates']) ? (array)$args['page_templates'] : [];
        // $this->field_width = isset($args['field_width']) ? $args['field_width'] : 'full-width';
        // Register hooks
        add_action('add_meta_boxes', array($this, 'register_metabox'));
        add_action('save_post', array($this, 'save_metabox_data'));
        add_action('admin_head', array($this, 'enqueue_metabox_css'));
        add_action('admin_footer', array($this, 'enqueue_metabox_scripts'));
    }

    public function register_metabox() {
        global $post;

        // Get the current page template
        $current_template = get_page_template_slug($post->ID);

        // Check if the metabox should be displayed based on slug, template, or post type
        $display_metabox = false;

        if (!empty($this->post_type) && in_array($post->post_type, $this->post_type)) {
            $display_metabox = true;
        }

        if (!empty($this->page_slugs) && in_array($post->post_name, $this->page_slugs)) {
            $display_metabox = true;
        }

        if (!empty($this->page_templates) && in_array($current_template, $this->page_templates)) {
            $display_metabox = true;
        }

        if ($display_metabox) {
            add_meta_box(
                $this->id,
                $this->title,
                array($this, 'metabox_callback'),
                $this->post_type,
                'normal',
                'high'
            );
        }
    }

    public function metabox_callback($post) {
        wp_nonce_field($this->id . '_nonce', $this->id . '_nonce_field');
       // echo '<!-- Field Width: ' . $this->field_width . ' -->';
        echo '<table class="form-table ">';
        foreach ($this->fields as $field) {
            @$field['value'] = get_post_meta($post->ID, $field['id'], true) ?: $field['value'];
            
            echo '<tr>'; 
            echo '<th><label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . '</label></th>';
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
                case 'dynamic_multiselect':
                    if (is_array($field['options'])) {
                        foreach ($field['options'] as $label => $value) {
                            echo '<label>
                            <input type="checkbox" name="' . esc_attr($field['name']) . '[]" value="' . esc_attr($value) . '"' . (in_array($value, (array)$field['value']) ? 'checked' : '') . '> '
                                . ucwords($label) . 
                            '</label><br>';
                        }
                    } else {
                        echo '<p>' . esc_html__('No options available.', 'text-domain') . '</p>';
                    }
                    break;
                case 'select':
                case 'dynamic_select':
                    echo '<select class="wp-form-builder-field field-' . $field['type'] . '" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '">';
                    echo '<option value="">-- Select --</option>';
                    if ($field['type'] == 'dynamic_select') {
                        $field['options'] = $this->get_dynamic_options($field['options']);
                    }
                    if (is_array($field['options'])) {
                        foreach ($field['options'] as $value => $label) {
                            echo '<option value="' . esc_attr($value) . '"' . selected($field['value'], $value, false) . '>' . esc_html($label) . '</option>';
                        }
                    } else {
                        echo '<option value="">' . esc_html__('No options available.', 'text-domain') . '</option>';
                    }
                    echo '</select>';
                    break;
                case 'upload':
                    $value = $field['value'];
                    echo '<input type="text" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" />';
                    echo '<input type="button" id="' . esc_attr($field['id']) . '_button" value="Upload" class="button button-primary wp-upload-button" />';
                    echo '<div id="' . esc_attr($field['id']) . '_preview">';
                    if ($value) {
                        foreach (explode(',', $value) as $file_id) {
                            $file = get_attached_file($file_id);
                            echo '<div class="file-preview"><a href="' . $value . '" target="_blank"><img src="'.$value.'" style="max-width: 100px;float: right;margin-top: -50px;"></a> <span class="remove-file">Remove</span></div>';
                        }
                    }
                    echo '</div>';
                    break;
                case 'date':
                    echo '<input class="wp-form-builder-field field-' . $field['type'] . '" type="date" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field['value']) . '">';
                    break;
            }
            echo '<small>' . esc_html($field['description'] ?? '') . '</small>';
            echo '</td></tr>';
        }
        echo '</table>';
    }

    public function save_metabox_data($post_id) {
        if (!isset($_POST[$this->id . '_nonce_field']) || !wp_verify_nonce($_POST[$this->id . '_nonce_field'], $this->id . '_nonce')) {
            return $post_id;
        }
        foreach ($this->fields as $field) {
            if (isset($_POST[$field['name']])) {
                update_post_meta($post_id, $field['id'], sanitize_text_field($_POST[$field['name']]));
            } else {
                delete_post_meta($post_id, $field['id']);
            }
        }
    }

    public function enqueue_metabox_css() {
        echo '<style>.wp-form-builder-field { width: 100%; }.color-picker { max-width: 100px; }.wp-upload-button { margin-top: 5px; }.file-preview { margin-top: 5px; }            .file-preview .remove-file {cursor: pointer !important;color: #F00 !important;}</style>';    
    }
    public function enqueue_metabox_scripts() {
        wp_enqueue_media(); // Enqueue WordPress media uploader
?>
<script>
jQuery(document).ready(function($) {
    console.log('WpFormBuilder upload....'); 
    $('.wp-upload-button').click(function(e) {
        console.log('WpUpload Init....');
        e.preventDefault();
        const button = $(this);
        const field = button.siblings('input[type=text]');
        const preview = button.siblings(`#${field.attr('id')}_preview`);
        const uploader = wp.media({
            title: 'Upload Image',
            button: { text: 'Use this image' },
            multiple: false,
        }).on('select', function() {
            const selection = uploader.state().get('selection');
            const attachment = selection.first().toJSON().url;
            field.val(attachment);
            preview.empty().append(`<div class="uploaded-file"><img src="${attachment}" style="max-width:100px;" /><span style="color:#F00;cursor:pointer;" class="remove-file" data-file="${attachment}">Remove</span></div>`);
        }).open();
    });

    // Remove file functionality
    $(document).on('click', '.remove-file', function() {
        const file = $(this).data('file');
        const field = $(this).closest('td').find('input[type=text]');
        field.val('');
        const existing_files = field.val().split(',');
        
        const updated_files = existing_files.filter(function(value) {
            return value !== file;
        });
        field.val(updated_files.join(','));
        $(this).parent().remove();
    });
});
</script> 
<?php
    }

    public function get_dynamic_options($type) {
        $options = [];
        switch ($type) {
            case 'all_pages':
                $pages = get_pages();
                foreach ($pages as $page) {
                    $options[$page->ID] = $page->post_title;
                }
                break;
            // Add other cases here for different dynamic options if needed
        }
        return $options;
    }
}
