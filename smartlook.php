<?php
/**
 * Plugin Name: WF Smartlook
 * Plugin URI: https://webfeszek.hu/plugins#wf-smartlook
 * Author URI: https://webfeszek.hu
 * Description: Smartlook user recording integration
 * Version: 0.0.5
 * Author: Tamas Amon
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.2.0.html
 * Text Domain: wf-smartlook
 * GitHub Plugin URI: https://github.com/webfeszek/wf-smartlook
 */

// Biztonsági ellenőrzés - közvetlen hozzáférés tiltása
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WF Smartlook Plugin Class
 */
class WF_Smartlook_Plugin {

    /**
     * Plugin verzió
     */
    const VERSION = '0.0.5';

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
        // Admin menü hozzáadása
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Beállítások regisztrálása
        add_action('admin_init', array($this, 'register_settings'));

        // JavaScript kód beszúrása a head-be
        add_action('wp_head', array($this, 'insert_smartlook_code'));
    }

    /**
     * Admin menü hozzáadása
     */
    public function add_admin_menu() {
        add_options_page(
            esc_html__('Smartlook Settings', 'wf-smartlook'),
            esc_html__('Smartlook', 'wf-smartlook'),
            'manage_options',
            'smartlook-settings',
            array($this, 'admin_page')
        );
    }

    /**
     * Beállítások regisztrálása
     */
    public function register_settings() {
        register_setting('smartlook_options', 'smartlook_api_key', array(
            'sanitize_callback' => array($this, 'sanitize_api_key')
        ));
        register_setting('smartlook_options', 'smartlook_region', array(
            'sanitize_callback' => array($this, 'sanitize_region')
        ));

        add_settings_section(
            'smartlook_main',
            esc_html__('Smartlook API Settings', 'wf-smartlook'),
            array($this, 'settings_section_callback'),
            'smartlook-settings'
        );

        add_settings_field(
            'smartlook_api_key',
            esc_html__('API Key', 'wf-smartlook'),
            array($this, 'api_key_field_callback'),
            'smartlook-settings',
            'smartlook_main'
        );

        add_settings_field(
            'smartlook_region',
            esc_html__('Data center', 'wf-smartlook'),
            array($this, 'region_field_callback'),
            'smartlook-settings',
            'smartlook_main'
        );
    }

    /**
     * Beállítások szekció callback
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__('Enter your Smartlook API key to activate user recording.', 'wf-smartlook') . '</p>';
    }

    /**
     * API kulcs mező callback
     */
    public function api_key_field_callback() {
        $api_key = get_option('smartlook_api_key');
        echo '<input type="text" id="smartlook_api_key" name="smartlook_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('The Smartlook API key you get from your Smartlook dashboard.', 'wf-smartlook') . '</p>';
    }

    /**
     * Régió mező callback
     */
    public function region_field_callback() {
        $region = get_option('smartlook_region', 'eu');
        $regions = array(
            'eu' => esc_html__('Europe', 'wf-smartlook'),
            'us' => esc_html__('United States', 'wf-smartlook'),
            'au' => esc_html__('Australia', 'wf-smartlook'),
            'ca' => esc_html__('Canada', 'wf-smartlook'),
            'sg' => esc_html__('Singapore', 'wf-smartlook'),
            'jp' => esc_html__('Japan', 'wf-smartlook')
        );

        echo '<select id="smartlook_region" name="smartlook_region">';
        foreach ($regions as $value => $label) {
            $selected = selected($region, $value, false);
            echo '<option value="' . esc_attr($value) . '" ' . esc_html($selected) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Choose the Smartlook service region.', 'wf-smartlook') . '</p>';
    }

    /**
     * Admin oldal megjelenítése
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Smartlook Settings', 'wf-smartlook'); ?></h1>
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
     * API kulcs sanitization
     */
    public function sanitize_api_key($input) {
        // Csak alfanumerikus karakterek és kötőjelek engedélyezettek
        $sanitized = sanitize_text_field($input);

        // Ellenőrizzük, hogy a Smartlook API kulcs formátuma megfelelő-e
        if (!empty($sanitized) && !preg_match('/^[a-zA-Z0-9]{32,}$/', $sanitized)) {
            add_settings_error(
                'smartlook_api_key',
                'invalid_api_key',
                esc_html__('Invalid Smartlook API key format. Please check your key.', 'wf-smartlook')
            );
            return get_option('smartlook_api_key'); // Visszaállítjuk a régi értéket
        }

        return $sanitized;
    }

    /**
     * Régió sanitization
     */
    public function sanitize_region($input) {
        $allowed_regions = array('eu', 'us', 'au', 'ca', 'sg', 'jp');
        $sanitized = sanitize_text_field($input);

        // Ellenőrizzük, hogy a régió érvényes-e
        if (!in_array($sanitized, $allowed_regions, true)) {
            add_settings_error(
                'smartlook_region',
                'invalid_region',
                esc_html__('Invalid region selected. Please choose a valid region.', 'wf-smartlook')
            );
            return 'eu'; // Alapértelmezett régió
        }

        return $sanitized;
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
new WF_Smartlook_Plugin();
