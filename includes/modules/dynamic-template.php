<?php

if(!defined('ABSPATH'))
    exit;

// Check setting
if(!acf_get_setting('acfe/modules/dynamic_templates'))
    return;

if(!class_exists('acfe_template')):

class acfe_template{
    
    public $post_type = '';
    
    function __construct(){
        
        // Post Type
        $this->post_type = 'acfe-template';
        
        // Admin
        add_action('init',                                                          array($this, 'init'));
        add_action('admin_menu',                                                    array($this, 'admin_menu'));
        add_action('current_screen',                                                array($this, 'current_screen'));
        
        // ACF
        add_filter('acf/get_post_types',                                            array($this, 'filter_post_type'), 10, 2);
        
        // ACF Locations
        add_filter('acf/location/rule_types',                                       array($this, 'location_types'));
        add_filter('acf/location/rule_operators/acfe_template',                     array($this, 'location_operators'), 10, 2);
        add_filter('acf/location/rule_values/acfe_template',                        array($this, 'location_values'));
        add_filter('acf/location/rule_match/acfe_template',                         array($this, 'location_match_target'), 10, 4);
        add_filter('acf/location/rule_match',                                       array($this, 'location_match_template'), 99, 4);
        
        add_filter('acf/validate_field_group',                                      array($this, 'validate_field_group'), 20, 1);
        
    }
    
    function init(){
        
        // Post Type
        register_post_type($this->post_type, array(
            'label'                 => __('Templates', 'acfe'),
            'description'           => __('Templates', 'acfe'),
            'labels'                => array(
                'name'          => __('Templates', 'acfe'),
                'singular_name' => __('Template', 'acfe'),
                'menu_name'     => __('Templates', 'acfe'),
                'edit_item'     => __('Edit Template', 'acfe'),
                'add_new_item'  => __('New Template', 'acfe'),
            ),
            'supports'              => array('title'),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'menu_icon'             => 'dashicons-feedback',
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => false,
            'has_archive'           => false,
            'rewrite'               => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capabilities'          => array(
                'publish_posts'         => acf_get_setting('capability'),
                'edit_posts'            => acf_get_setting('capability'),
                'edit_others_posts'     => acf_get_setting('capability'),
                'delete_posts'          => acf_get_setting('capability'),
                'delete_others_posts'   => acf_get_setting('capability'),
                'read_private_posts'    => acf_get_setting('capability'),
                'edit_post'             => acf_get_setting('capability'),
                'delete_post'           => acf_get_setting('capability'),
                'read_post'             => acf_get_setting('capability'),
            )
        ));
        
    }
    
    function admin_menu(){
        
        if(!acf_get_setting('show_admin'))
            return;
        
        add_submenu_page('edit.php?post_type=acf-field-group', __('Templates', 'acfe'), __('Templates', 'acfe'), acf_get_setting('capability'), 'edit.php?post_type=' . $this->post_type);
        
    }
    
    function current_screen(){
        
        global $typenow;
        
        if($typenow !== $this->post_type)
            return;
        
        // customize post_status
		global $wp_post_statuses;
		
		// modify publish post status
		$wp_post_statuses['publish']->label_count = _n_noop('Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'acf');
        
        add_action('load-edit.php',     array($this, 'load_list'));
        add_action('load-post.php',     array($this, 'load_post'));
        add_action('load-post-new.php', array($this, 'load_post_new'));
        
        add_action('load-post.php',     array($this, 'load'));
        add_action('load-post-new.php', array($this, 'load'));
        
    }
    
    function load_list(){
        
        // Posts per page
        add_filter('edit_posts_per_page', function(){
            return 999;
        });
        
        // Order
        add_action('pre_get_posts', function($query){
            
            if(!$query->is_main_query())
                return;
            
            if(!acf_maybe_get($_REQUEST,'orderby'))
                $query->set('orderby', 'name');
            
            if(!acf_maybe_get($_REQUEST,'order'))
                $query->set('order', 'ASC');
            
        });
        
        // Columns
        add_filter('manage_edit-' . $this->post_type . '_columns',         array($this, 'admin_columns'));
        add_action('manage_' . $this->post_type . '_posts_custom_column',  array($this, 'admin_columns_html'), 10, 2);
        
    }
    
    function load_post(){
        
        // After Title
        add_action('edit_form_after_title', array($this, 'edit_form_after_title'));
        
    }
    
    function load_post_new(){
        
    }
    
    function load(){
        
        // Remove Slug Metabox
        remove_meta_box('slugdiv', $this->post_type, 'normal');
        
        // Menu
        add_filter('parent_file', function(){
            return 'edit.php?post_type=acf-field-group';
        });
        
        // Submenu
        add_filter('submenu_file', function(){
            return 'edit.php?post_type=' . $this->post_type;
        });
        
        // Metaboxes
        add_action('acf/add_meta_boxes', array($this, 'add_metaboxes'), 10, 3);
        
        // Footer
        add_action('admin_footer', array($this, 'load_footer'));
        
    }
    
    function edit_form_after_title(){
        
        echo '<div class="notice notice-warning inline"><p>' . __('You are currently editing a Dynamic Template.', 'acfe') . '</p></div>';
        
    }
    
    function add_metaboxes($post_type, $post, $field_groups){
        
        $postboxes = array();
        
        // No Field Groups
        if(empty($field_groups)){
            
            // vars
            $id = "acfe-template-no-field-group";
            $title = __('Instructions', 'acfe');
            $context = 'normal';
            $priority = 'default';

            // Localize data
            $postboxes[] = array(
                'id'		=> $id,
                'key'		=> '',
                'style'		=> 'default',
                'label'		=> 'left'
            );
            
            // Add Instructions
            add_meta_box($id, $title, array($this, 'render_meta_box_instructions'), $post_type, $context, $priority, array());
        
        // Field Groups
        }else{
        
            // vars
            $id = "acfe-template";
            $title = __('Template Rules', 'acfe');
            $context = 'side';
            $priority = 'core';

            // Localize data
            $postboxes[] = array(
                'id'		=> $id,
                'key'		=> '',
                'style'		=> 'default',
                'label'		=> 'top'
            );
            
            add_meta_box($id, $title, array($this, 'render_meta_box_rules'), $post_type, $context, $priority, array('field_groups' => $field_groups));
        
        }
        
        // Add Postbox Javascript
        if($postboxes){
            
            $data = acf_get_instance('ACF_Assets')->data;
            $acf_postboxes = isset($data['postboxes']) ? $data['postboxes']: array();
            $acf_postboxes = array_merge($acf_postboxes, $postboxes);
            
            // Localize postboxes.
            acf_localize_data(array(
                'postboxes'	=> $acf_postboxes
            ));
        
        }
        
    }
    
    function get_target_locations($field_groups, $post_id){
        
        $groups = array();
        
        foreach($field_groups as $field_group){
                
            if($field_group['location']){
                
                // Loop through location groups.
                foreach($field_group['location'] as $group){
                    
                    // ignore group if no rules.
                    if(empty($group))
                        continue;
                    
                    $found = false;
                    
                    foreach($group as $rule){
                        
                        if($rule['param'] === 'acfe_template' && $rule['value'] === $post_id){
                            
                            $found = true;
                            
                        }
                        
                    }
                    
                    if($found){
                        
                        $groups[] = $group;
                        
                    }
                    
                }
                
            }
        
        }
        
        $rules = array();
        
        foreach($groups as $group){
            
            foreach($group as $rule){
                
                if($rule['param'] === 'acfe_template')
                    continue;
                
                $rules[] = $rule;
                
            }
            
        }
        
        return $rules;
        
    }
    
    function render_meta_box_rules($post, $metabox){
        
        // vars
		$id = $metabox['id'];
        $field_groups = $metabox['args']['field_groups'];
        $rules = $this->get_target_locations($field_groups, $post->ID);
        
        ?>
        
        <?php foreach($rules as $rule){ ?>
            <div class="acf-field">
                <div class="acf-input">
                
                    <ul style="list-style:square inside;margin:0;">
                    
                        <?php 
                        // Location
                        $location = acf_get_location_rule($rule['param']);
                        
                        // Operator
                        $operators = acf_get_location_rule_operators($rule);
                        $operator = $operators[$rule['operator']];
                        
                        // Value
                        $values = acf_get_location_rule_values($rule);
                        $value = $values[$rule['value']];
                        ?>
                    
                        <li style="margin:0;"><strong><?php echo $location->label; ?></strong> <?php echo $operator; ?> <strong><?php echo $value; ?></strong><br /></li>
                    
                        
                    </ul>
                    
                </div>
            </div>
        <?php } ?>
        
        <?php
        
    }
    
    function render_meta_box_instructions($post, $metabox){
        
        ?>
        <div class="acf-field">
            <div class="acf-label">
                <label>How it works</label>
            </div>
            <div class="acf-input">
            
                <p style="margin-top:0;">Dynamic Templates let you manage default ACF values in an advanced way. In order to start, you need to connect a field group to this template. Head over the <a href="<?php echo admin_url('edit.php?post_type=acf-field-group'); ?>">Field Groups administration</a>, select the field group of your choice and scroll down to the location settings. To connect a field group to a template, pick a location and click on the "Add" button. Select the rule "Dynamic Template" under "Forms", then choose your template and save the field group.</p>
                
                <p>You can now fill up the template page, values will be automatically loaded for the location it is tied to if the user never saved anything. In this screenshot, there is a different template for the "Post Type: Page" & the "Post Type: Post" while using the same field group.</p>
                
                <p>The Dynamic Template design is smart enough to fulfill complex scenarios. For example, one single template can be used in conjunction with as many field group location as needed. It is also possible to add multiple field groups into a single template to keep things organized.</p>
                
                <p><u>Note:</u> Template values will be loaded when the user haven't saved any data related to the said values. Typically in a "New Post" situation. If the user save a value, even an empty one, the template won't be loaded.</p>
                
                <div style="margin-top:25px;">
                    <img src="<?php echo acfe_get_url('assets/images/dynamic-template-instructions.jpg'); ?>" style="width:100%; height:auto;" />
                </div>
            
            </div>
        </div>
        <?php
        
    }
    
    function load_footer(){
        
        ?>
        <script type="text/javascript">
        (function($){
            
            if(typeof acf === 'undefined')
                return;
            
            $('#post').submit(function(e){
                
                // vars
                var $title = $('#titlewrap #title');
                
                // empty
                if(!$title.val()){
                    
                    // prevent default
                    e.preventDefault();
                    
                    // alert
                    alert('Template title is required.');
                    
                    // focus
                    $title.focus();
                    
                }
                
            });
            
        })(jQuery);
        
        jQuery
        </script>
        <?php
    }
    
    function admin_columns($columns){
        
        if(isset($columns['date']))
            unset($columns['date']);
        
        $columns['field_groups'] = __('Field Groups', 'acfe');
        $columns['locations'] = __('Locations', 'acfe');
        $columns['fields'] = __('Fields', 'acfe');
        
        return $columns;
        
    }
    
    function admin_columns_html($column, $post_id){
        
        // Locations
        if($column === 'field_groups'){
            
            $field_groups = acf_get_field_groups(array(
                'post_id'	=> (string) $post_id, 
                'post_type'	=> $this->post_type
            ));
            
            if(!$field_groups){
                
                echo '—';
                return;
                
            }
            
            $html = array();
            
            foreach($field_groups as $field_group){
                
                $html[] = '<a href="' . admin_url('post.php?post=' . $field_group['ID'] . '&action=edit') . '">' . $field_group['title'] . '</a>';
                
            }
            
            echo implode(', ', $html);
            
        }
        
        // Locations
        if($column === 'locations'){
            
            $field_groups = acf_get_field_groups(array(
                'post_id'	=> (string) $post_id, 
                'post_type'	=> $this->post_type
            ));
            
            if(!$field_groups){
                
                echo '—';
                return;
                
            }
            
            $rules = $this->get_target_locations($field_groups, (string) $post_id);
            $rules = acfe_get_locations_array($rules);
            
            foreach($rules as $rule){
                
                echo $rule['html'];
                
            }
            
        }
        
        // Fields
        if($column === 'fields'){
            
            $field_groups = acf_get_field_groups(array(
                'post_id'	=> (string) $post_id, 
                'post_type'	=> $this->post_type
            ));
            
            if(!$field_groups){
                
                echo '—';
                return;
                
            }
            
            $count =0;
            foreach($field_groups as $field_group){
                
                $count += acf_get_field_count($field_group);
                
            }
            
            echo $count;
            
        }
        
    }
    
    function filter_post_type($post_types, $args){
        
        if(empty($post_types))
            return $post_types;
        
        foreach($post_types as $k => $post_type){
            
            if($post_type !== $this->post_type)
                continue;
            
            unset($post_types[$k]);
            
        }
        
        return $post_types;
        
    }
    
    function location_types($choices){
        
        $name = __('Forms', 'acf');
        
        $choices[$name] = acfe_array_insert_after('options_page', $choices[$name], 'acfe_template', __('Dynamic Template', 'acfe'));

        return $choices;
        
    }
    
    function location_operators($operators, $rule){
        
        $operators = array(
            '=='	=> __("is equal to",'acf'),
        );

        return $operators;
        
    }
    
    function location_values($choices){
        
        $get_posts = get_posts(array(
            'post_type'         => $this->post_type,
            'posts_per_page'    => -1,
            'fields'            => 'ids'
        ));
        
        $choices = array();
        
        if(!empty($get_posts)){
            
            foreach($get_posts as $pid){
                
                $choices[$pid] = get_the_title($pid);
                
            }
            
        }else{
            
            $choices[''] = __('No template pages found', 'acfe');
            
        }
        
        return $choices;
        
    }
    
    function validate_field_group($field_group){
        
        if($field_group['location']){
            
            // Loop through location groups.
            foreach($field_group['location'] as $k => $group){
                
                // ignore group if no rules.
                if(empty($group))
                    continue;
                
                // Do not allow Template as single location (only use in combination with another rule)
                if(count($group) === 1 && $group[0]['param'] === 'acfe_template'){
                    
                    unset($field_group['location'][$k]);
                    
                }
                
            }
            
        }
        
        return $field_group;
        
    }
    
    // Match for target
    function location_match_target($match, $rule, $screen, $field_group){
        
        $post_type = acf_maybe_get($screen, 'post_type');
        
        // Do not match template single
        if($post_type === $this->post_type)
            return $match;
        
        $post_id = $rule['value'];
        $fields = get_field_objects($post_id, false);
        
        // bail early
        if($fields){
            
            // populate
            foreach($fields as $field_name => $field){
                
                $field_key = $field['key'];
                $new_value = $field['value'];
                
                add_filter('acf/load_value/key=' . $field_key, function($value, $post_id, $field) use($new_value){
                    
                    if(empty($value) && !is_numeric($value)){
                        
                        $value = acf_get_metadata($post_id, $field['name']);
                        
                        if($value === null)
                            return $new_value;
                        
                    }
                    
                    return $value;
                    
                }, 10, 3);
            }
        
        }
        
        return true;

    }
    
    // Match for single Template
    function location_match_template($match, $rule, $screen, $field_group){
        
        $post_type = acf_maybe_get($screen, 'post_type');
        $post_id = acf_maybe_get($screen, 'post_id');
        
        if($post_type !== $this->post_type || !$post_id)
            return $match;
        
        // Check if active.
        if(!$field_group['active'])
            return false;
        
        if($field_group['location']){
            
            // Loop through location groups.
            foreach($field_group['location'] as $group){
                
                // ignore group if no rules.
                if(empty($group))
                    continue;
                
                // Loop over rules and determine if all rules match.
                $match_group = false;
                
                foreach($group as $rule){
                    
                    if($rule['param'] === 'acfe_template' && $rule['value'] === $post_id){
                        
                        $match_group = true;
                        break;
                        
                    }
                    
                }
                
                if($match_group)
                    return true;
                
            }
            
        }
        
        // Return default.
        return false;
        
    }
    
}

// initialize
acfe()->acfe_template = new acfe_template();

endif;