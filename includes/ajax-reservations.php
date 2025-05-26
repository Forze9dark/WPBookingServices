<?php
if (!defined('ABSPATH')) {
    exit;
}

// Función para guardar una nueva reserva
function wbs_save_reservation() {
    check_ajax_referer('wbs_nonce');
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'wbs_reservations';
    
    $service_id = intval($_POST['service_id']);
    $reservation_date = sanitize_text_field($_POST['reservation_date']);
    $participants = intval($_POST['participants']);
    $payment_method = sanitize_text_field($_POST['payment_method']);
    $total_amount = floatval($_POST['total_amount']);
    
    // Validar datos
    if (!$service_id || !$reservation_date || !$participants || !$payment_method || !$total_amount) {
        wp_send_json_error('Todos los campos son requeridos');
        return;
    }
    
    // Insertar la reserva en la base de datos
    $result = $wpdb->insert(
        $table_name,
        array(
            'service_id' => $service_id,
            'reservation_date' => $reservation_date,
            'participants' => $participants,
            'payment_method' => $payment_method,
            'total_amount' => $total_amount,
            'status' => 'pending'
        ),
        array('%d', '%s', '%d', '%s', '%f', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error('Error al guardar la reserva');
        return;
    }
    
    wp_send_json_success(array(
        'message' => 'Reserva guardada exitosamente',
        'reservation_id' => $wpdb->insert_id
    ));
}
add_action('wp_ajax_save_reservation', 'wbs_save_reservation');
add_action('wp_ajax_nopriv_save_reservation', 'wbs_save_reservation');