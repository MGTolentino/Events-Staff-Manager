<?php
/**
 * Plugin Name: Events Staff Manager
 * Description: Gestiona las funcionalidades de los ejecutivos de ventas para filtrar leads por ciudad y categoría
 * Version: 1.0.0
 * Author: Miguel Tolentino
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
        add_filter('ltb_leads_query_where', array($this, 'filter_leads_query'), 10, 2);
        add_filter('ltb_leads_query_args', array($this, 'add_user_restrictions_to_args'), 10, 1);
        add_action('wp_ajax_esm_debug_restrictions', array($this, 'debug_user_restrictions'));
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
            
            // Agregar datos localizados al script
            wp_localize_script('esm-frontend', 'esm_frontend', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('esm_nonce')
            ));
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
    
    public function filter_leads_query($where, $args) {
        error_log('ESM - filter_leads_query() - Ejecutando');
        
        if (!$this->should_apply_user_restrictions()) {
            error_log('ESM - No se aplican restricciones según should_apply_user_restrictions()');
            return $where;
        }
        
        global $wpdb;
        $current_user = wp_get_current_user();
        
        $allowed_cities = get_user_meta($current_user->ID, 'esm_allowed_cities', true);
        $allowed_categories = get_user_meta($current_user->ID, 'esm_allowed_categories', true);
        
        error_log('ESM - Ciudades permitidas: ' . print_r($allowed_cities, true));
        error_log('ESM - Categorías permitidas: ' . print_r($allowed_categories, true));
        
        // Si no hay restricciones específicas, mostrar todos
        if (empty($allowed_cities) && empty($allowed_categories)) {
            error_log('ESM - No hay restricciones específicas, mostrando todos los leads');
            return $where;
        }
        
        $additional_where = array();
        
        if (!empty($allowed_cities) && is_array($allowed_cities)) {
            $cities_in = array();
            foreach ($allowed_cities as $city) {
                $cities_in[] = $wpdb->prepare('%s', $city);
            }
            $city_condition = "EXISTS (SELECT 1 FROM {$wpdb->prefix}jet_cct_eventos e WHERE e.lead_id = l._ID AND e.ubicacion_evento IN (" . implode(',', $cities_in) . "))";
            $additional_where[] = $city_condition;
            error_log('ESM - Condición de ciudades: ' . $city_condition);
        }
        
        if (!empty($allowed_categories) && is_array($allowed_categories)) {
            $categories_in = array();
            foreach ($allowed_categories as $category) {
                $categories_in[] = $wpdb->prepare('%s', $category);
            }
            $category_condition = "EXISTS (SELECT 1 FROM {$wpdb->prefix}jet_cct_eventos e WHERE e.lead_id = l._ID AND e.categoria_listing_post IN (" . implode(',', $categories_in) . "))";
            $additional_where[] = $category_condition;
            error_log('ESM - Condición de categorías: ' . $category_condition);
        }
        
        if (!empty($additional_where)) {
            $filter_condition = '(' . implode(' OR ', $additional_where) . ')';
            error_log('ESM - Condición de filtro completa: ' . $filter_condition);
            
            if (!empty($where)) {
                $where .= ' AND ' . $filter_condition;
            } else {
                $where = $filter_condition;
            }
        }
        
        error_log('ESM - WHERE final: ' . $where);
        return $where;
    }
    
    public function add_user_restrictions_to_args($args) {
        if (!$this->should_apply_user_restrictions()) {
            return $args;
        }
        
        $current_user = wp_get_current_user();
        $args['esm_user_id'] = $current_user->ID;
        $args['esm_cities'] = get_user_meta($current_user->ID, 'esm_allowed_cities', true);
        $args['esm_categories'] = get_user_meta($current_user->ID, 'esm_allowed_categories', true);
        
        return $args;
    }
    
    private function should_apply_user_restrictions() {
        // No aplicar en wp-admin real (pero sí en AJAX)
        // Debug
        error_log('ESM - is_admin(): ' . (is_admin() ? 'true' : 'false'));
        error_log('ESM - wp_doing_ajax(): ' . (wp_doing_ajax() ? 'true' : 'false'));
        
        if (is_admin() && !wp_doing_ajax()) {
            error_log('ESM - No aplicando restricciones (estamos en admin y no es AJAX)');
            return false;
        }
        
        $current_user = wp_get_current_user();
        error_log('ESM - Usuario actual: ' . $current_user->ID . ', Roles: ' . implode(',', $current_user->roles));
        
        if (!$current_user || !in_array('ejecutivo_de_ventas', $current_user->roles)) {
            error_log('ESM - No aplicando restricciones (usuario no es ejecutivo_de_ventas)');
            return false;
        }
        
        error_log('ESM - Aplicando restricciones para ejecutivo_de_ventas');
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
        
        error_log('ESM - Ciudades disponibles en eventos: ' . print_r($cities, true));
        
        $taxonomy_cities = $this->get_taxonomy_cities();
        error_log('ESM - Ciudades de taxonomía: ' . print_r($taxonomy_cities, true));
        
        $all_cities = array_merge($cities, $taxonomy_cities);
        $all_cities = array_unique(array_filter($all_cities));
        sort($all_cities);
        
        error_log('ESM - Todas las ciudades (combinadas): ' . print_r($all_cities, true));
        
        return $all_cities;
    }
    
    public function debug_user_restrictions() {
        $current_user = wp_get_current_user();
        $allowed_cities = get_user_meta($current_user->ID, 'esm_allowed_cities', true);
        $allowed_categories = get_user_meta($current_user->ID, 'esm_allowed_categories', true);
        
        // Verificar si hay ciudades en la base de datos que coincidan con las permitidas
        global $wpdb;
        $cities_in_db = array();
        
        if (!empty($allowed_cities) && is_array($allowed_cities)) {
            $cities_in = array();
            foreach ($allowed_cities as $city) {
                $cities_in[] = $wpdb->prepare('%s', $city);
            }
            
            if (!empty($cities_in)) {
                $sql = "SELECT DISTINCT ubicacion_evento FROM {$wpdb->prefix}jet_cct_eventos 
                       WHERE ubicacion_evento IN (" . implode(',', $cities_in) . ")";
                $cities_in_db = $wpdb->get_col($sql);
            }
        }
        
        error_log('ESM - Debug: Verificando ciudades en DB que coinciden con permitidas: ' . print_r($cities_in_db, true));
        
        wp_send_json_success(array(
            'user_id' => $current_user->ID,
            'user_roles' => $current_user->roles,
            'allowed_cities' => $allowed_cities,
            'allowed_categories' => $allowed_categories,
            'cities_in_db' => $cities_in_db,
            'should_apply' => $this->should_apply_user_restrictions(),
            'is_ajax' => wp_doing_ajax(),
            'is_admin' => is_admin()
        ));
    }
    
    private function get_taxonomy_cities() {
        $cities = array();
        
        $terms = get_terms(array(
            'taxonomy' => 'hp_listing_ubicacion',
            'hide_empty' => false
        ));
        
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $cities[] = $term->name;
                
                $children = get_term_children($term->term_id, 'hp_listing_ubicacion');
                if (!is_wp_error($children)) {
                    foreach ($children as $child_id) {
                        $child_term = get_term($child_id, 'hp_listing_ubicacion');
                        if ($child_term && !is_wp_error($child_term)) {
                            $cities[] = $child_term->name;
                        }
                    }
                }
            }
        }
        
        return $cities;
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