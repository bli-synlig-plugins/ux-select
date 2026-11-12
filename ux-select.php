<?php
/**
 * Plugin Name:       UX Select
 * Plugin URI:        https://kodekassa.no/plugins/ux-select
 * Description:       Valglister i UX Builder. Knytt en select til innholdspaneler som kan ligge hvor som helst på siden.
 * Version:           1.0.0
 * Author:            Benjamin Pedersen
 * Text Domain:       ux-select
 */

if (!defined('ABSPATH')) {
    exit;
}

define('UXS_VERSION', '1.0.0');
define('UXS_FILE', __FILE__);
define('UXS_DIR', plugin_dir_path(__FILE__));
define('UXS_URL', plugin_dir_url(__FILE__));

require_once UXS_DIR . 'includes/class-uxs-plugin.php';
require_once UXS_DIR . 'includes/class-uxs-sets.php';
require_once UXS_DIR . 'includes/class-uxs-admin.php';
require_once UXS_DIR . 'includes/class-uxs-select.php';
require_once UXS_DIR . 'includes/class-uxs-panel.php';

add_action('plugins_loaded', array('UXS_Plugin', 'init'));
