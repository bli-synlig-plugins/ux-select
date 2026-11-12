<?php

if (!defined('ABSPATH')) {
    exit;
}

class UXS_Sets
{
    public const OPTION = 'uxs_sets';

    /**
     * @return list<array{id: string, label: string, placeholder: string, default: string, options: list<array{id: string, label: string}>}>
     */
    public static function get(): array
    {
        $saved = get_option(self::OPTION, array());

        return self::sanitize($saved);
    }

    /**
     * @return array{id: string, label: string, placeholder: string, default: string, options: list<array{id: string, label: string}>}|null
     */
    public static function get_by_id(string $id)
    {
        $id = sanitize_title($id);
        if ($id === '') {
            return null;
        }

        foreach (self::get() as $set) {
            if ($set['id'] === $id) {
                return $set;
            }
        }

        return null;
    }

    /**
     * @return array{id: string, label: string}|null
     */
    public static function get_option(string $set_id, string $option_id)
    {
        $set = self::get_by_id($set_id);
        if (!$set) {
            return null;
        }

        $option_id = sanitize_title($option_id);
        foreach ($set['options'] as $option) {
            if ($option['id'] === $option_id) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public static function builder_set_choices(): array
    {
        $choices = array(
            '' => __('— Velg sett —', 'ux-select'),
        );

        foreach (self::get() as $set) {
            $choices[$set['id']] = $set['label'];
        }

        return $choices;
    }

    /**
     * UX Builder-felt for Select-innhold: sett + avkrysning per valg.
     *
     * @return array<string, mixed>
     */
    public static function builder_panel_options(): array
    {
        $options = array(
            'set' => array(
                'type'        => 'select',
                'heading'     => __('Sett', 'ux-select'),
                'description' => __('Kryss av ett eller flere valg. Panelet vises når ett av dem er aktivt.', 'ux-select'),
                'default'     => '',
                'options'     => self::builder_set_choices(),
            ),
        );

        foreach (self::get() as $set) {
            foreach ($set['options'] as $option) {
                $options[self::option_attr_key($set['id'], $option['id'])] = array(
                    'type'       => 'checkbox',
                    'heading'    => $option['label'],
                    'default'    => 'false',
                    'conditions' => 'set === "' . $set['id'] . '"',
                );
            }
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function panel_shortcode_defaults(): array
    {
        $defaults = array(
            'set'    => '',
            'target' => '',
            'class'  => '',
            'id'     => '',
        );

        foreach (self::get() as $set) {
            foreach ($set['options'] as $option) {
                $defaults[self::option_attr_key($set['id'], $option['id'])] = 'false';
            }
        }

        return $defaults;
    }

    /**
     * @param array<string, mixed> $atts
     * @return array{set: string, values: list<string>}
     */
    public static function panel_values(array $atts): array
    {
        $set_id = sanitize_title((string) ($atts['set'] ?? ''));
        $values = array();

        if ($set_id !== '') {
            $set = self::get_by_id($set_id);
            if ($set) {
                foreach ($set['options'] as $option) {
                    $key = self::option_attr_key($set['id'], $option['id']);
                    if (self::is_enabled($atts[$key] ?? '')) {
                        $values[] = $option['id'];
                    }
                }
            }
        }

        if ($values) {
            return array(
                'set'    => $set_id,
                'values' => $values,
            );
        }

        $parsed = self::parse_target((string) ($atts['target'] ?? ''));
        if ($parsed['set'] !== '' && $parsed['option'] !== '') {
            return array(
                'set'    => $parsed['set'],
                'values' => array($parsed['option']),
            );
        }

        return array(
            'set'    => $set_id,
            'values' => array(),
        );
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    public static function panel_value_labels(string $set_id, array $values): array
    {
        $labels = array();
        foreach ($values as $option_id) {
            $option = self::get_option($set_id, $option_id);
            $labels[] = $option ? $option['label'] : $option_id;
        }

        return $labels;
    }

    /**
     * @param string $target set:option
     * @return array{set: string, option: string}
     */
    public static function parse_target(string $target): array
    {
        $target = trim($target);
        if ($target === '' || strpos($target, ':') === false) {
            return array('set' => '', 'option' => '');
        }

        $parts = explode(':', $target, 2);

        return array(
            'set'    => sanitize_title($parts[0]),
            'option' => sanitize_title($parts[1]),
        );
    }

    public static function option_attr_key(string $set_id, string $option_id): string
    {
        $set_id = str_replace('-', '_', sanitize_title($set_id));
        $option_id = str_replace('-', '_', sanitize_title($option_id));

        return 'v_' . $set_id . '_' . $option_id;
    }

    /**
     * @param mixed $value
     */
    private static function is_enabled($value): bool
    {
        return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on'), true);
    }

    /**
     * @param mixed $input
     * @return list<array{id: string, label: string, placeholder: string, default: string, options: list<array{id: string, label: string}>}>
     */
    public static function sanitize($input): array
    {
        if (!is_array($input)) {
            return array();
        }

        $sets = array();
        $used_set_ids = array();

        foreach ($input as $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = isset($row['label']) ? sanitize_text_field((string) $row['label']) : '';
            if ($label === '') {
                continue;
            }

            $id = self::unique_id(
                isset($row['id']) ? (string) $row['id'] : '',
                $label,
                $used_set_ids
            );
            if ($id === '') {
                continue;
            }

            $options = array();
            $used_option_ids = array();
            $posted_options = isset($row['options']) && is_array($row['options']) ? $row['options'] : array();

            foreach ($posted_options as $option_row) {
                if (!is_array($option_row)) {
                    continue;
                }

                $option_label = isset($option_row['label']) ? sanitize_text_field((string) $option_row['label']) : '';
                if ($option_label === '') {
                    continue;
                }

                $option_id = self::unique_id(
                    isset($option_row['id']) ? (string) $option_row['id'] : '',
                    $option_label,
                    $used_option_ids
                );
                if ($option_id === '') {
                    continue;
                }

                $options[] = array(
                    'id'    => $option_id,
                    'label' => $option_label,
                );
            }

            $option_ids = array();
            foreach ($options as $option) {
                $option_ids[] = $option['id'];
            }

            $placeholder = isset($row['placeholder']) ? sanitize_text_field((string) $row['placeholder']) : '';
            $default = isset($row['default']) ? sanitize_title((string) $row['default']) : '';
            if ($default !== '' && !in_array($default, $option_ids, true)) {
                $default = '';
            }
            if ($default === '' && $option_ids && $placeholder === '') {
                $default = $option_ids[0];
            }

            $sets[] = array(
                'id'          => $id,
                'label'       => $label,
                'placeholder' => $placeholder,
                'default'     => $default,
                'options'     => $options,
            );
        }

        return $sets;
    }

    /**
     * @param list<string> $used
     */
    private static function unique_id(string $id, string $label, array &$used): string
    {
        $id = sanitize_title($id);
        if ($id === '') {
            $id = sanitize_title($label);
        }
        if ($id === '') {
            return '';
        }

        $base = $id;
        $suffix = 2;
        while (in_array($id, $used, true)) {
            $id = $base . '-' . $suffix;
            $suffix++;
        }
        $used[] = $id;

        return $id;
    }
}
