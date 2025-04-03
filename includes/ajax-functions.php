<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Funciones AJAX para el plugin WP Booking Services
 */

// Verificar si un servicio tiene artículos asociados
function wbs_check_service_articles() {
    // Verificar nonce para seguridad
    // check_ajax_referer('wbs_ajax_nonce', 'nonce');
    
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    
    if ($service_id <= 0) {
        wp_send_json_error('ID de servicio inválido');
    }
    
    global $wpdb;
    $table_articles = $wpdb->prefix . 'wbs_articles';
    $table_article_groups = $wpdb->prefix . 'wbs_article_groups';
    
    // Verificar si hay grupos de artículos asociados al servicio
    $has_articles = false;
    
    $groups = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_article_groups WHERE service_id = %d",
        $service_id
    ));
    
    if (!empty($groups)) {
        // Verificar si hay artículos en estos grupos
        foreach ($groups as $group) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_articles WHERE group_id = %d",
                $group->id
            ));
            
            if ($count > 0) {
                $has_articles = true;
                break;
            }
        }
    }
    
    wp_send_json(array('has_articles' => $has_articles));
}

// Registrar la función para usuarios autenticados y no autenticados
add_action('wp_ajax_check_service_articles', 'wbs_check_service_articles');
add_action('wp_ajax_nopriv_check_service_articles', 'wbs_check_service_articles');

// Obtener artículos de un servicio
function wbs_get_service_articles() {
    // Verificar nonce para seguridad
    // check_ajax_referer('wbs_ajax_nonce', 'nonce');
    
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    
    if ($service_id <= 0) {
        wp_send_json_error('ID de servicio inválido');
    }
    
    global $wpdb;
    $table_articles = $wpdb->prefix . 'wbs_articles';
    $table_article_groups = $wpdb->prefix . 'wbs_article_groups';
    
    // Obtener todos los grupos de artículos del servicio
    $groups = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_article_groups WHERE service_id = %d ORDER BY display_order ASC",
        $service_id
    ));
    
    $result = array();
    
    if (!empty($groups)) {
        foreach ($groups as $group) {
            // Obtener artículos de cada grupo
            $articles = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_articles WHERE group_id = %d ORDER BY display_order ASC",
                $group->id
            ));
            
            if (!empty($articles)) {
                $result[] = array(
                    'group' => $group,
                    'articles' => $articles
                );
            }
        }
    }
    
    wp_send_json(array('success' => true, 'articles' => $result));
}

// Registrar la función para usuarios autenticados y no autenticados
add_action('wp_ajax_get_service_articles', 'wbs_get_service_articles');
add_action('wp_ajax_nopriv_get_service_articles', 'wbs_get_service_articles');

// Obtener artículos de un grupo específico
function wbs_get_group_articles() {
    // Verificar nonce para seguridad
    // check_ajax_referer('wbs_ajax_nonce', 'nonce');
    
    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
    
    if ($group_id <= 0) {
        wp_send_json_error('ID de grupo inválido');
        return;
    }
    
    global $wpdb;
    $table_articles = $wpdb->prefix . 'wbs_articles';
    
    // Obtener artículos del grupo
    $articles = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_articles WHERE group_id = %d ORDER BY display_order ASC",
        $group_id
    ));
    
    // Formatear los datos para la respuesta JSON
    $formatted_articles = array();
    foreach ($articles as $article) {
        $formatted_articles[] = array(
            'id' => $article->id,
            'name' => $article->name,
            'description' => $article->description,
            'price' => $article->price
        );
    }
    
    wp_send_json($formatted_articles);
}

// Registrar la función para usuarios autenticados y no autenticados
add_action('wp_ajax_get_group_articles', 'wbs_get_group_articles');
add_action('wp_ajax_nopriv_get_group_articles', 'wbs_get_group_articles');