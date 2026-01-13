<?php
/**
 * Plugin Name: Tetrabyblos
 * Description: Opciones y plantilla para Tetrabyblos.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TETRABYBLOS_OPTION_KEY', 'tetrabyblos_settings');

define('TETRABYBLOS_DEFAULTS_FILE', __DIR__ . '/dbase/users/settings.txt');

function tetrabyblos_read_defaults() {
    if (!file_exists(TETRABYBLOS_DEFAULTS_FILE)) {
        return array();
    }

    $contents = file_get_contents(TETRABYBLOS_DEFAULTS_FILE);
    if ($contents === false) {
        return array();
    }

    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
        return array();
    }

    return $decoded;
}

function tetrabyblos_get_settings() {
    $settings = get_option(TETRABYBLOS_OPTION_KEY);

    if (!is_array($settings) || empty($settings)) {
        $defaults = tetrabyblos_read_defaults();
        if (!empty($defaults)) {
            $settings = $defaults;
            add_option(TETRABYBLOS_OPTION_KEY, $settings);
        } else {
            $settings = array();
        }
    }

    return $settings;
}

function tetrabyblos_get_setting($key, $default = '') {
    $settings = tetrabyblos_get_settings();

    if (array_key_exists($key, $settings)) {
        return $settings[$key];
    }

    return $default;
}

function tetrabyblos_sanitize_html_template($value) {
    $allowed = wp_kses_allowed_html('post');

    foreach ($allowed as $tag => $attrs) {
        $allowed[$tag]['class'] = true;
        $allowed[$tag]['id'] = true;
        $allowed[$tag]['style'] = true;
    }

    $allowed['script'] = array(
        'type' => true,
    );
    $allowed['style'] = array(
        'type' => true,
    );

    return wp_kses($value, $allowed);
}

function tetrabyblos_sanitize_settings($input) {
    $defaults = tetrabyblos_read_defaults();
    $current = get_option(TETRABYBLOS_OPTION_KEY, array());

    $clean = array();
    if (is_array($defaults)) {
        $clean = $defaults;
    }
    if (is_array($current)) {
        $clean = array_merge($clean, $current);
    }

    if (!is_array($input)) {
        return $clean;
    }

    foreach ($input as $key => $value) {
        if ($key === 'textarea_html_output') {
            $clean[$key] = tetrabyblos_sanitize_html_template((string) $value);
            continue;
        }

        if (is_string($value)) {
            $clean[$key] = sanitize_text_field($value);
        } else {
            $clean[$key] = $value;
        }
    }

    return $clean;
}

function tetrabyblos_register_settings() {
    register_setting(
        'tetrabyblos_settings',
        TETRABYBLOS_OPTION_KEY,
        array(
            'type' => 'array',
            'sanitize_callback' => 'tetrabyblos_sanitize_settings',
            'default' => tetrabyblos_read_defaults(),
        )
    );

    add_settings_section(
        'tetrabyblos_main_section',
        __('Opciones principales', 'tetrabyblos'),
        '__return_false',
        'tetrabyblos-settings'
    );

    add_settings_field(
        'tetrabyblos_textarea_html_output',
        __('Plantilla HTML', 'tetrabyblos'),
        'tetrabyblos_field_textarea_html_output',
        'tetrabyblos-settings',
        'tetrabyblos_main_section'
    );

    add_settings_field(
        'tetrabyblos_text_field_title_1',
        __('Título principal', 'tetrabyblos'),
        'tetrabyblos_field_text',
        'tetrabyblos-settings',
        'tetrabyblos_main_section',
        array(
            'key' => 'byblos_text_field_title_1',
        )
    );

    add_settings_field(
        'tetrabyblos_text_field_title_1_1',
        __('Título de resultados', 'tetrabyblos'),
        'tetrabyblos_field_text',
        'tetrabyblos-settings',
        'tetrabyblos_main_section',
        array(
            'key' => 'byblos_text_field_title_1_1',
        )
    );

    add_settings_field(
        'tetrabyblos_select_field_chart',
        __('Mostrar carta', 'tetrabyblos'),
        'tetrabyblos_field_text',
        'tetrabyblos-settings',
        'tetrabyblos_main_section',
        array(
            'key' => 'byblos_select_field_chart',
        )
    );

    add_settings_field(
        'tetrabyblos_select_field_chart_style',
        __('Estilo de carta', 'tetrabyblos'),
        'tetrabyblos_field_text',
        'tetrabyblos-settings',
        'tetrabyblos_main_section',
        array(
            'key' => 'byblos_select_field_chart_style',
        )
    );

    add_settings_field(
        'tetrabyblos_select_field_digsys',
        __('Sistema de dignidades', 'tetrabyblos'),
        'tetrabyblos_field_text',
        'tetrabyblos-settings',
        'tetrabyblos_main_section',
        array(
            'key' => 'byblos_select_field_digsys',
        )
    );
}
add_action('admin_init', 'tetrabyblos_register_settings');

function tetrabyblos_register_admin_menu() {
    add_menu_page(
        __('Tetrabyblos', 'tetrabyblos'),
        __('Tetrabyblos', 'tetrabyblos'),
        'manage_options',
        'tetrabyblos-settings',
        'tetrabyblos_render_settings_page',
        'dashicons-star-filled'
    );
}
add_action('admin_menu', 'tetrabyblos_register_admin_menu');

function tetrabyblos_field_textarea_html_output() {
    $settings = tetrabyblos_get_settings();
    $value = isset($settings['textarea_html_output']) ? $settings['textarea_html_output'] : '';

    printf(
        '<textarea name="%1$s[textarea_html_output]" rows="12" cols="80" class="large-text code">%2$s</textarea>',
        esc_attr(TETRABYBLOS_OPTION_KEY),
        esc_textarea($value)
    );

    echo '<p class="description">' . esc_html__('Contenido HTML para la plantilla.', 'tetrabyblos') . '</p>';
}

function tetrabyblos_field_text($args) {
    $key = isset($args['key']) ? $args['key'] : '';
    if ($key === '') {
        return;
    }

    $settings = tetrabyblos_get_settings();
    $value = isset($settings[$key]) ? $settings[$key] : '';

    printf(
        '<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s" />',
        esc_attr(TETRABYBLOS_OPTION_KEY),
        esc_attr($key),
        esc_attr($value)
    );
}

function tetrabyblos_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Opciones de Tetrabyblos', 'tetrabyblos') . '</h1>';
    echo '<form method="post" action="options.php">';

    settings_fields('tetrabyblos_settings');
    do_settings_sections('tetrabyblos-settings');
    submit_button();

    echo '</form>';
    echo '</div>';
}
