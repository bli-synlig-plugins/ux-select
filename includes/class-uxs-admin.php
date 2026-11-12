<?php

if (!defined('ABSPATH')) {
    exit;
}

class UXS_Admin
{
    public static function init(): void
    {
        add_action('admin_menu', array(__CLASS__, 'add_page'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue'));
        add_action('wp_ajax_uxs_save_settings', array(__CLASS__, 'ajax_save'));
    }

    public static function add_page(): void
    {
        add_options_page(
            __('UX Select', 'ux-select'),
            __('UX Select', 'ux-select'),
            'manage_options',
            'ux-select',
            array(__CLASS__, 'render')
        );
    }

    public static function enqueue(string $hook): void
    {
        if ($hook !== 'settings_page_ux-select') {
            return;
        }

        wp_enqueue_style(
            'uxs-admin',
            UXS_URL . 'assets/css/admin.css',
            array(),
            UXS_Plugin::asset_ver('assets/css/admin.css')
        );

        wp_enqueue_script(
            'uxs-admin',
            UXS_URL . 'assets/js/admin.js',
            array(),
            UXS_Plugin::asset_ver('assets/js/admin.js'),
            true
        );

        wp_localize_script('uxs-admin', 'uxsAdmin', array(
            'settings' => UXS_Plugin::get_options(),
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('uxs_save_settings'),
            'i18n'     => array(
                'saved'        => __('Endringene er lagret', 'ux-select'),
                'error'        => __('Kunne ikke lagre innstillingene. Prøv igjen.', 'ux-select'),
                'empty'        => __('Ingen sett ennå. Trykk «Legg til sett» for å starte.', 'ux-select'),
                'setName'      => __('Navn', 'ux-select'),
                'setSlug'      => __('Slug', 'ux-select'),
                'slugPending'  => __('Lages fra navnet når du lagrer', 'ux-select'),
                'placeholder'  => __('Placeholder', 'ux-select'),
                'placeholderH' => __('Valgfri tom rad øverst i listen.', 'ux-select'),
                'defaultOpt'   => __('Standardvalg', 'ux-select'),
                'firstOption'  => __('Første valg', 'ux-select'),
                'options'      => __('Valg', 'ux-select'),
                'optionLabel'  => __('Etikett', 'ux-select'),
                'addOption'    => __('Legg til valg', 'ux-select'),
                'removeSet'    => __('Fjern sett', 'ux-select'),
                'removeOption' => __('Fjern', 'ux-select'),
                'removeConfirm'=> __('Fjerne dette settet?', 'ux-select'),
                'unnamed'      => __('Nytt sett', 'ux-select'),
            ),
        ));
    }

    public static function ajax_save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Ingen tilgang.', 'ux-select')), 403);
        }

        check_ajax_referer('uxs_save_settings', 'nonce');

        $raw = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : '';
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : array();
        }
        if (!is_array($raw)) {
            $raw = array();
        }

        $clean = UXS_Plugin::sanitize_settings($raw);
        update_option(UXS_Sets::OPTION, $clean['sets']);

        wp_send_json_success(array(
            'settings' => UXS_Plugin::get_options(),
            'message'  => __('Endringene er lagret', 'ux-select'),
        ));
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap uxs-admin">
            <div class="uxs-app">
                <header class="uxs-top">
                    <div>
                        <h1><?php echo esc_html__('UX Select', 'ux-select'); ?></h1>
                        <p><?php echo esc_html__('Opprett valglister her. I UX Builder velger du et sett på Select, og knytter Select-innhold til ett valg.', 'ux-select'); ?></p>
                    </div>
                    <span class="uxs-pill">v<?php echo esc_html(UXS_VERSION); ?></span>
                </header>

                <nav class="uxs-nav" role="tablist" aria-label="<?php echo esc_attr__('Innstillinger', 'ux-select'); ?>">
                    <button type="button" role="tab" aria-selected="true" data-tab="sets"><?php echo esc_html__('Sett', 'ux-select'); ?></button>
                    <button type="button" role="tab" aria-selected="false" data-tab="help"><?php echo esc_html__('Bruk', 'ux-select'); ?></button>
                </nav>

                <main class="uxs-main">
                    <section class="uxs-tab is-on" id="uxs-tab-sets" role="tabpanel">
                        <p class="uxs-toolbar">
                            <button type="button" class="uxs-btn is-primary" id="uxs-add-set"><?php echo esc_html__('Legg til sett', 'ux-select'); ?></button>
                        </p>
                        <div id="uxs-sets"></div>
                    </section>

                    <section class="uxs-tab" id="uxs-tab-help" role="tabpanel" hidden>
                        <div class="uxs-card">
                            <h2><?php echo esc_html__('Slik bruker du det', 'ux-select'); ?></h2>
                            <p class="uxs-sub"><?php echo esc_html__('Select og innhold er to separate elementer, så de kan ligge hvor som helst på siden.', 'ux-select'); ?></p>
                            <ol class="uxs-help">
                                <li><?php echo esc_html__('Lag et sett (f.eks. Fylker) og legg inn valg.', 'ux-select'); ?></li>
                                <li><?php echo esc_html__('I UX Builder: Content → Select. Velg settet.', 'ux-select'); ?></li>
                                <li><?php echo esc_html__('Legg inn Select-innhold der informasjonen skal vises. Velg samme sett og ett valg, og dropp tekst, rader og andre elementer inni.', 'ux-select'); ?></li>
                            </ol>
                        </div>
                    </section>
                </main>
            </div>

            <div class="uxs-save" id="uxs-save" role="region" aria-label="<?php echo esc_attr__('Ulagrede endringer', 'ux-select'); ?>">
                <span><?php echo esc_html__('Du har ulagrede endringer', 'ux-select'); ?></span>
                <button type="button" class="uxs-btn" id="uxs-rst"><?php echo esc_html__('Tilbakestill', 'ux-select'); ?></button>
                <button type="button" class="uxs-btn is-primary" id="uxs-sv"><?php echo esc_html__('Lagre endringer', 'ux-select'); ?></button>
            </div>
            <div class="uxs-toast" id="uxs-toast" role="status"><?php echo esc_html__('Endringene er lagret', 'ux-select'); ?></div>
        </div>
        <?php
    }
}
