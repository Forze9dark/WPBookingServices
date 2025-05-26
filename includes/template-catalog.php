<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Forzar que no se cargue el tema
define('WP_USE_THEMES', false);

// Limpiar cualquier salida anterior
ob_clean();
?>
<!DOCTYPE html>
<html lang="<?php echo get_locale(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html__('Catálogo de Servicios', 'wp-booking-services'); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background-color: #f5f5f5;
        }
        .catalog-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .catalog-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .catalog-header h1 {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 10px;
        }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .service-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .service-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .service-content {
            padding: 20px;
        }
        .service-title {
            font-size: 1.2em;
            margin: 0 0 10px;
        }
        .service-price {
            font-weight: bold;
            color: #2271b1;
        }
        .service-button {
            display: inline-block;
            padding: 8px 16px;
            background: #2271b1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .service-button:hover {
            background: #135e96;
            color: white;
        }
    </style>
</head>
<body>
    <div class="catalog-container">
        <div class="catalog-header">
            <h1><?php echo esc_html__('Catálogo de Servicios', 'wp-booking-services'); ?></h1>
            <p><?php echo esc_html__('Explora nuestra selección de servicios disponibles', 'wp-booking-services'); ?></p>
        </div>
        
        <div class="services-grid">
            <?php
            global $wpdb;
            $table_services = $wpdb->prefix . 'wbs_services';
            $services = $wpdb->get_results("SELECT * FROM $table_services WHERE status = 'active' ORDER BY created_at DESC");

            if ($services): foreach ($services as $service): ?>
                <div class="service-card">
                    <?php if ($service->main_image): ?>
                        <img src="<?php echo esc_url($service->main_image); ?>" alt="<?php echo esc_attr($service->title); ?>" class="service-image">
                    <?php endif; ?>
                    
                    <div class="service-content">
                        <h2 class="service-title"><?php echo esc_html($service->title); ?></h2>
                        <div class="service-description">
                            <?php echo wp_trim_words($service->description, 20); ?>
                        </div>
                        <p class="service-price">
                            <?php echo esc_html(sprintf(__('Precio: $%s', 'wp-booking-services'), number_format($service->price, 2))); ?>
                        </p>
                        <a href="<?php echo esc_url(get_permalink(get_page_by_path('reservas')) . '?service_id=' . $service->id); ?>" class="service-button">
                            <?php echo esc_html__('Reservar', 'wp-booking-services'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <p><?php echo esc_html__('No hay servicios disponibles en este momento.', 'wp-booking-services'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>
</html><?php
// Terminar la ejecución para evitar que WordPress cargue más contenido
exit;
?>