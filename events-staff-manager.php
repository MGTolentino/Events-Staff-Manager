<?php
/**
 * Plugin Name: Events Staff Manager
 * Description: Gestiona las funcionalidades de los ejecutivos de ventas para filtrar leads por ciudad y categoría
 * Version: 1.0.0
 * Author: Tu Nombre
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ESM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ESM_PLUGIN_PATH', plugin_dir_path(__FILE__));

class Events_Staff_Manager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_ajax_esm_save_user_restrictions', array($this, 'save_user_restrictions'));
        add_filter('posts_where', array($this, 'filter_leads_query'), 10, 2);
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Events Staff Manager',
            'Events Staff Manager',
            'manage_options',
            'events-staff-manager',
            array($this, 'admin_page'),
            'dashicons-groups',
            30
        );
    }
    
    public function admin_scripts($hook) {
        if ($hook !== 'toplevel_page_events-staff-manager') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'));
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('esm-admin', ESM_PLUGIN_URL . 'assets/admin.js', array('jquery', 'select2'), '1.0.0', true);
        wp_enqueue_style('esm-admin', ESM_PLUGIN_URL . 'assets/admin.css', array(), '1.0.0');
        
        wp_localize_script('esm-admin', 'esm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('esm_nonce')
        ));
    }
    
    public function frontend_scripts() {
        if (is_page() && has_shortcode(get_post()->post_content, 'ltb_leads_table')) {
            wp_enqueue_script('esm-frontend', ESM_PLUGIN_URL . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        }
    }
    
    public function admin_page() {
        include ESM_PLUGIN_PATH . 'includes/admin-page.php';
    }
    
    public function save_user_restrictions() {
        check_ajax_referer('esm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id']);
        $cities = isset($_POST['cities']) ? array_map('sanitize_text_field', $_POST['cities']) : array();
        $categories = isset($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : array();
        
        update_user_meta($user_id, 'esm_allowed_cities', $cities);
        update_user_meta($user_id, 'esm_allowed_categories', $categories);
        
        wp_send_json_success('Restricciones guardadas correctamente');
    }
    
    public function filter_leads_query($where, $query) {
        global $wpdb;
        
        if (!$this->should_filter_query($query)) {
            return $where;
        }
        
        $current_user = wp_get_current_user();
        
        if (!in_array('ejecutivo_de_ventas', $current_user->roles)) {
            return $where;
        }
        
        $allowed_cities = get_user_meta($current_user->ID, 'esm_allowed_cities', true);
        $allowed_categories = get_user_meta($current_user->ID, 'esm_allowed_categories', true);
        
        if (empty($allowed_cities) && empty($allowed_categories)) {
            return $where;
        }
        
        $additional_where = array();
        
        if (!empty($allowed_cities)) {
            $cities_placeholders = implode(',', array_fill(0, count($allowed_cities), '%s'));
            $additional_where[] = $wpdb->prepare(
                "EXISTS (SELECT 1 FROM {$wpdb->prefix}jet_cct_eventos e WHERE e.lead_id = {$wpdb->prefix}jet_cct_leads._ID AND e.ubicacion_evento IN ($cities_placeholders))",
                $allowed_cities
            );
        }
        
        if (!empty($allowed_categories)) {
            $categories_placeholders = implode(',', array_fill(0, count($allowed_categories), '%s'));
            $additional_where[] = $wpdb->prepare(
                "EXISTS (SELECT 1 FROM {$wpdb->prefix}jet_cct_eventos e WHERE e.lead_id = {$wpdb->prefix}jet_cct_leads._ID AND e.categoria_listing_post IN ($categories_placeholders))",
                $allowed_categories
            );
        }
        
        if (!empty($additional_where)) {
            $where .= ' AND (' . implode(' OR ', $additional_where) . ')';
        }
        
        return $where;
    }
    
    private function should_filter_query($query) {
        global $wpdb;
        
        if (is_admin()) {
            return false;
        }
        
        if (!is_main_query()) {
            return false;
        }
        
        if (strpos($wpdb->last_query, 'jet_cct_leads') === false) {
            return false;
        }
        
        return true;
    }
    
    public function get_sales_executives() {
        $users = get_users(array(
            'role' => 'ejecutivo_de_ventas',
            'orderby' => 'display_name'
        ));
        
        return $users;
    }
    
    public function get_available_cities() {
        global $wpdb;
        
        $cities = $wpdb->get_col("
            SELECT DISTINCT ubicacion_evento 
            FROM {$wpdb->prefix}jet_cct_eventos 
            WHERE ubicacion_evento IS NOT NULL 
            AND ubicacion_evento != '' 
            ORDER BY ubicacion_evento
        ");
        
        return array_filter($cities);
    }
    
    public function get_available_categories() {
        global $wpdb;
        
        $categories = $wpdb->get_col("
            SELECT DISTINCT categoria_listing_post 
            FROM {$wpdb->prefix}jet_cct_eventos 
            WHERE categoria_listing_post IS NOT NULL 
            AND categoria_listing_post != '' 
            ORDER BY categoria_listing_post
        ");
        
        return array_filter($categories);
    }
}

new Events_Staff_Manager();