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
    <title><?php echo esc_html__('Reservas', 'wp-booking-services'); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background-color: #f5f5f5;
        }
        .booking-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .booking-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .booking-header h1 {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 10px;
        }
        .booking-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="booking-header">
            <h1><?php echo esc_html__('Sistema de Reservas', 'wp-booking-services'); ?></h1>
            <p><?php echo esc_html__('Reserva tu servicio de forma fácil y rápida', 'wp-booking-services'); ?></p>
        </div>
        <div class="booking-content">
            <?php
            // Aquí irá el contenido del formulario de reservas
            ?>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>
</html><?php
// Terminar la ejecución para evitar que WordPress cargue más contenido
exit;
?>