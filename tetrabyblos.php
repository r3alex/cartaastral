<?php
/**
 * Plugin Name: Tetrabyblos
 * Plugin URI: http://cosmos.pt
 * Description: Allows the insertion of a short code to calculate a user natal horoscope and transits.
 * Version: 0.1.0
 * Author: Miguel Fernandes
 * Author URI: http://cosmos.pt
 * Text Domain: Tetrabyblos
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TETRABYBLOS_VERSION', '0.1.0');

define('TETRABYBLOS_PLUGIN_FILE', __FILE__);

define('TETRABYBLOS_PLUGIN_DIR', plugin_dir_path(__FILE__));

define('TETRABYBLOS_PLUGIN_URL', plugin_dir_url(__FILE__));

function tetrabyblos_activate(): void
{
    add_option('tetrabyblos_version', TETRABYBLOS_VERSION);
}
register_activation_hook(__FILE__, 'tetrabyblos_activate');

function tetrabyblos_load_textdomain(): void
{
    load_plugin_textdomain('Tetrabyblos', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'tetrabyblos_load_textdomain');

function tetrabyblos_enqueue_assets(): void
{
    wp_enqueue_style(
        'tetrabyblos-w3',
        TETRABYBLOS_PLUGIN_URL . 'css/w3.css',
        [],
        TETRABYBLOS_VERSION
    );

    wp_enqueue_style(
        'tetrabyblos-main',
        TETRABYBLOS_PLUGIN_URL . 'css/main.css',
        ['tetrabyblos-w3'],
        TETRABYBLOS_VERSION
    );

    wp_enqueue_script(
        'tetrabyblos-ephemeris',
        TETRABYBLOS_PLUGIN_URL . 'js/ephemeris-0.1.0.js',
        [],
        TETRABYBLOS_VERSION,
        true
    );

    wp_enqueue_script(
        'tetrabyblos-astrochart',
        TETRABYBLOS_PLUGIN_URL . 'js/astrochart.js',
        ['tetrabyblos-ephemeris'],
        TETRABYBLOS_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'tetrabyblos_enqueue_assets');

function tetrabyblos_render_shortcode(): string
{
    ob_start();
    ?>
    <div class="tetrabyblos">
        <div class="w3-content w3-center" id="TETRA_FORM"></div>
        <div class="w3-content w3-center" id="TETRA_OUTPUT"></div>
    </div>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('tetrabyblos', 'tetrabyblos_render_shortcode');
