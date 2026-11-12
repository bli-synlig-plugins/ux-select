<?php

if (!defined('ABSPATH')) {
    exit;
}

class UXS_Plugin
{
    public static function init(): void
    {
        add_filter(
            'plugin_action_links_' . plugin_basename(UXS_FILE),
            array(__CLASS__, 'action_links')
        );
        add_action('admin_notices', array(__CLASS__, 'admin_notices'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'), 5);
        add_action('ux_builder_enqueue_scripts', array(__CLASS__, 'enqueue_builder'));

        UXS_Admin::init();
        UXS_Select::init();
        UXS_Panel::init();
    }

    /**
     * @return array{sets: list<array<string, mixed>>}
     */
    public static function get_options(): array
    {
        return array(
            'sets' => UXS_Sets::get(),
        );
    }

    /**
     * @param mixed $input
     * @return array{sets: list<array<string, mixed>>}
     */
    public static function sanitize_settings($input): array
    {
        $input = is_array($input) ? $input : array();

        return array(
            'sets' => UXS_Sets::sanitize($input['sets'] ?? array()),
        );
    }

    /**
     * @param array<string, string> $links
     * @return array<string, string>
     */
    public static function action_links(array $links): array
    {
        $settings = '<a href="' . esc_url(admin_url('options-general.php?page=ux-select')) . '">'
            . esc_html__('Innstillinger', 'ux-select')
            . '</a>';
        array_unshift($links, $settings);

        return $links;
    }

    public static function has_flatsome(): bool
    {
        return function_exists('add_ux_builder_shortcode')
            || function_exists('flatsome_option')
            || class_exists('Flatsome_Default', false)
            || defined('FLATSOME_VERSION');
    }

    public static function admin_notices(): void
    {
        if (self::has_flatsome() || !current_user_can('activate_plugins')) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('UX Select trenger temaet Flatsome for UX Builder-elementene. Shortcodene rendres fortsatt.', 'ux-select');
        echo '</p></div>';
    }

    public static function register_assets(): void
    {
        wp_register_style(
            'ux-select',
            UXS_URL . 'assets/css/frontend.css',
            array(),
            self::asset_ver('assets/css/frontend.css')
        );
        wp_register_script(
            'ux-select',
            UXS_URL . 'assets/js/frontend.js',
            array(),
            self::asset_ver('assets/js/frontend.js'),
            true
        );
    }

    public static function enqueue_assets(): void
    {
        self::register_assets();
        wp_enqueue_style('ux-select');
        wp_enqueue_script('ux-select');
    }

    /**
     * @param string $context editor|content
     */
    public static function enqueue_builder($context = ''): void
    {
        if ($context !== 'content') {
            return;
        }

        self::enqueue_assets();
    }

    public static function asset_ver(string $relative): string
    {
        $path = UXS_DIR . $relative;
        $mtime = is_readable($path) ? (string) filemtime($path) : '';

        return UXS_VERSION . ($mtime !== '' ? '.' . $mtime : '');
    }

    public static function is_builder_preview(): bool
    {
        if (defined('UX_BUILDER_IFRAME') && UX_BUILDER_IFRAME) {
            return true;
        }

        if (defined('UX_BUILDER_DOING_AJAX') || defined('UX_BUILDER_AJAX_REQUEST')) {
            return true;
        }

        return function_exists('ux_builder_is_iframe') && ux_builder_is_iframe();
    }

    /**
     * @return array<string, mixed>
     */
    public static function advanced_options(): array
    {
        return array(
            'type'    => 'group',
            'heading' => __('Avansert', 'ux-select'),
            'options' => array(
                'class' => array(
                    'type'       => 'textfield',
                    'heading'    => __('Klasse', 'ux-select'),
                    'default'    => '',
                    'full_width' => true,
                ),
                'id' => array(
                    'type'    => 'textfield',
                    'heading' => __('ID', 'ux-select'),
                    'default' => '',
                ),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function color_option(string $heading): array
    {
        return array(
            'type'     => 'colorpicker',
            'heading'  => $heading,
            'default'  => '',
            'format'   => 'rgb',
            'alpha'    => true,
            'position' => 'bottom right',
            'helpers'  => self::color_helpers(),
        );
    }

    /**
     * @return list<array{title: string, value: string}>
     */
    public static function color_helpers(): array
    {
        $file = get_template_directory() . '/inc/builder/shortcodes/helpers/colors.php';
        if (is_readable($file)) {
            $helpers = require $file;
            if (is_array($helpers) && $helpers !== []) {
                return $helpers;
            }
        }

        return array(
            array('title' => 'Dark', 'value' => 'rgb(0,0,0)'),
            array('title' => 'White', 'value' => 'rgb(255,255,255)'),
            array('title' => 'Primary', 'value' => ''),
            array('title' => 'Secondary', 'value' => ''),
            array('title' => 'Success', 'value' => ''),
        );
    }

    public static function sanitize_color(string $color, string $fallback = ''): string
    {
        $color = trim($color);
        if ($color === '') {
            return $fallback;
        }

        if (preg_match('/^#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color)) {
            return $color;
        }

        if (preg_match('/^rgba?\(\s*(?:\d{1,3}\s*,\s*){2}\d{1,3}\s*(?:,\s*(?:0|1|0?\.\d+)\s*)?\)$/', $color)) {
            return $color;
        }

        if (preg_match('/^hsla?\(\s*\d{1,3}(?:deg)?\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*(?:,\s*(?:0|1|0?\.\d+)\s*)?\)$/', $color)) {
            return $color;
        }

        if (preg_match('/^var\(\s*--[a-zA-Z0-9_-]+\s*\)$/', $color)) {
            return $color;
        }

        $named = array('transparent', 'currentcolor', 'inherit', 'black', 'white');
        $lower = strtolower($color);
        if (in_array($lower, $named, true)) {
            return $lower === 'currentcolor' ? 'currentColor' : $lower;
        }

        return $fallback;
    }

    /**
     * @return list<string>
     */
    public static function class_list(string $value): array
    {
        $classes = array();
        $value = trim($value);
        if ($value === '') {
            return $classes;
        }

        foreach (preg_split('/\s+/', $value) as $part) {
            $clean = sanitize_html_class($part);
            if ($clean !== '') {
                $classes[] = $clean;
            }
        }

        return array_values(array_unique($classes));
    }

    public static function sanitize_html_id(string $value): string
    {
        $id = preg_replace('/[^A-Za-z0-9_-]/', '', $value);

        return is_string($id) ? $id : '';
    }
}
