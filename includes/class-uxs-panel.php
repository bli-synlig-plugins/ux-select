<?php

if (!defined('ABSPATH')) {
    exit;
}

class UXS_Panel
{
    public const TAG = 'uxs_panel';

    public static function init(): void
    {
        add_shortcode(self::TAG, array(__CLASS__, 'render'));
        add_action('ux_builder_setup', array(__CLASS__, 'register'));
    }

    public static function register(): void
    {
        if (!function_exists('add_ux_builder_shortcode')) {
            return;
        }

        add_ux_builder_shortcode(self::TAG, array(
            'type'     => 'container',
            'name'     => __('Select-innhold', 'ux-select'),
            'category' => __('Content', 'flatsome'),
            'icon'     => 'dashicons-index-card',
            'info'     => '{{ set }}',
            'wrap'     => false,
            'options'  => self::options(),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private static function options(): array
    {
        return array(
            'content_options' => array(
                'type'    => 'group',
                'heading' => __('Innhold', 'ux-select'),
                'options' => UXS_Sets::builder_panel_options(),
            ),
            'advanced_options' => UXS_Plugin::advanced_options(),
        );
    }

    /**
     * @param mixed $atts
     */
    public static function render($atts, $content = ''): string
    {
        $atts = shortcode_atts(
            UXS_Sets::panel_shortcode_defaults(),
            is_array($atts) ? $atts : array(),
            self::TAG
        );

        $parsed = UXS_Sets::panel_values($atts);
        $builder = UXS_Plugin::is_builder_preview();

        if ($parsed['set'] === '' || $parsed['values'] === array()) {
            if (!$builder) {
                return '';
            }

            return '<p class="uxs-panel-empty">' . esc_html__('Velg sett og minst ett valg på Select-innhold.', 'ux-select') . '</p>';
        }

        UXS_Plugin::enqueue_assets();

        $labels = UXS_Sets::panel_value_labels($parsed['set'], $parsed['values']);
        $badge = implode(', ', $labels);
        $inner = do_shortcode($content);

        $set = UXS_Sets::get_by_id($parsed['set']);
        $selected = $set ? $set['default'] : '';
        if ($selected === '' && $set && $set['options'] && $set['placeholder'] === '') {
            $selected = $set['options'][0]['id'];
        }
        $visible = $builder || in_array($selected, $parsed['values'], true);

        $classes = array('uxs-panel');
        if ($builder) {
            $classes[] = 'uxs-is-builder';
        }
        foreach (UXS_Plugin::class_list((string) $atts['class']) as $class) {
            $classes[] = $class;
        }

        $html_id = UXS_Plugin::sanitize_html_id((string) $atts['id']);
        $id_attr = $html_id !== '' ? ' id="' . esc_attr($html_id) . '"' : '';

        $hidden_attr = $visible ? '' : ' hidden';
        $aria = $visible ? 'false' : 'true';
        $values_attr = implode(',', $parsed['values']);

        ob_start();
        ?>
        <div
            class="<?php echo esc_attr(implode(' ', $classes)); ?>"
            <?php echo $id_attr; ?>
            data-uxs-panel
            data-uxs-set="<?php echo esc_attr($parsed['set']); ?>"
            data-uxs-values="<?php echo esc_attr($values_attr); ?>"
            aria-hidden="<?php echo esc_attr($aria); ?>"
            <?php echo $builder ? ' data-uxs-builder="1"' : ''; ?>
            <?php echo $hidden_attr; ?>
        >
            <?php if ($builder) : ?>
                <span class="uxs-panel__badge"><?php echo esc_html($badge); ?></span>
            <?php endif; ?>
            <?php echo $inner; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}
