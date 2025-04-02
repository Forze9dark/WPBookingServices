<?php
if (!defined('ABSPATH')) {
    exit;
}

function wbs_welcome_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Bienvenido a WP Booking Services', 'wp-booking-services'); ?></h1>
        <div class="about-text">
            <?php echo esc_html__('Gracias por instalar WP Booking Services. Este plugin te ayudará a gestionar servicios de reservas en tu sitio WordPress.', 'wp-booking-services'); ?>
        </div>
    </div>
    <?php
}