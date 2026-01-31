<?php
/**
 * Plugin Name: Language Switcher Dropdown
 * Description: Dropdown language switcher with flags and links, configurable from the admin panel.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: language-switcher-dropdown
 */

if (!defined('ABSPATH')) {
    exit;
}

final class LSLS_Language_Switcher_Dropdown {
    private const OPTION_KEY = 'lsls_options';

    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'register_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_frontend_assets']);
    }

    public static function default_options(): array {
        return [
            'languages' => [
                [
                    'code' => 'EN',
                    'name' => 'English',
                    'flag' => 'https://honeybetzonline.com/wp-content/uploads/2025/12/en.webp',
                    'url'  => '/',
                ],
                [
                    'code' => 'ES',
                    'name' => 'Español',
                    'flag' => 'https://honeybetzonline.com/wp-content/uploads/2025/12/es.webp',
                    'url'  => '/es/',
                ],
                [
                    'code' => 'DE',
                    'name' => 'German',
                    'flag' => 'https://honeybetzonline.com/wp-content/uploads/2025/12/de.webp',
                    'url'  => '/de/',
                ],
            ],
            'desktop_selector' => '.saintsmedia-theme-cta.menu-nav-buttons--desktop',
            'fallback_to_body' => true,
            'mobile_fixed'     => true,
            'mobile_position'  => 'bottom-left',
            'mobile_offset'    => 16,
            'breakpoint'       => 768,
            'enable_hover'     => true,
        ];
    }

    public static function get_options(): array {
        $defaults = self::default_options();
        $options = get_option(self::OPTION_KEY, []);
        if (!is_array($options)) {
            $options = [];
        }
        return array_replace_recursive($defaults, $options);
    }

    public static function register_admin_menu(): void {
        add_menu_page(
            __('Переключатель языков', 'language-switcher-dropdown'),
            __('Переключатель языков', 'language-switcher-dropdown'),
            'manage_options',
            'lsls-language-switcher',
            [__CLASS__, 'render_settings_page'],
            'dashicons-translation'
        );
    }

    public static function register_settings(): void {
        register_setting('lsls_settings_group', self::OPTION_KEY, [
            'type'              => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_options'],
            'default'           => self::default_options(),
        ]);
    }

    public static function enqueue_admin_assets(string $hook): void {
        if ($hook !== 'toplevel_page_lsls-language-switcher') {
            return;
        }

        wp_enqueue_style(
            'lsls-admin',
            plugins_url('assets/admin.css', __FILE__),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'lsls-admin',
            plugins_url('assets/admin.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('lsls-admin', 'lslsAdmin', [
            'removeLabel' => __('Удалить', 'language-switcher-dropdown'),
        ]);
    }

    public static function enqueue_frontend_assets(): void {
        $options = self::get_options();
        if (empty($options['languages']) || !is_array($options['languages'])) {
            return;
        }

        wp_enqueue_style(
            'lsls-frontend',
            plugins_url('assets/lsls.css', __FILE__),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'lsls-frontend',
            plugins_url('assets/lsls.js', __FILE__),
            [],
            '1.0.0',
            true
        );

        $config = [
            'languages' => array_values($options['languages']),
            'desktopSelector' => (string) ($options['desktop_selector'] ?? ''),
            'fallbackToBody'  => !empty($options['fallback_to_body']),
            'mobileFixed'     => !empty($options['mobile_fixed']),
            'mobilePosition'  => (string) ($options['mobile_position'] ?? 'bottom-left'),
            'mobileOffset'    => (int) ($options['mobile_offset'] ?? 16),
            'breakpoint'      => (int) ($options['breakpoint'] ?? 768),
            'enableHover'     => !empty($options['enable_hover']),
        ];

        wp_add_inline_script(
            'lsls-frontend',
            'window.LSLS_CONFIG = ' . wp_json_encode($config) . ';',
            'before'
        );
    }

    public static function sanitize_options($input): array {
        $defaults = self::default_options();
        $output = $defaults;

        $languages = [];
        if (isset($input['languages']) && is_array($input['languages'])) {
            foreach ($input['languages'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $code = sanitize_text_field($row['code'] ?? '');
                $name = sanitize_text_field($row['name'] ?? '');
                $flag = self::sanitize_url_field($row['flag'] ?? '');
                $url  = self::sanitize_url_field($row['url'] ?? '');

                if ($code === '' && $name === '' && $flag === '' && $url === '') {
                    continue;
                }

                $languages[] = [
                    'code' => $code,
                    'name' => $name,
                    'flag' => $flag,
                    'url'  => $url,
                ];
            }
        }

        if (!empty($languages)) {
            $output['languages'] = $languages;
        } else {
            $output['languages'] = [];
        }

        $output['desktop_selector'] = sanitize_text_field($input['desktop_selector'] ?? $defaults['desktop_selector']);
        $output['fallback_to_body'] = !empty($input['fallback_to_body']);
        $output['mobile_fixed'] = !empty($input['mobile_fixed']);
        $output['enable_hover'] = !empty($input['enable_hover']);

        $position = sanitize_text_field($input['mobile_position'] ?? $defaults['mobile_position']);
        if (!in_array($position, ['top-left', 'top-right', 'bottom-left', 'bottom-right'], true)) {
            $position = $defaults['mobile_position'];
        }
        $output['mobile_position'] = $position;

        $offset = isset($input['mobile_offset']) ? (int) $input['mobile_offset'] : (int) $defaults['mobile_offset'];
        $output['mobile_offset'] = max(0, min(200, $offset));

        $breakpoint = isset($input['breakpoint']) ? (int) $input['breakpoint'] : (int) $defaults['breakpoint'];
        $output['breakpoint'] = max(320, min(1920, $breakpoint));

        return $output;
    }

    public static function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $options = self::get_options();
        $languages = $options['languages'] ?? [];
        if (!is_array($languages)) {
            $languages = [];
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Переключатель языков', 'language-switcher-dropdown'); ?></h1>
            <form method="post" action="options.php" class="lsls-settings-form">
                <?php settings_fields('lsls_settings_group'); ?>

                <h2><?php echo esc_html__('Языки', 'language-switcher-dropdown'); ?></h2>
                <p class="description">
                    <?php echo esc_html__('Добавьте языки с кодом, названием, ссылкой на флаг и ссылкой на страницу. Пустые строки будут игнорироваться.', 'language-switcher-dropdown'); ?>
                </p>

                <table class="widefat lsls-table" id="lsls-languages-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Код', 'language-switcher-dropdown'); ?></th>
                            <th><?php echo esc_html__('Название', 'language-switcher-dropdown'); ?></th>
                            <th><?php echo esc_html__('Ссылка на флаг', 'language-switcher-dropdown'); ?></th>
                            <th><?php echo esc_html__('Ссылка на страницу', 'language-switcher-dropdown'); ?></th>
                            <th><?php echo esc_html__('Действие', 'language-switcher-dropdown'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($languages)) : ?>
                            <?php $languages = [['code' => '', 'name' => '', 'flag' => '', 'url' => '']]; ?>
                        <?php endif; ?>

                        <?php foreach ($languages as $index => $lang) : ?>
                            <tr>
                                <td>
                                    <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[languages][<?php echo esc_attr($index); ?>][code]" value="<?php echo esc_attr($lang['code'] ?? ''); ?>" class="regular-text" />
                                </td>
                                <td>
                                    <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[languages][<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($lang['name'] ?? ''); ?>" class="regular-text" />
                                </td>
                                <td>
                                    <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[languages][<?php echo esc_attr($index); ?>][flag]" value="<?php echo esc_attr($lang['flag'] ?? ''); ?>" class="regular-text" />
                                </td>
                                <td>
                                    <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[languages][<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_attr($lang['url'] ?? ''); ?>" class="regular-text" />
                                </td>
                                <td>
                                    <button type="button" class="button lsls-remove-row"><?php echo esc_html__('Удалить', 'language-switcher-dropdown'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                    <button type="button" class="button button-secondary" id="lsls-add-row"><?php echo esc_html__('Добавить язык', 'language-switcher-dropdown'); ?></button>
                </p>

                <h2><?php echo esc_html__('Размещение', 'language-switcher-dropdown'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="lsls-desktop-selector"><?php echo esc_html__('Селектор контейнера (десктоп)', 'language-switcher-dropdown'); ?></label></th>
                        <td>
                            <input type="text" id="lsls-desktop-selector" name="<?php echo esc_attr(self::OPTION_KEY); ?>[desktop_selector]" value="<?php echo esc_attr($options['desktop_selector'] ?? ''); ?>" class="regular-text" />
                            <p class="description"><?php echo esc_html__('CSS‑селектор контейнера для десктопа.', 'language-switcher-dropdown'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lsls-mobile-position"><?php echo esc_html__('Позиция на мобайле', 'language-switcher-dropdown'); ?></label></th>
                        <td>
                            <select id="lsls-mobile-position" name="<?php echo esc_attr(self::OPTION_KEY); ?>[mobile_position]" class="regular-text">
                                <option value="top-left" <?php selected($options['mobile_position'] ?? '', 'top-left'); ?>><?php echo esc_html__('Верхний левый угол', 'language-switcher-dropdown'); ?></option>
                                <option value="top-right" <?php selected($options['mobile_position'] ?? '', 'top-right'); ?>><?php echo esc_html__('Верхний правый угол', 'language-switcher-dropdown'); ?></option>
                                <option value="bottom-left" <?php selected($options['mobile_position'] ?? '', 'bottom-left'); ?>><?php echo esc_html__('Нижний левый угол', 'language-switcher-dropdown'); ?></option>
                                <option value="bottom-right" <?php selected($options['mobile_position'] ?? '', 'bottom-right'); ?>><?php echo esc_html__('Нижний правый угол', 'language-switcher-dropdown'); ?></option>
                            </select>
                            <p class="description"><?php echo esc_html__('Выберите угол экрана для размещения переключателя.', 'language-switcher-dropdown'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lsls-mobile-offset"><?php echo esc_html__('Отступ от края (px)', 'language-switcher-dropdown'); ?></label></th>
                        <td>
                            <input type="number" id="lsls-mobile-offset" name="<?php echo esc_attr(self::OPTION_KEY); ?>[mobile_offset]" value="<?php echo esc_attr((string) ($options['mobile_offset'] ?? 16)); ?>" class="small-text" min="0" max="200" />
                            <p class="description"><?php echo esc_html__('Расстояние от выбранного угла в пикселях.', 'language-switcher-dropdown'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lsls-breakpoint"><?php echo esc_html__('Мобильный брейкпоинт', 'language-switcher-dropdown'); ?></label></th>
                        <td>
                            <input type="number" id="lsls-breakpoint" name="<?php echo esc_attr(self::OPTION_KEY); ?>[breakpoint]" value="<?php echo esc_attr((string) ($options['breakpoint'] ?? 768)); ?>" class="small-text" min="320" max="1920" />
                            <p class="description"><?php echo esc_html__('Ширина экрана в пикселях для мобильного режима.', 'language-switcher-dropdown'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Фолбэк в body на мобайле', 'language-switcher-dropdown'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[fallback_to_body]" value="1" <?php checked(!empty($options['fallback_to_body'])); ?> />
                                <?php echo esc_html__('Если мобильный контейнер не найден — вставить переключатель в body.', 'language-switcher-dropdown'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Фиксировать на мобайле', 'language-switcher-dropdown'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[mobile_fixed]" value="1" <?php checked(!empty($options['mobile_fixed'])); ?> />
                                <?php echo esc_html__('Закрепить переключатель внизу слева на мобильных.', 'language-switcher-dropdown'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Открывать по наведению', 'language-switcher-dropdown'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[enable_hover]" value="1" <?php checked(!empty($options['enable_hover'])); ?> />
                                <?php echo esc_html__('Открывать меню при наведении на десктопе.', 'language-switcher-dropdown'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private static function sanitize_url_field($value): string {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '/') || str_starts_with($value, '#') || str_starts_with($value, '?')) {
            return sanitize_text_field($value);
        }

        return esc_url_raw($value);
    }
}

LSLS_Language_Switcher_Dropdown::init();
