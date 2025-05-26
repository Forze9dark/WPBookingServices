<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Forzar que no se cargue el tema
define('WP_USE_THEMES', false);

// Obtener el ID del servicio de la URL
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

// Obtener información del servicio
global $wpdb;
$table_services = $wpdb->prefix . 'wbs_services';
$service = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_services WHERE id = %d AND status = 'active'", $service_id));

if (!$service) {
    wp_redirect(home_url('/catalogo-servicios/'));
    exit;
}

// Procesar el formulario de reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_email = sanitize_email($_POST['customer_email']);
    $customer_phone = sanitize_text_field($_POST['customer_phone']);
    $booking_date = sanitize_text_field($_POST['booking_date']);
    $number_of_people = intval($_POST['number_of_people']);
    $notes = sanitize_textarea_field($_POST['notes']);
    
    // Validar campos requeridos
    $errors = array();
    if (empty($customer_name)) $errors[] = 'El nombre es requerido';
    if (empty($customer_email)) $errors[] = 'El email es requerido';
    if (empty($customer_phone)) $errors[] = 'El teléfono es requerido';
    if (empty($booking_date)) $errors[] = 'La fecha de reserva es requerida';
    if ($number_of_people < 1) $errors[] = 'El número de personas debe ser mayor a 0';
    if ($number_of_people > $service->max_people) $errors[] = 'El número de personas excede el máximo permitido';
    
    // Si no hay errores, guardar la reserva
    if (empty($errors)) {
        $table_bookings = $wpdb->prefix . 'wbs_bookings';
        $total_amount = $service->price * $number_of_people;
        
        $result = $wpdb->insert(
            $table_bookings,
            array(
                'service_id' => $service_id,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'booking_date' => $booking_date,
                'number_of_people' => $number_of_people,
                'total_amount' => $total_amount,
                'notes' => $notes,
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s')
        );
        
        if ($result) {
            $success_message = 'Tu reserva ha sido registrada exitosamente. Te contactaremos pronto.';
        } else {
            $errors[] = 'Hubo un error al procesar tu reserva. Por favor, intenta nuevamente.';
        }
    }
}

// Limpiar cualquier salida anterior
ob_clean();
?>
<!DOCTYPE html>
<html lang="<?php echo get_locale(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html__('Reservar Servicio', 'wp-booking-services'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        .booking-container {
            max-width: 800px;
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
        .service-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .booking-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-row {
            margin-bottom: 20px;
        }
        .form-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-row input[type="text"],
        .form-row input[type="email"],
        .form-row input[type="tel"],
        .form-row input[type="date"],
        .form-row input[type="number"],
        .form-row textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-row textarea {
            height: 100px;
        }
        .submit-button {
            background: #2271b1;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-button:hover {
            background: #135e96;
        }
        .error-message {
            color: #dc3232;
            padding: 10px;
            background: #fce4e4;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success-message {
            color: #46b450;
            padding: 10px;
            background: #ecf7ed;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #2271b1;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="booking-header">
            <h1><?php echo esc_html__('Reservar Servicio', 'wp-booking-services'); ?></h1>
        </div>
        
        <div class="service-details">
            <h2><?php echo esc_html($service->title); ?></h2>
            <p><?php echo wp_kses_post($service->description); ?></p>
            <p><strong>Precio por persona:</strong> $<?php echo number_format($service->price, 2); ?></p>
            <p><strong>Máximo de personas:</strong> <?php echo esc_html($service->max_people); ?></p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo esc_html($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <p><?php echo esc_html($success_message); ?></p>
                <a href="<?php echo esc_url(home_url('/catalogo-servicios/')); ?>" class="back-link">
                    <?php echo esc_html__('← Volver al catálogo', 'wp-booking-services'); ?>
                </a>
            </div>
        <?php else: ?>
            <form method="post" class="booking-form">
                <div class="form-row">
                    <label for="customer_name"><?php echo esc_html__('Nombre completo', 'wp-booking-services'); ?> *</label>
                    <input type="text" id="customer_name" name="customer_name" required 
                           value="<?php echo isset($_POST['customer_name']) ? esc_attr($_POST['customer_name']) : ''; ?>">
                </div>

                <div class="form-row">
                    <label for="customer_email"><?php echo esc_html__('Email', 'wp-booking-services'); ?> *</label>
                    <input type="email" id="customer_email" name="customer_email" required
                           value="<?php echo isset($_POST['customer_email']) ? esc_attr($_POST['customer_email']) : ''; ?>">
                </div>

                <div class="form-row">
                    <label for="customer_phone"><?php echo esc_html__('Teléfono', 'wp-booking-services'); ?> *</label>
                    <input type="tel" id="customer_phone" name="customer_phone" required
                           value="<?php echo isset($_POST['customer_phone']) ? esc_attr($_POST['customer_phone']) : ''; ?>">
                </div>

                <div class="form-row">
                    <label for="booking_date"><?php echo esc_html__('Fecha de reserva', 'wp-booking-services'); ?> *</label>
                    <input type="text" id="booking_date" name="booking_date" required readonly
                           value="<?php echo isset($_POST['booking_date']) ? esc_attr($_POST['booking_date']) : ''; ?>">
                </div>

                <div class="form-row">
                    <label for="number_of_people"><?php echo esc_html__('Número de personas', 'wp-booking-services'); ?> *</label>
                    <input type="number" id="number_of_people" name="number_of_people" min="1" 
                           max="<?php echo esc_attr($service->max_people); ?>" required
                           value="<?php echo isset($_POST['number_of_people']) ? esc_attr($_POST['number_of_people']) : '1'; ?>">
                </div>

                <div class="form-row">
                    <label for="notes"><?php echo esc_html__('Notas adicionales', 'wp-booking-services'); ?></label>
                    <textarea id="notes" name="notes"><?php echo isset($_POST['notes']) ? esc_textarea($_POST['notes']) : ''; ?></textarea>
                </div>

                <div class="form-row">
                    <button type="submit" name="submit_booking" class="submit-button">
                        <?php echo esc_html__('Confirmar Reserva', 'wp-booking-services'); ?>
                    </button>
                </div>
            </form>

            <a href="<?php echo esc_url(home_url('/catalogo-servicios/')); ?>" class="back-link">
                <?php echo esc_html__('← Volver al catálogo', 'wp-booking-services'); ?>
            </a>
        <?php endif; ?>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $("#booking_date").datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            maxDate: '+6M',
            beforeShowDay: function(date) {
                // Aquí puedes añadir lógica para deshabilitar fechas específicas
                return [true, ''];
            }
        });

        // Calcular total al cambiar número de personas
        $('#number_of_people').on('change', function() {
            var price = <?php echo esc_js($service->price); ?>;
            var people = $(this).val();
            var total = price * people;
            // Aquí puedes actualizar el total en la UI si lo deseas
        });
    });
    </script>

    <?php wp_footer(); ?>
</body>
</html><?php
// Terminar la ejecución para evitar que WordPress cargue más contenido
exit;
?>