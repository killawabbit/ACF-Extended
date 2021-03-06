<?php

if(!defined('ABSPATH'))
    exit;

add_action('acf/render_field_settings/type=group', 'acfe_field_group_settings');
function acfe_field_group_settings($field){
    
    acf_render_field_setting($field, array(
        'label'         => __('Seemless Style', 'acfe'),
        'name'          => 'acfe_seemless_style',
        'key'           => 'acfe_seemless_style',
        'instructions'  => __('Enable better CSS integration: remove borders and padding', 'acfe'),
        'type'              => 'true_false',
        'message'           => '',
        'default_value'     => false,
        'ui'                => true,
        'conditional_logic' => array(
            array(
                array(
                    'field'     => 'acfe_group_modal',
                    'operator'  => '!=',
                    'value'     => '1',
                )
            )
        )
    ));
    
    acf_render_field_setting($field, array(
        'label'         => __('Edition modal', 'acfe'),
        'name'          => 'acfe_group_modal',
        'key'           => 'acfe_group_modal',
        'instructions'  => __('Edit fields in a modal', 'acfe'),
        'type'              => 'true_false',
        'message'           => '',
        'default_value'     => false,
        'ui'                => true,
        'conditional_logic' => array(
            array(
                array(
                    'field'     => 'display',
                    'operator'  => '==',
                    'value'     => 'group',
                ),
            )
        )
    ));
    
    acf_render_field_setting($field, array(
        'label'         => __('Edition modal button', 'acfe'),
        'name'          => 'acfe_group_modal_button',
        'key'           => 'acfe_group_modal_button',
        'instructions'  => __('Text displayed in the edition modal button', 'acfe'),
        'type'          => 'text',
        'placeholder'   => __('Edit', 'acfe'),
        'conditional_logic' => array(
            array(
                array(
                    'field'     => 'display',
                    'operator'  => '==',
                    'value'     => 'group',
                ),
                array(
                    'field'     => 'acfe_group_modal',
                    'operator'  => '==',
                    'value'     => '1',
                ),
            )
        )
    ));
    
}

add_filter('acfe/field_wrapper_attributes/type=group', 'acfe_field_group_wrapper', 10, 2);
function acfe_field_group_wrapper($wrapper, $field){
    
    if(isset($field['acfe_group_modal']) && !empty($field['acfe_group_modal'])){
        
        $wrapper['data-acfe-group-modal'] = 1;
        $wrapper['data-acfe-group-modal-button'] = __('Edit', 'acfe');
        
        if(isset($field['acfe_group_modal_button']) && !empty($field['acfe_group_modal_button'])){
            
            $wrapper['data-acfe-group-modal-button'] = $field['acfe_group_modal_button'];
            
        }
        
    }
    
    return $wrapper;
    
}

add_filter('acf/prepare_field/type=group', 'acfe_field_group_type_class', 99);
function acfe_field_group_type_class($field){
    
    if(acf_maybe_get($field, 'acfe_seemless_style')){
        
        $field['wrapper']['class'] .= ' acfe-seemless-style';
        
    }
    
    $field['wrapper']['class'] .= ' acfe-field-group-layout-' . $field['layout'];
    
    return $field;
    
}
