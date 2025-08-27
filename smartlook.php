<?php
/**
 * Plugin Name: Smartlook
 * Plugin URI: https://webfeszek.hu/plugins#smartlook
 * Author URI: https://webfeszek.hu
 * Description: Smartlook user recording integration
 * Version: 1.0.0
 * Author: Tamas Amon
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smartlook
 * Domain Path: /languages
 */

// Biztonsági ellenőrzés - közvetlen hozzáférés tiltása
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Smartlook Plugin Class
 */
class Smartlook_Plugin {

    /**
     * Plugin verzió
     */
    const VERSION = '1.0.0';

    /**
     * Konstruktor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Plugin inicializálás
     */
    private function init() {
        // Load text domain for translations
        add_action('init', array($this, 'load_textdomain'));

        // Admin menü hozzáadása
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Beállítások regisztrálása
        add_action('admin_init', array($this, 'register_settings'));

        // JavaScript kód beszúrása a head-be
        add_action('wp_head', array($this, 'insert_smartlook_code'));
    }

    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain('smartlook', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Admin menü hozzáadása
     */
    public function add_admin_menu() {
        add_options_page(
            __('Smartlook Settings', 'smartlook'),
            __('Smartlook', 'smartlook'),
            'manage_options',
            'smartlook-settings',
            array($this, 'admin_page')
        );
    }

    /**
     * Beállítások regisztrálása
     */
    public function register_settings() {
        register_setting('smartlook_options', 'smartlook_api_key');
        register_setting('smartlook_options', 'smartlook_region');

        add_settings_section(
            'smartlook_main',
            __('Smartlook API Settings', 'smartlook'),
            array($this, 'settings_section_callback'),
            'smartlook-settings'
        );

        add_settings_field(
            'smartlook_api_key',
            __('API Key', 'smartlook'),
            array($this, 'api_key_field_callback'),
            'smartlook-settings',
            'smartlook_main'
        );

        add_settings_field(
            'smartlook_region',
            __('Data center', 'smartlook'),
            array($this, 'region_field_callback'),
            'smartlook-settings',
            'smartlook_main'
        );
    }

    /**
     * Beállítások szekció callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Enter your Smartlook API key to activate user recording.', 'smartlook') . '</p>';
    }

    /**
     * API kulcs mező callback
     */
    public function api_key_field_callback() {
        $api_key = get_option('smartlook_api_key');
        echo '<input type="text" id="smartlook_api_key" name="smartlook_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('The Smartlook API key you get from your Smartlook dashboard.', 'smartlook') . '</p>';
    }

    /**
     * Régió mező callback
     */
    public function region_field_callback() {
        $region = get_option('smartlook_region', 'eu');
        $regions = array(
            'eu' => __('Europe', 'smartlook'),
            'us' => __('United States', 'smartlook'),
        );

        echo '<select id="smartlook_region" name="smartlook_region">';
        foreach ($regions as $value => $label) {
            $selected = selected($region, $value, false);
            echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Choose the Smartlook service region.', 'smartlook') . '</p>';
    }

    /**
     * Admin oldal megjelenítése
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smartlook Settings', 'smartlook'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smartlook_options');
                do_settings_sections('smartlook-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Smartlook JavaScript kód beszúrása
     */
    public function insert_smartlook_code() {
        $api_key = get_option('smartlook_api_key');
        $region = get_option('smartlook_region', 'eu');

        // Ha nincs API kulcs, ne futtassa a kódot
        if (empty($api_key)) {
            return;
        }

        // JavaScript kód beszúrása
        ?>
        <script type='text/javascript'>
            window.smartlook||(function(d) {
                var o=smartlook=function(){ o.api.push(arguments)},h=d.getElementsByTagName('head')[0];
                var c=d.createElement('script');o.api=new Array();c.async=true;c.type='text/javascript';
                c.charset='utf-8';c.src='https://web-sdk.smartlook.com/recorder.js';h.appendChild(c);
            })(document);
            smartlook('init', '<?php echo esc_js($api_key); ?>', { region: '<?php echo esc_js($region); ?>' });
        </script>
        <?php
    }
}

// Plugin indítása
new Smartlook_Plugin();
