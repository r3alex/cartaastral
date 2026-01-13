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
define('TETRABYBLOS_DEFAULT_SETTINGS_FILE', 'dbase/users/settings.txt');
define('TETRABYBLOS_CITIES_FILE', 'data/cities_results_1000.txt');
define('TETRABYBLOS_DEFS_DIR', 'dbase');

function tetrabyblos_activate(): void
{
    add_option('tetrabyblos_version', TETRABYBLOS_VERSION);

    if (get_option('tetrabyblos_settings') === false) {
        $defaults = tetrabyblos_get_default_settings();
        if (!empty($defaults)) {
            add_option('tetrabyblos_settings', $defaults);
        }
    }
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

    wp_enqueue_script(
        'tetrabyblos-app',
        TETRABYBLOS_PLUGIN_URL . 'js/tetrabyblos-app.js',
        ['tetrabyblos-astrochart'],
        TETRABYBLOS_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'tetrabyblos_enqueue_assets');

function tetrabyblos_get_data_path(string $relative_path): string
{
    return TETRABYBLOS_PLUGIN_DIR . ltrim($relative_path, '/');
}

function tetrabyblos_read_file_contents(string $relative_path): ?string
{
    $path = tetrabyblos_get_data_path($relative_path);

    if (!is_readable($path)) {
        return null;
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        return null;
    }

    return $contents;
}

function tetrabyblos_get_default_settings(): array
{
    $contents = tetrabyblos_read_file_contents(TETRABYBLOS_DEFAULT_SETTINGS_FILE);
    if ($contents === null) {
        return [];
    }

    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

function tetrabyblos_get_settings(): array
{
    $settings = get_option('tetrabyblos_settings', []);
    if (!is_array($settings) || empty($settings)) {
        $settings = tetrabyblos_get_default_settings();
    }

    return $settings;
}

function tetrabyblos_get_template_html(array $settings): string
{
    if (!array_key_exists('textarea_html_output', $settings)) {
        return '';
    }

    return (string) $settings['textarea_html_output'];
}

function tetrabyblos_load_def_file(string $basename): array
{
    $cache_key = 'tetrabyblos_def_' . sanitize_key($basename);
    $cached = wp_cache_get($cache_key, 'tetrabyblos');
    if ($cached !== false) {
        return $cached;
    }

    $contents = tetrabyblos_read_file_contents(TETRABYBLOS_DEFS_DIR . '/' . $basename . '.def');
    if ($contents === null) {
        wp_cache_set($cache_key, [], 'tetrabyblos');
        return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', trim($contents));
    $parsed = [];

    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parsed[] = str_getcsv($line);
    }

    wp_cache_set($cache_key, $parsed, 'tetrabyblos');

    return $parsed;
}

function tetrabyblos_load_cities(int $limit = 0): array
{
    $cache_key = 'tetrabyblos_cities_' . $limit;
    $cached = wp_cache_get($cache_key, 'tetrabyblos');
    if ($cached !== false) {
        return $cached;
    }

    $contents = tetrabyblos_read_file_contents(TETRABYBLOS_CITIES_FILE);
    if ($contents === null) {
        wp_cache_set($cache_key, [], 'tetrabyblos');
        return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', trim($contents));
    $cities = [];

    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }

        $parts = explode(';', $line);
        if (count($parts) < 9) {
            continue;
        }

        $cities[] = [
            'slug' => $parts[0],
            'name' => $parts[1],
            'feature' => $parts[2],
            'latitude' => (float) $parts[3],
            'longitude' => (float) $parts[4],
            'country_code' => $parts[5],
            'country' => $parts[6],
            'region' => $parts[7],
            'timezone' => $parts[8],
        ];

        if ($limit > 0 && count($cities) >= $limit) {
            break;
        }
    }

    wp_cache_set($cache_key, $cities, 'tetrabyblos');

    return $cities;
}

function tetrabyblos_render_shortcode(): string
{
    $settings = tetrabyblos_get_settings();
    $template = tetrabyblos_get_template_html($settings);

    ob_start();
    ?>
    <div class="tetrabyblos">
        <div class="w3-content w3-center" id="TETRA_FORM"></div>
        <?php if ($template !== '') : ?>
            <div class="tetrabyblos-template">
                <?php echo wp_kses_post($template); ?>
            </div>
        <?php else : ?>
            <div class="w3-content w3-center" id="TETRA_OUTPUT"></div>
        <?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('tetrabyblos', 'tetrabyblos_render_shortcode');
