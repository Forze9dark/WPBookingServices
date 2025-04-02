<?php
if (!defined('ABSPATH')) {
    exit;
}

// Procesar el formulario de servicio
function wbs_process_service_form() {
    if (!isset($_POST['wbs_service_nonce']) || !wp_verify_nonce($_POST['wbs_service_nonce'], 'wbs_service_nonce')) {
        wp_die(__('Acceso no autorizado', 'wp-booking-services'));
    }

    global $wpdb;
    $table_services = $wpdb->prefix . 'wbs_services';
    $table_gallery = $wpdb->prefix . 'wbs_service_gallery';
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;

    // Procesar imagen principal
    $main_image_url = '';
    if (!empty($_POST['main_image_url'])) {
        $main_image_url = esc_url_raw($_POST['main_image_url']);
    } elseif ($service_id > 0) {
        $main_image_url = $wpdb->get_var($wpdb->prepare(
            "SELECT main_image FROM $table_services WHERE id = %d",
            $service_id
        ));
    }

    // Preparar datos del servicio
    $service_data = array(
        'title' => sanitize_text_field($_POST['title']),
        'description' => wp_kses_post($_POST['description']),
        'main_image' => $main_image_url,
        'promo_video' => esc_url_raw($_POST['promo_video']),
        'max_people' => intval($_POST['max_people']),
        'price' => floatval(str_replace(',', '', $_POST['price'])),
        'service_date' => date('Y-m-d', strtotime(sanitize_text_field($_POST['service_date']))),
        'status' => sanitize_text_field($_POST['status'])
    );

    // Insertar o actualizar servicio
    if ($service_id > 0) {
        $wpdb->update(
            $table_services,
            $service_data,
            array('id' => $service_id),
            array('%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s'),
            array('%d')
        );
    } else {
        $wpdb->insert(
            $table_services,
            $service_data,
            array('%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s')
        );
        $service_id = $wpdb->insert_id;
    }

    // Procesar galería de imágenes
    if (!empty($_POST['gallery_images']) && is_array($_POST['gallery_images'])) {
        $gallery_images = $_POST['gallery_images'];
        $max_gallery_images = 5;

        // Obtener número actual de imágenes en la galería
        $current_images = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_gallery WHERE service_id = %d",
            $service_id
        ));

        // Limitar el número de nuevas imágenes
        $remaining_slots = $max_gallery_images - $current_images;
        $images_to_process = min(count($gallery_images), $remaining_slots);

        for ($i = 0; $i < $images_to_process; $i++) {
            $image_url = esc_url_raw($gallery_images[$i]);
            
            if (!empty($image_url)) {
                $wpdb->insert(
                    $table_gallery,
                    array(
                        'service_id' => $service_id,
                        'image_url' => $image_url
                    ),
                    array('%d', '%s')
                );
            }
        }
    }

    // Redireccionar a la lista de servicios
    wp_redirect(admin_url('admin.php?page=wbs-services&message=success'));
    exit;
}
add_action('admin_post_wbs_save_service', 'wbs_process_service_form');

// Eliminar servicio
function wbs_delete_service() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wbs_delete_service')) {
        wp_send_json_error('Acceso no autorizado');
    }

    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    if ($service_id > 0) {
        global $wpdb;
        $table_services = $wpdb->prefix . 'wbs_services';
        
        $wpdb->delete(
            $table_services,
            array('id' => $service_id),
            array('%d')
        );

        wp_send_json_success();
    }

    wp_send_json_error('ID de servicio inválido');
}
add_action('wp_ajax_wbs_delete_service', 'wbs_delete_service');