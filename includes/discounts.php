<?php
if (!defined('ABSPATH')) {
    exit;
}

// Registrar estilos de administración para descuentos
function wbs_discounts_admin_styles() {
    wp_enqueue_style('dashicons');
    wp_enqueue_style('wbs-admin-styles', plugin_dir_url(__FILE__) . '../assets/css/admin-style.css', array(), '1.0.0');
}
add_action('admin_enqueue_scripts', 'wbs_discounts_admin_styles');

// Página principal de descuentos
function wbs_discounts_page() {
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

    switch ($action) {
        case 'add':
        case 'edit':
            wbs_discount_form();
            break;
        default:
            wbs_discounts_list();
            break;
    }
}

// Mostrar lista de descuentos
function wbs_discounts_list() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wbs_discounts';
    $discounts = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html__('Descuentos', 'wp-booking-services'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-discounts&action=add')); ?>" class="page-title-action">
            <?php echo esc_html__('Añadir Nuevo', 'wp-booking-services'); ?>
        </a>
        <hr class="wp-header-end">
        
        <?php if (isset($_GET['message'])): ?>
            <?php if ($_GET['message'] == '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Descuento guardado correctamente.', 'wp-booking-services'); ?></p>
                </div>
            <?php elseif ($_GET['message'] == '2'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Descuento eliminado correctamente.', 'wp-booking-services'); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary"><?php echo esc_html__('Título', 'wp-booking-services'); ?></th>
                    <th scope="col" class="manage-column"><?php echo esc_html__('Tipo', 'wp-booking-services'); ?></th>
                    <th scope="col" class="manage-column"><?php echo esc_html__('Condición', 'wp-booking-services'); ?></th>
                    <th scope="col" class="manage-column"><?php echo esc_html__('Valor', 'wp-booking-services'); ?></th>
                    <th scope="col" class="manage-column"><?php echo esc_html__('Acciones', 'wp-booking-services'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($discounts)): ?>
                    <tr>
                        <td colspan="5"><?php echo esc_html__('No hay descuentos disponibles.', 'wp-booking-services'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($discounts as $discount): ?>
                        <tr>
                            <td class="title column-title has-row-actions column-primary">
                                <strong>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-discounts&action=edit&id=' . $discount->id)); ?>">
                                        <?php echo esc_html($discount->title); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-discounts&action=edit&id=' . $discount->id)); ?>">
                                            <?php echo esc_html__('Editar', 'wp-booking-services'); ?>
                                        </a> | 
                                    </span>
                                    <span class="trash">
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wbs_delete_discount&id=' . $discount->id), 'wbs_delete_discount_' . $discount->id)); ?>" class="submitdelete" onclick="return confirm('<?php echo esc_js(__('¿Estás seguro de que deseas eliminar este descuento?', 'wp-booking-services')); ?>')">
                                            <?php echo esc_html__('Eliminar', 'wp-booking-services'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php 
                                echo $discount->discount_type === 'percentage' 
                                    ? esc_html__('Porcentaje', 'wp-booking-services') 
                                    : esc_html__('Monto Fijo', 'wp-booking-services'); 
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($discount->condition_type === 'price') {
                                    echo esc_html(sprintf(__('Precio > $%s', 'wp-booking-services'), number_format($discount->condition_value, 2)));
                                } else {
                                    echo esc_html(sprintf(__('Personas > %s', 'wp-booking-services'), $discount->condition_value));
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($discount->discount_type === 'percentage') {
                                    echo esc_html($discount->discount_value . '%');
                                } else {
                                    echo esc_html('$' . number_format($discount->discount_value, 2));
                                }
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-discounts&action=edit&id=' . $discount->id)); ?>" class="button button-small">
                                    <span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Editar', 'wp-booking-services'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Formulario para añadir/editar descuento
function wbs_discount_form() {
    global $wpdb;
    $discount_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $discount = null;

    if ($discount_id > 0) {
        $table_name = $wpdb->prefix . 'wbs_discounts';
        $discount = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $discount_id));
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php echo $discount_id ? esc_html__('Editar Descuento', 'wp-booking-services') : esc_html__('Añadir Nuevo Descuento', 'wp-booking-services'); ?>
        </h1>
        <hr class="wp-header-end">

        <?php if (isset($_GET['message']) && $_GET['message'] == '1'): ?>
            <div id="message" class="updated notice notice-success is-dismissible">
                <p><?php echo esc_html__('Descuento guardado correctamente.', 'wp-booking-services'); ?></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__('Descartar este aviso.', 'wp-booking-services'); ?></span></button>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wbs-discount-form">
            <?php wp_nonce_field('wbs_discount_nonce', 'wbs_discount_nonce'); ?>
            <input type="hidden" name="action" value="wbs_save_discount">
            <input type="hidden" name="discount_id" value="<?php echo esc_attr($discount_id); ?>">

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="title"><?php echo esc_html__('Título del Descuento', 'wp-booking-services'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input name="title" type="text" id="title" class="regular-text" value="<?php echo $discount ? esc_attr($discount->title) : ''; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="description"><?php echo esc_html__('Descripción', 'wp-booking-services'); ?></label>
                    </th>
                    <td>
                        <textarea name="description" id="description" class="large-text" rows="5"><?php echo $discount ? esc_textarea($discount->description) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="discount_type"><?php echo esc_html__('Tipo de Descuento', 'wp-booking-services'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select name="discount_type" id="discount_type" required>
                            <option value="percentage" <?php selected($discount && $discount->discount_type === 'percentage'); ?>>
                                <?php echo esc_html__('Porcentaje', 'wp-booking-services'); ?>
                            </option>
                            <option value="fixed" <?php selected($discount && $discount->discount_type === 'fixed'); ?>>
                                <?php echo esc_html__('Monto Fijo', 'wp-booking-services'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="condition_type"><?php echo esc_html__('Condición del Descuento', 'wp-booking-services'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select name="condition_type" id="condition_type" required>
                            <option value="price" <?php selected($discount && $discount->condition_type === 'price'); ?>>
                                <?php echo esc_html__('Si el precio es mayor a', 'wp-booking-services'); ?>
                            </option>
                            <option value="people" <?php selected($discount && $discount->condition_type === 'people'); ?>>
                                <?php echo esc_html__('Si la cantidad de personas es mayor a', 'wp-booking-services'); ?>
                            </option>
                        </select>
                        <input name="condition_value" type="number" id="condition_value" class="small-text" min="0" step="<?php echo $discount && $discount->condition_type === 'price' ? '0.01' : '1'; ?>" value="<?php echo $discount ? esc_attr($discount->condition_value) : '0'; ?>" required>
                        <p class="description" id="condition-description">
                            <?php echo esc_html__('Establece el valor mínimo para que se aplique el descuento.', 'wp-booking-services'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="discount_value"><?php echo esc_html__('Valor del Descuento', 'wp-booking-services'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input name="discount_value" type="number" id="discount_value" class="regular-text" min="0" step="<?php echo $discount && $discount->discount_type === 'percentage' ? '1' : '0.01'; ?>" value="<?php echo $discount ? esc_attr($discount->discount_value) : '0'; ?>" required>
                        <span id="discount-symbol"><?php echo $discount && $discount->discount_type === 'percentage' ? '%' : '$'; ?></span>
                        <p class="description">
                            <?php echo esc_html__('Valor del descuento a aplicar.', 'wp-booking-services'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="status"><?php echo esc_html__('Estado', 'wp-booking-services'); ?></label>
                    </th>
                    <td>
                        <select name="status" id="status">
                            <option value="active" <?php selected(!$discount || $discount->status === 'active'); ?>>
                                <?php echo esc_html__('Activo', 'wp-booking-services'); ?>
                            </option>
                            <option value="inactive" <?php selected($discount && $discount->status === 'inactive'); ?>>
                                <?php echo esc_html__('Inactivo', 'wp-booking-services'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__('Guardar Descuento', 'wp-booking-services'); ?>">
                <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-discounts')); ?>" class="button button-secondary">
                    <?php echo esc_html__('Cancelar', 'wp-booking-services'); ?>
                </a>
            </p>
        </form>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Actualizar el paso y símbolo según el tipo de descuento
        $('#discount_type').on('change', function() {
            if ($(this).val() === 'percentage') {
                $('#discount_value').attr('step', '1');
                $('#discount-symbol').text('%');
            } else {
                $('#discount_value').attr('step', '0.01');
                $('#discount-symbol').text('$');
            }
        });

        // Actualizar el paso según el tipo de condición
        $('#condition_type').on('change', function() {
            if ($(this).val() === 'price') {
                $('#condition_value').attr('step', '0.01');
            } else {
                $('#condition_value').attr('step', '1');
            }
        });
    });
    </script>
    <?php
}

// Guardar descuento
function wbs_save_discount() {
    if (!isset($_POST['wbs_discount_nonce']) || !wp_verify_nonce($_POST['wbs_discount_nonce'], 'wbs_discount_nonce')) {
        wp_die(esc_html__('Acción no autorizada.', 'wp-booking-services'));
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'wp-booking-services'));
    }

    global $wpdb;
    $discount_id = isset($_POST['discount_id']) ? intval($_POST['discount_id']) : 0;
    $title = sanitize_text_field($_POST['title']);
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $discount_type = sanitize_text_field($_POST['discount_type']);
    $condition_type = sanitize_text_field($_POST['condition_type']);
    $condition_value = floatval($_POST['condition_value']);
    $discount_value = floatval($_POST['discount_value']);
    $status = sanitize_text_field($_POST['status']);

    $table_name = $wpdb->prefix . 'wbs_discounts';

    if ($discount_id > 0) {
        // Actualizar descuento existente
        $wpdb->update(
            $table_name,
            array(
                'title' => $title,
                'description' => $description,
                'discount_type' => $discount_type,
                'condition_type' => $condition_type,
                'condition_value' => $condition_value,
                'discount_value' => $discount_value,
                'status' => $status,
            ),
            array('id' => $discount_id)
        );
    } else {
        // Insertar nuevo descuento
        $wpdb->insert(
            $table_name,
            array(
                'title' => $title,
                'description' => $description,
                'discount_type' => $discount_type,
                'condition_type' => $condition_type,
                'condition_value' => $condition_value,
                'discount_value' => $discount_value,
                'status' => $status,
            )
        );
        $discount_id = $wpdb->insert_id;
    }

    // Redirigir después de guardar
    wp_redirect(admin_url('admin.php?page=wbs-discounts&action=edit&id=' . $discount_id . '&message=1'));
    exit;
}
add_action('admin_post_wbs_save_discount', 'wbs_save_discount');

// Eliminar descuento
function wbs_delete_discount() {
    $discount_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if (!$discount_id) {
        wp_redirect(admin_url('admin.php?page=wbs-discounts'));
        exit;
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wbs_delete_discount_' . $discount_id)) {
        wp_die(esc_html__('Acción no autorizada.', 'wp-booking-services'));
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'wp-booking-services'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wbs_discounts';
    $wpdb->delete($table_name, array('id' => $discount_id));

    wp_redirect(admin_url('admin.php?page=wbs-discounts&message=2'));
    exit;
}
add_action('admin_post_wbs_delete_discount', 'wbs_delete_discount');