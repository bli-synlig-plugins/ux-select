<?php

if (!defined('ABSPATH')) {
    exit;
}

class UXS_Select
{
    public const TAG = 'uxs_select';

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
            'name'     => __('Select', 'ux-select'),
            'category' => __('Content', 'flatsome'),
            'icon'     => 'dashicons-arrow-down-alt2',
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
                'options' => array(
                    'set' => array(
                        'type'    => 'select',
                        'heading' => __('Sett', 'ux-select'),
                        'default' => '',
                        'options' => UXS_Sets::builder_set_choices(),
                    ),
                    'label' => array(
                        'type'       => 'textfield',
                        'heading'    => __('Ledetekst', 'ux-select'),
                        'default'    => '',
                        'full_width' => true,
                    ),
                    'placeholder' => array(
                        'type'       => 'textfield',
                        'heading'    => __('Placeholder', 'ux-select'),
                        'description'=> __('Overstyrer placeholder fra admin. Tom = bruk settet.', 'ux-select'),
                        'default'    => '',
                        'full_width' => true,
                    ),
                ),
            ),
            'style_options' => array(
                'type'    => 'group',
                'heading' => __('Stil', 'ux-select'),
                'options' => array(
                    'width' => array(
                        'type'    => 'slider',
                        'heading' => __('Maks bredde', 'ux-select'),
                        'default' => '0',
                        'min'     => '0',
                        'max'     => '800',
                        'step'    => '10',
                        'unit'    => 'px',
                    ),
                    'font_size' => array(
                        'type'    => 'slider',
                        'heading' => __('Skriftstørrelse', 'ux-select'),
                        'default' => '16',
                        'min'     => '12',
                        'max'     => '24',
                        'step'    => '1',
                        'unit'    => 'px',
                    ),
                ),
            ),
            'color_options' => array(
                'type'    => 'group',
                'heading' => __('Farger', 'ux-select'),
                'options' => array(
                    'background' => UXS_Plugin::color_option(__('Bakgrunn', 'ux-select')),
                    'text_color' => UXS_Plugin::color_option(__('Tekst', 'ux-select')),
                    'border'     => UXS_Plugin::color_option(__('Kant', 'ux-select')),
                ),
            ),
            'advanced_options' => UXS_Plugin::advanced_options(),
        );
    }

    /**
     * @param mixed $atts
     */
    public static function render($atts, $content = ''): string
    {
        unset($content);
        $atts = shortcode_atts(array(
            'set'         => '',
            'label'       => '',
            'placeholder' => '',
            'width'       => '0',
            'font_size'   => '16',
            'background'  => '',
            'text_color'  => '',
            'border'      => '',
            'class'       => '',
            'id'          => '',
        ), is_array($atts) ? $atts : array(), self::TAG);

        $set_id = sanitize_title((string) $atts['set']);
        $set = $set_id !== '' ? UXS_Sets::get_by_id($set_id) : null;
        $builder = UXS_Plugin::is_builder_preview();

        if (!$set) {
            if (!$builder) {
                return '';
            }

            return '<p class="uxs-select-empty">' . esc_html__('Velg et sett i Select-elementet. Opprett sett under Innstillinger → UX Select.', 'ux-select') . '</p>';
        }

        UXS_Plugin::enqueue_assets();

        $placeholder = trim((string) $atts['placeholder']);
        if ($placeholder === '') {
            $placeholder = $set['placeholder'];
        }

        $selected = $set['default'];
        if ($placeholder !== '' && $set['default'] === '') {
            $selected = '';
        }
        if ($selected === '' && $placeholder === '' && $set['options']) {
            $selected = $set['options'][0]['id'];
        }

        $label = sanitize_text_field((string) $atts['label']);
        $select_id = UXS_Plugin::sanitize_html_id((string) $atts['id']);
        if ($select_id === '') {
            $select_id = 'uxs-select-' . $set['id'] . '-' . wp_unique_id();
        }

        $classes = array('uxs-select');
        if ($builder) {
            $classes[] = 'uxs-is-builder';
        }
        foreach (UXS_Plugin::class_list((string) $atts['class']) as $class) {
            $classes[] = $class;
        }

        $width = max(0, (int) $atts['width']);
        $font = max(12, min(24, (int) $atts['font_size']));
        $vars = array(
            '--uxs-font-size:' . $font . 'px',
        );
        if ($width > 0) {
            $vars[] = '--uxs-max-width:' . $width . 'px';
        }
        $bg = UXS_Plugin::sanitize_color((string) $atts['background']);
        $fg = UXS_Plugin::sanitize_color((string) $atts['text_color']);
        $bd = UXS_Plugin::sanitize_color((string) $atts['border']);
        if ($bg !== '') {
            $vars[] = '--uxs-bg:' . $bg;
        }
        if ($fg !== '') {
            $vars[] = '--uxs-fg:' . $fg;
        }
        if ($bd !== '') {
            $vars[] = '--uxs-border:' . $bd;
        }

        ob_start();
        ?>
        <div
            class="<?php echo esc_attr(implode(' ', $classes)); ?>"
            style="<?php echo esc_attr(implode(';', $vars)); ?>"
            <?php echo $builder ? ' data-uxs-builder="1"' : ''; ?>
        >
            <?php if ($label !== '') : ?>
                <label class="uxs-select__label" for="<?php echo esc_attr($select_id); ?>"><?php echo esc_html($label); ?></label>
            <?php endif; ?>
            <select
                class="uxs-select__control"
                id="<?php echo esc_attr($select_id); ?>"
                data-uxs-select
                data-uxs-set="<?php echo esc_attr($set['id']); ?>"
                data-uxs-default="<?php echo esc_attr($selected); ?>"
            >
                <?php if ($placeholder !== '') : ?>
                    <option value="" <?php selected($selected, ''); ?>><?php echo esc_html($placeholder); ?></option>
                <?php endif; ?>
                <?php foreach ($set['options'] as $option) : ?>
                    <option value="<?php echo esc_attr($option['id']); ?>" <?php selected($selected, $option['id']); ?>>
                        <?php echo esc_html($option['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}
