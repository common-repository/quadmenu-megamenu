<?php
/*
 * Plugin Name: QuadMenu Importer for Max Mega Menu
 * Plugin URI:  https://quadmenu.com
 * Description: Import Max Mega Menu items and themes.
 * Version:     1.0.2
 * Author:      QuadMenu
 * Author URI:  https://quadmenu.com
 *  License: GPLv3
 * Text Domain: quadmenu
 */

if (!defined('ABSPATH')) {
    die('-1');
}

class QuadMenu_MegaMenu {

    public $settings = array();
    public $duplicated = array();

    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue'), 5);
        add_action('admin_menu', array($this, 'panel'), 40);
        add_action('admin_notices', array($this, 'required'), 10);
        add_action('wp_ajax_quadmenu_import_megamenu', array($this, 'import'));
        add_action('duplicate_menu_item', array($this, 'megamenu_item_settings'), 5, 4);
        add_action('duplicate_menu_item', array($this, 'megamenu_item_standard_layout'), 20, 4);
        add_action('duplicate_menu_item', array($this, 'megamenu_item_grid_layout'), 20, 4);
    }

    function enqueue() {
        wp_enqueue_script('quadmenu-generatepress', plugin_dir_url(__FILE__) . 'assets/quadmenu-megamenu.js', array('jquery'), '1.0.0', 'all');
    }

    function panel() {

        if (class_exists('QuadMenu')) {
            add_submenu_page('quadmenu_welcome', 'Max Mega Menu', 'Max Mega Menu', 'edit_posts', 'quadmenu_max_mega_menu', array($this, 'page'));
        }
    }

    function page() {
        ?>
        <div class="about-wrap quadmenu-admin-wrap theme-browser">
            <h1><?php esc_html_e('Max Mega Menu', 'quadmenu'); ?></h1>
            <div class="about-text">
                <?php esc_html_e('QuadMenu gives you the ability to import Max Mega Menu content in one step. Activate Max Mega Menu and press the Import button.', 'quadmenu'); ?>
            </div>
            <hr/>
            <div class="quadmenu-admin-columns">
                <div class="theme">
                    <div class="theme-screenshot">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/megamenu.jpg'); ?>" alt="<?php esc_html_e('Max Mega Menu', 'quadmenu'); ?>">
                    </div>
                    <div class="theme-id-container">
                        <h2 class="theme-name" id="eduka-name"><?php esc_html_e('Max Mega Menu', 'quadmenu'); ?></h2>
                        <div class="theme-actions">          
                            <span class="spinner left"></span>
                            <a class="button button-primary max_mega_menu_import" href="#"><?php esc_html_e('Import', 'quadmenu'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    function required() {

        $screen = get_current_screen();

        if (isset($screen->parent_file) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id) {
            return;
        }

        $plugin = 'quadmenu/quadmenu.php';

        if (is_plugin_active($plugin)) {
            return;
        }

        if (function_exists('is_quadmenu_installed') && is_quadmenu_installed()) {

            if (!current_user_can('activate_plugins')) {
                return;
            }
            ?>
            <div class="error">
                <p>
                    <a href="<?php echo wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1', 'activate-plugin_' . $plugin); ?>" class='button button-secondary'><?php _e('Activate QuadMenu', 'quadmenu'); ?></a>
                    <?php esc_html_e('Please activate the QuadMenu plugin to import your Max Mega Menu.', 'quadmenu'); ?>   
                </p>
            </div>
            <?php
        } else {

            if (!current_user_can('install_plugins')) {
                return;
            }
            ?>
            <div class="error">
                <p>
                    <a href="<?php echo wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=quadmenu'), 'install-plugin_quadmenu'); ?>" class='button button-secondary'><?php _e('Install QuadMenu', 'quadmenu'); ?></a>
                    <?php esc_html_e('Please install the QuadMenu plugin to import your Max Mega Menu.', 'quadmenu'); ?>
                </p>
            </div>
            <?php
        }
    }

    function import() {

        if (!check_ajax_referer('quadmenu', 'nonce', false)) {
            QuadMenu::send_json_error(esc_html__('Please reload page.', 'quadmenu'));
        }

        if (!class_exists('Mega_Menu')) {
            QuadMenu::send_json_error(esc_html__('Please activate Max Mega Menu.', 'quadmenu'));
        }

        $this->add_themes();
        $this->add_themes_locations();
        $this->add_menus();
        $this->add_menus_locations();
        QuadMenu_Redux::add_notification('blue', sprintf(esc_html__('Max Mega Menu have been imported! %s. %s', 'quadmenu'), esc_html__('We have to create the stylesheets', 'quadmenu'), esc_html__('Please wait', 'quadmenu')));
        Quadmenu_Compiler::do_compiler(true);
        QuadMenu::send_json_success(QuadMenu::taburl('0'));
    }

    function add_themes() {

        global $quadmenu, $quadmenu_themes;

        if (!class_exists('Mega_Menu_Style_Manager')) {
            QuadMenu::send_json_error(esc_html__('Please activate Max Mega Menu.', 'quadmenu'));
        }

        $megamenu = new Mega_Menu_Style_Manager();

        $weight = array(
            'inherit' => 400,
            'light' => 300,
            'normal' => 400,
            'bold' => 700
        );

        if (is_array($megamenu_themes = $megamenu->get_themes()) && count($megamenu_themes)) {

            foreach ($megamenu_themes as $key => $theme_settings) {

                $quadmenu_themes[$key] = 'MegaMenu ' . $theme_settings['title'];

                $quadmenu[$key . '_layout'] = 'embed';
                $quadmenu[$key . '_layout_offcanvas_float'] = $theme_settings['menu_item_align'];
                $quadmenu[$key . '_layout_align'] = $theme_settings['menu_item_align'];
                $quadmenu[$key . '_layout_sticky'] = 0;
                $quadmenu[$key . '_layout_sticky_offset'] = '90';
                $quadmenu[$key . '_layout_divider'] = $theme_settings['menu_item_divider'] == 'on' ? 'show' : 'hide';
                $quadmenu[$key . '_layout_caret'] = 'show';
                $quadmenu[$key . '_layout_trigger'] = 'hoverintent';
                $quadmenu[$key . '_layout_current'] = 0;
                $quadmenu[$key . '_layout_classes'] = '';
                $quadmenu[$key . '_layout_breakpoint'] = '768';
                $quadmenu[$key . '_layout_width_inner_selector'] = '';
                $quadmenu[$key . '_layout_hover_effect'] = '';
                $quadmenu[$key . '_navbar_font'] = $quadmenu[$key . '_font'] = array(
                    'font-family' => $theme_settings['menu_item_link_font'],
                    'font-size' => (int) str_replace('px', '', $theme_settings['menu_item_link_font_size']),
                    'font-weight' => $weight[$theme_settings['menu_item_link_weight']],
                    'font-style' => '',
                    'letter-spacing' => '',
                );
                $quadmenu[$key . '_dropdown_font'] = array(
                    'font-family' => $theme_settings['flyout_link_family'],
                    'font-size' => (int) str_replace('px', '', $theme_settings['flyout_link_size']),
                    'font-weight' => $weight[$theme_settings['flyout_link_weight']],
                    'font-style' => '',
                    'letter-spacing' => '',
                );

                $quadmenu[$key . '_navbar_height'] = (int) str_replace('px', '', $theme_settings['menu_item_link_height']);
                $quadmenu[$key . '_navbar_background'] = ($theme_settings['container_background_from'] === $theme_settings['container_background_to']) ? 'color' : 'gradient';
                $quadmenu[$key . '_navbar_background_color'] = $theme_settings['container_background_from'];
                $quadmenu[$key . '_navbar_background_to'] = $theme_settings['container_background_to'];
                $quadmenu[$key . '_navbar_link'] = $theme_settings['menu_item_link_color'];
                $quadmenu[$key . '_navbar_link_hover'] = $theme_settings['menu_item_link_color_hover'];
                $quadmenu[$key . '_navbar_link_bg'] = $theme_settings['menu_item_background_from'];
                $quadmenu[$key . '_navbar_link_bg_hover'] = $theme_settings['menu_item_background_hover_from'];
                //$quadmenu[$key . '_navbar_link_hover_effect'] = array('color' => '#fb88dd', 'alpha' => '1');
                $quadmenu[$key . '_navbar_link_margin'] = array(
                    'border-top' => '0',
                    'border-right' => (int) $theme_settings['menu_item_spacing'] / 2,
                    'border-left' => (int) $theme_settings['menu_item_spacing'] / 2,
                    'border-bottom' => '0'
                );
                $quadmenu[$key . '_navbar_link_radius'] = array(
                    'border-top' => $theme_settings['container_border_radius_top_left'],
                    'border-bottom' => $theme_settings['container_border_radius_top_right'],
                    'border-left' => $theme_settings['container_border_radius_bottom_right'],
                    'border-right' => $theme_settings['container_border_radius_bottom_left'],
                );
                $quadmenu[$key . '_navbar_link_transform'] = $theme_settings['menu_item_link_text_transform'];

                $quadmenu[$key . '_dropdown_margin'] = 0;
                $quadmenu[$key . 'dropdown_shadow'] = 'hide';
                $quadmenu[$key . '_dropdown_radius'] = array(
                    'border-top' => $theme_settings['flyout_border_radius_top_left'],
                    'border-right' => $theme_settings['flyout_border_radius_top_right'],
                    'border-left' => $theme_settings['flyout_border_radius_bottom_left'],
                    'border-bottom' => $theme_settings['flyout_border_radius_bottom_right'],
                );
                $quadmenu[$key . '_dropdown_border'] = array(
                    'border-all' => $theme_settings['flyout_border_top'],
                    'border-top' => $theme_settings['flyout_border_top'],
                    'border-left' => $theme_settings['flyout_border_top'],
                    'border-right' => $theme_settings['flyout_border_top'],
                    'border-bottom' => $theme_settings['flyout_border_top'],
                    'border-color' => $theme_settings['flyout_border_color'],
                );
                $quadmenu[$key . '_dropdown_background'] = $theme_settings['flyout_menu_background_from'];
                //$quadmenu[$key . '_dropdown_scrollbar'] = '#fb88dd';
                //$quadmenu[$key . '_dropdown_scrollbar_rail'] = '#ffffff';
                $quadmenu[$key . '_dropdown_title'] = $theme_settings['panel_header_color'];
                $quadmenu[$key . '_dropdown_title_border'] = array(
                    'border-all' => $theme_settings['panel_header_border_bottom'],
                    'border-top' => $theme_settings['panel_header_border_bottom'],
                    'border-color' => $theme_settings['panel_header_border_color'],
                    'border-style' => 'solid'
                );
                $quadmenu[$key . '_dropdown_link'] = $theme_settings['flyout_link_color'];
                $quadmenu[$key . '_dropdown_link_hover'] = $theme_settings['flyout_link_color_hover'];
                $quadmenu[$key . '_dropdown_link_bg_hover'] = $theme_settings['flyout_background_hover_from'];
                $quadmenu[$key . '_dropdown_link_border'] = array(
                    'border-all' => $theme_settings['flyout_menu_item_divider'] ? 1 : 0,
                    'border-top' => $theme_settings['flyout_menu_item_divider'] ? 1 : 0,
                    'border-color' => $theme_settings['flyout_menu_item_divider_color'],
                    'border-style' => 'solid'
                );
                $quadmenu[$key . '_dropdown_link_transform'] = $theme_settings['flyout_link_text_transform'];
                //$quadmenu[$key . '_dropdown_button'] = '#ffffff';
                //$quadmenu[$key . '_dropdown_button_bg'] = '#fb88dd';
                //$quadmenu[$key . '_dropdown_button_hover'] = '#ffffff';
                //$quadmenu[$key . '_dropdown_button_bg_hover'] = '#000000';
                $quadmenu[$key . '_dropdown_link_icon'] = $theme_settings['panel_second_level_font_color'];
                $quadmenu[$key . '_dropdown_link_icon_hover'] = $theme_settings['panel_second_level_font_color_hover'];
                $quadmenu[$key . '_dropdown_link_subtitle'] = $theme_settings['panel_second_level_font_color'];
                $quadmenu[$key . '_dropdown_link_subtitle_hover'] = $theme_settings['panel_second_level_font_color_hover'];
            }

            update_option(QUADMENU_THEMES, $quadmenu_themes);

            update_option(QUADMENU_OPTIONS, $quadmenu);
        }
    }

    function add_themes_locations() {

        if (is_array($megamenu_settings = get_option('megamenu_settings')) && count($megamenu_settings)) {

            if (is_array($quadmenu = get_option(QUADMENU_OPTIONS)) && count($quadmenu)) {

                foreach (get_nav_menu_locations() as $key => $menu_id) {

                    if (isset($megamenu_settings[$key]['theme'])) {

                        $quadmenu[$key . '_integration'] = 1;

                        $quadmenu[$key . '_theme'] = $megamenu_settings[$key]['theme'];
                    }
                }

                update_option(QUADMENU_OPTIONS, $quadmenu);
            }
        }
    }

    function add_menus() {

        $add_menus = array();

        if (is_array($megamenu_settings = get_option('megamenu_settings')) && count($megamenu_settings)) {
            foreach (get_nav_menu_locations() as $id => $menu_id) {

                if (!is_nav_menu($menu_id))
                    continue;

                if (empty($megamenu_settings[$id]))
                    continue;

                $menu_obj = get_term($menu_id, 'nav_menu');

                if (strpos($menu_obj->name, '[QuadMenu]') !== false)
                    continue;

                $add_menus[$menu_id] = '[QuadMenu] ' . $menu_obj->name;
            }
        }

        if (count($add_menus)) {
            foreach ($add_menus as $menu_id => $name) {
                $this->menu($menu_id, $name);
            }
        }
    }

    function menu($id = null, $name = null) {

        if (empty($id) || empty($name)) {
            return false;
        }

        $id = intval($id);

        $name = sanitize_text_field($name);

        $source = wp_get_nav_menu_object($id);

        $source_items = wp_get_nav_menu_items($id);

        if (!$new_id = get_term_by('name', $name, 'nav_menu')->term_id) {
            $new_id = wp_create_nav_menu($name);
        }

        if (!$new_id) {
            return false;
        }

// key is the original db ID, val is the new
        $rel = array();

        $i = 1;

        foreach ($source_items as $menu_item) {
            $args = array(
                'menu-item-db-id' => $menu_item->db_id,
                'menu-item-object-id' => $menu_item->object_id,
                'menu-item-object' => $menu_item->object,
                'menu-item-position' => $i,
                'menu-item-type' => $menu_item->type,
                'menu-item-title' => $menu_item->title,
                'menu-item-url' => $menu_item->url,
                'menu-item-description' => $menu_item->description,
                'menu-item-attr-title' => $menu_item->attr_title,
                'menu-item-target' => $menu_item->target,
                'menu-item-classes' => implode(' ', $menu_item->classes),
                'menu-item-xfn' => $menu_item->xfn,
                'menu-item-status' => $menu_item->post_status
            );
            $parent_id = wp_update_nav_menu_item($new_id, 0, $args);
            $rel[$menu_item->db_id] = $parent_id;
            // did it have a parent? if so, we need to update with the NEW ID
            if ($menu_item->menu_item_parent) {
                $args['menu-item-parent-id'] = $rel[$menu_item->menu_item_parent];
                $parent_id = wp_update_nav_menu_item($new_id, $parent_id, $args);
            }
            // allow developers to run any custom functionality they'd like
            do_action('duplicate_menu_item', $menu_id, $new_id, $menu_item, $parent_id);
            $i++;
        }

        $this->duplicated[$id] = $new_id;

        return $new_id;
    }

    function add_menus_locations() {

        if (is_array($locations = get_theme_mod('nav_menu_locations')) && count($locations)) {

            foreach ($locations as $key => $menu_id) {

                if (isset($this->duplicated[$menu_id])) {
                    $locations[$key] = $this->duplicated[$menu_id];
                }
            }

            set_theme_mod('nav_menu_locations', $locations);
        }
    }

    function megamenu_item_settings($menu_id, $new_id, $item, $new_item_id) {

        $this->settings[$new_item_id]['hidden'] = array();

        $megamenu = get_post_meta($item->ID, '_megamenu', true);

        if (!empty($megamenu['type'])) {
            if (in_array(sanitize_key($megamenu['type']), array('megamenu', 'grid'))) {
                $this->settings[$new_item_id]['quadmenu'] = 'mega';
            }
        }

        if (!empty($megamenu['icon'])) {

            if (strpos($megamenu['icon'], 'dashicons-') !== false) {
                $this->settings[$new_item_id]['icon'] = 'dashicons ';
            }

            $this->settings[$new_item_id]['icon'] .= $megamenu['icon'];
        }

        if (!empty($megamenu['item_align'])) {
            $this->settings[$new_item_id]['float'] = $megamenu['item_align'];
        }

        if (!empty($megamenu['align'])) {
            $this->settings[$new_item_id]['dropdown'] = $megamenu['align'] == 'bottom-right' ? 'left' : 'right';
        }

        if (isset($megamenu['hide_on_mobile']) && $megamenu['hide_on_mobile'] === true) {
            $this->settings[$new_item_id]['hidden'][] = 'hidden-xs';
            $this->settings[$new_item_id]['hidden'][] = 'hidden-sm';
        }

        if (isset($megamenu['hide_on_desktop']) && $megamenu['hide_on_desktop'] === true) {
            $this->settings[$new_item_id]['hidden'][] = 'hidden-md';
            $this->settings[$new_item_id]['hidden'][] = 'hidden-lg';
        }

        if (!$new_item_id)
            return false;

        if (get_post_meta($new_item_id, QUADMENU_DB_KEY, true))
            return false;

        update_post_meta($new_item_id, QUADMENU_DB_KEY, $this->settings[$new_item_id]);
    }

    function megamenu_item_standard_layout($menu_id, $new_id, $item, $new_item_id) {

        //if (!class_exists('Mega_Menu_Widget_Manager')) {
        //    QuadMenu::send_json_error(esc_html__('Please activate Max Mega Menu.', 'quadmenu'));
        //}

        $megamenu = new Mega_Menu_Widget_Manager();

        $megamenu_settings = array_filter((array) get_post_meta($item->ID, '_megamenu', true));

        if (isset($megamenu_settings['type']) && $megamenu_settings['type'] == 'megamenu') {

            $widgets = $megamenu->get_widgets_for_menu_id($item->ID, $menu_id);

            if (!is_array($widgets))
                return false;

            if (!count($widgets))
                return false;

            foreach ($widgets as $id => $widget) {

                $column_args = array(
                    'menu-item-status' => 'publish',
                    'menu-item-type' => 'custom',
                    'menu-item-title' => 'Column',
                    'menu-item-url' => '#column',
                    'menu-item-parent-id' => $new_item_id,
                    'menu-item-quadmenu' => 'column',
                );

                $column_item_id = wp_update_nav_menu_item($new_id, 0, $column_args);

                $settings = array(
                    'quadmenu' => 'column',
                    'columns' => array('col-sm-6', 'col-md-' . (int) 12 / (6 / $widget['columns']))
                );

                update_post_meta($column_item_id, QUADMENU_DB_KEY, $settings);

                $this->add_widget($column_item_id, $new_id, $widget, $megamenu);
            }
        }
    }

    function megamenu_item_grid_layout($menu_id, $new_id, $item, $new_item_id) {

        //if (!class_exists('Mega_Menu_Widget_Manager')) {
        //    QuadMenu::send_json_error(esc_html__('Please activate Max Mega Menu.', 'quadmenu'));
        //}

        $megamenu = new Mega_Menu_Widget_Manager();

        $megamenu_settings = array_filter((array) get_post_meta($item->ID, '_megamenu', true));

        if (isset($megamenu_settings['type']) && $megamenu_settings['type'] == 'grid') {

            $grid = $megamenu->get_grid_widgets_and_menu_items_for_menu_id($item->ID, $menu_id);

            if (!is_array($grid))
                return false;

            if (!count($grid))
                return false;

            foreach ($grid as $row => $row_data) {

                if (isset($row_data['columns']) && is_array($row_data['columns']) && count($row_data['columns'])) {

                    foreach ($row_data['columns'] as $col => $col_data) {

                        $column_args = array(
                            'menu-item-status' => 'publish',
                            'menu-item-type' => 'custom',
                            'menu-item-title' => 'Column',
                            'menu-item-url' => '#column',
                            'menu-item-parent-id' => $new_item_id,
                            'menu-item-quadmenu' => 'column',
                        );

                        $column_item_id = wp_update_nav_menu_item($new_id, 0, $column_args);

                        $settings = array(
                            'quadmenu' => 'column',
                            'columns' => array('col-sm-6', 'col-md-' . (int) $col_data['meta']['span']),
                                //'hidden' => array()
                        );

                        if (isset($col_data['meta']['hide-on-mobile']) && $col_data['meta']['hide-on-mobile'] === 'true') {
                            $settings['columns'][] = 'hidden-xs';
                            $settings['columns'][] = 'hidden-sm';
                        }

                        if (isset($col_data['meta']['hide-on-desktop']) && $col_data['meta']['hide-on-desktop'] === 'true') {
                            $settings['columns'][] = 'hidden-md';
                            $settings['columns'][] = 'hidden-lg';
                        }

                        update_post_meta($column_item_id, QUADMENU_DB_KEY, $settings);

                        if (isset($col_data['items']) && is_array($col_data['items']) && count($col_data['items'])) {

                            foreach ($col_data['items'] as $widget) {
                                $this->add_widget($column_item_id, $new_id, $widget, $megamenu);
                            }
                        }
                    }
                }
            }
        }
    }

    function add_widget($column_item_id, $new_id, $widget, $megamenu) {

        $widget_args = array(
            'menu-item-status' => 'publish',
            'menu-item-type' => 'custom',
            'menu-item-title' => 'Widget',
            'menu-item-url' => '#widget',
            'menu-item-parent-id' => $column_item_id,
            'menu-item-quadmenu' => 'widget',
        );

        $settings = array(
            'quadmenu' => 'widget',
            'widget' => $megamenu->get_id_base_for_widget_id($widget['id']),
            //'widget_number' => $widget['order'],
            'widget_id' => $widget['id']
        );

        $widget_item_id = wp_update_nav_menu_item($new_id, 0, $widget_args);

        update_post_meta($widget_item_id, QUADMENU_DB_KEY, $settings);
    }

}

new QuadMenu_MegaMenu();
