<?php
if (!defined('ABSPATH')) {
    exit;
}

// Registrar el menú administrativo
function wbs_admin_menu() {
    add_menu_page(
        __('WP Booking Services', 'wp-booking-services'),
        __('WP Booking Services', 'wp-booking-services'),
        'manage_options',
        'wp-booking-services',
        'wbs_welcome_page',
        'dashicons-calendar-alt',
        30
    );

    add_submenu_page(
        'wp-booking-services',
        __('Categorías', 'wp-booking-services'),
        __('Categorías', 'wp-booking-services'),
        'manage_options',
        'wbs-categories',
        'wbs_categories_page'
    );

    add_submenu_page(
        'wp-booking-services',
        __('Servicios', 'wp-booking-services'),
        __('Servicios', 'wp-booking-services'),
        'manage_options',
        'wbs-services',
        'wbs_services_page'
    );

    add_submenu_page(
        'wp-booking-services',
        __('Artículos', 'wp-booking-services'),
        __('Artículos', 'wp-booking-services'),
        'manage_options',
        'wbs-articles',
        'wbs_articles_page'
    );

    add_submenu_page(
        'wp-booking-services',
        __('Descuentos', 'wp-booking-services'),
        __('Descuentos', 'wp-booking-services'),
        'manage_options',
        'wbs-discounts',
        'wbs_discounts_page'
    );
}
add_action('admin_menu', 'wbs_admin_menu');