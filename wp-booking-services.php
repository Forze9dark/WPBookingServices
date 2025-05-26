<?php
/**
 * Plugin Name: WP Booking Services
 * Plugin URI: https://example.com/wp-booking-services
 * Description: Un plugin para gestionar servicios de reservas en WordPress
 * Version: 1.0.0
 * Author: Carlos Sanchez
 * Author URI: https://example.com
 * Text Domain: wp-booking-services
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Evitar el acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WBS_VERSION', '1.0.0');
define('WBS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WBS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Función para verificar si WooCommerce está instalado y activado
function wbs_check_woocommerce() {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        deactivate_plugins(plugin_basename(__FILE__));
        add_action('admin_notices', 'wbs_woocommerce_missing_notice');
        return false;
    }
    return true;
}

// Función para mostrar el aviso de WooCommerce faltante
function wbs_woocommerce_missing_notice() {
    $message = sprintf(
        esc_html__('WP Booking Services requiere que WooCommerce esté instalado y activado. %sInstalar WooCommerce%s', 'wp-booking-services'),
        '<a href="' . esc_url(admin_url('plugin-install.php?tab=search&s=woocommerce')) . '">',
        '</a>'
    );
    echo '<div class="error"><p>' . $message . '</p></div>';
}

// Función que se ejecuta al activar el plugin
function wbs_activate() {
    if (!wbs_check_woocommerce()) {
        wp_die(
            esc_html__('WP Booking Services requiere que WooCommerce esté instalado y activado.', 'wp-booking-services'),
            'Plugin Dependency Error',
            array('back_link' => true)
        );
    }

    // Crear la página del catálogo si no existe
    $catalog_page = get_page_by_path('catalogo-servicios');
    if (!$catalog_page) {
        $page_data = array(
            'post_title'    => 'Catálogo de Servicios',
            'post_name'     => 'catalogo-servicios',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_content'  => '',
            'page_template' => 'template-catalog.php'
        );
        
        $page_id = wp_insert_post($page_data);
        if ($page_id) {
            update_post_meta($page_id, '_wp_page_template', 'template-catalog.php');
        }
    }

    // Crear la página de reservas si no existe
    $booking_page = get_page_by_path('reservas');
    if (!$booking_page) {
        $booking_page_data = array(
            'post_title'    => 'Reservas',
            'post_name'     => 'reservas',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_content'  => '',
            'page_template' => 'template-booking.php'
        );
        
        $booking_page_id = wp_insert_post($booking_page_data);
        if ($booking_page_id) {
            update_post_meta($booking_page_id, '_wp_page_template', 'template-booking.php');
        }
    }

    // Crear las tablas en la base de datos
    wbs_create_tables();
}
register_activation_hook(__FILE__, 'wbs_activate');

// Función que se ejecuta al desactivar el plugin
function wbs_deactivate() {
    global $wpdb;
    
    // Eliminar todas las tablas del plugin
    $table_gallery = $wpdb->prefix . 'wbs_service_gallery';
    $table_articles = $wpdb->prefix . 'wbs_articles';
    $table_service_discounts = $wpdb->prefix . 'wbs_service_discounts';
    $table_services = $wpdb->prefix . 'wbs_services';
    $table_categories = $wpdb->prefix . 'wbs_categories';
    $table_article_groups = $wpdb->prefix . 'wbs_article_groups';
    $table_discounts = $wpdb->prefix . 'wbs_discounts';
    
    // Eliminar primero las tablas con claves foráneas
    $wpdb->query("DROP TABLE IF EXISTS $table_gallery");
    $wpdb->query("DROP TABLE IF EXISTS $table_articles");
    $wpdb->query("DROP TABLE IF EXISTS $table_service_discounts");
    $wpdb->query("DROP TABLE IF EXISTS $table_services");
    $wpdb->query("DROP TABLE IF EXISTS $table_categories");
    $wpdb->query("DROP TABLE IF EXISTS $table_article_groups");
    $wpdb->query("DROP TABLE IF EXISTS $table_discounts");
}
register_deactivation_hook(__FILE__, 'wbs_deactivate');

// Incluir archivos necesarios
require_once WBS_PLUGIN_DIR . 'includes/database.php';
require_once WBS_PLUGIN_DIR . 'includes/ajax-functions.php';
require_once WBS_PLUGIN_DIR . 'includes/discounts.php';
require_once WBS_PLUGIN_DIR . 'admin/welcome-page.php';
require_once WBS_PLUGIN_DIR . 'admin/admin-menu.php';
require_once WBS_PLUGIN_DIR . 'admin/services.php';
require_once WBS_PLUGIN_DIR . 'admin/services-actions.php';
require_once WBS_PLUGIN_DIR . 'admin/categories.php';
require_once WBS_PLUGIN_DIR . 'admin/articles.php';

// Registrar el template personalizado
function wbs_add_template($templates) {
    $templates['template-catalog.php'] = 'Catálogo Servicios Template';
    $templates['template-booking.php'] = 'Página de Reservas Template';
    return $templates;
}
add_filter('theme_page_templates', 'wbs_add_template');

// Cargar el template personalizado
function wbs_load_template($template) {
    if (is_page_template('template-catalog.php')) {
        $template = WBS_PLUGIN_DIR . 'includes/template-catalog.php';
    } elseif (is_page_template('template-booking.php')) {
        $template = WBS_PLUGIN_DIR . 'includes/template-booking.php';
    }
    return $template;
}
add_filter('template_include', 'wbs_load_template');

// Inicialización del plugin
function wbs_init() {
    // Cargar traducciones
    load_plugin_textdomain('wp-booking-services', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'wbs_init');
