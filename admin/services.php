<?php
if (!defined('ABSPATH')) {
    exit;
}

// Registrar estilos de administración
function wbs_admin_styles() {
    wp_enqueue_style('dashicons');
    wp_enqueue_style('wbs-admin-styles', plugin_dir_url(__FILE__) . '../assets/css/admin-style.css', array(), '1.0.0');
    if (isset($_GET['page']) && $_GET['page'] === 'wbs-services' && isset($_GET['action']) && ($_GET['action'] === 'add' || $_GET['action'] === 'edit')) {
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css', array(), '1.13.2');
    }
}
add_action('admin_enqueue_scripts', 'wbs_admin_styles');

// Inicializar datepicker
function wbs_init_datepicker() {
    if (isset($_GET['page']) && $_GET['page'] === 'wbs-services' && isset($_GET['action']) && ($_GET['action'] === 'add' || $_GET['action'] === 'edit')) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_add_inline_script('jquery-ui-datepicker', '
            jQuery(document).ready(function($) {
                if (typeof $.datepicker !== "undefined") {
                    $.datepicker.regional["es"] = {
                        closeText: "Cerrar",
                        prevText: "Anterior",
                        nextText: "Siguiente",
                        currentText: "Hoy",
                        monthNames: ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"],
                        monthNamesShort: ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"],
                        dayNames: ["Domingo","Lunes","Martes","Miércoles","Jueves","Viernes","Sábado"],
                        dayNamesShort: ["Dom","Lun","Mar","Mié","Jue","Vie","Sáb"],
                        dayNamesMin: ["Do","Lu","Ma","Mi","Ju","Vi","Sa"],
                        weekHeader: "Sm",
                        dateFormat: "dd/mm/yy",
                        firstDay: 1,
                        isRTL: false,
                        showMonthAfterYear: false,
                        yearSuffix: ""
                    };
                    $.datepicker.setDefaults($.datepicker.regional["es"]);
                    $("#service_date").datepicker({
                        dateFormat: "dd/mm/yy",
                        changeMonth: true,
                        changeYear: true,
                        minDate: 0
                    });
                }
            });
        ');
    }
}
add_action('admin_enqueue_scripts', 'wbs_init_datepicker');

// Página principal de servicios
function wbs_services_page() {
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

    switch ($action) {
        case 'add':
        case 'edit':
            wbs_service_form();
            break;
        default:
            wbs_services_list();
            break;
    }
}

// Mostrar lista de servicios
function wbs_services_list() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wbs_services';
    $services = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html__('Servicios', 'wp-booking-services'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-services&action=add')); ?>" class="page-title-action">
            <?php echo esc_html__('Añadir Nuevo', 'wp-booking-services'); ?>
        </a>
        <hr class="wp-header-end">
        
        <?php if (empty($services)): ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('No hay servicios disponibles. ¡Añade uno nuevo!', 'wp-booking-services'); ?></p>
        </div>
        <?php else: ?>
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <!-- Aquí podrían ir acciones en lote en el futuro -->
            </div>
            <div class="tablenav-pages one-page">
                <span class="displaying-num"><?php echo sprintf(_n('%s elemento', '%s elementos', count($services), 'wp-booking-services'), number_format_i18n(count($services))); ?></span>
            </div>
            <br class="clear">
        </div>
        
        <table class="wp-list-table widefat fixed striped table-view-list posts">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary sortable desc">
                        <a href="#"><span><?php echo esc_html__('Título', 'wp-booking-services'); ?></span><span class="sorting-indicator"></span></a>
                    </th>
                    <th scope="col" class="manage-column column-price sortable desc">
                        <a href="#"><span><?php echo esc_html__('Precio', 'wp-booking-services'); ?></span><span class="sorting-indicator"></span></a>
                    </th>
                    <th scope="col" class="manage-column column-date sortable desc">
                        <a href="#"><span><?php echo esc_html__('Fecha', 'wp-booking-services'); ?></span><span class="sorting-indicator"></span></a>
                    </th>
                    <th scope="col" class="manage-column column-status"><?php echo esc_html__('Estado', 'wp-booking-services'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php echo esc_html__('Acciones', 'wp-booking-services'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php foreach ($services as $service): ?>
                    <tr id="service-<?php echo esc_attr($service->id); ?>" class="iedit author-self level-0 post-<?php echo esc_attr($service->id); ?> type-service status-<?php echo esc_attr(strtolower($service->status)); ?>">
                        <td class="title column-title has-row-actions column-primary page-title" data-colname="<?php echo esc_attr__('Título', 'wp-booking-services'); ?>">
                            <strong>
                                <a class="row-title" href="<?php echo esc_url(admin_url('admin.php?page=wbs-services&action=edit&id=' . $service->id)); ?>">
                                    <?php echo esc_html($service->title); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-services&action=edit&id=' . $service->id)); ?>" aria-label="<?php echo esc_attr__('Editar', 'wp-booking-services'); ?>">
                                        <?php echo esc_html__('Editar', 'wp-booking-services'); ?>
                                    </a> | 
                                </span>
                                <span class="trash">
                                    <a href="#" class="wbs-delete-service submitdelete" data-id="<?php echo esc_attr($service->id); ?>" aria-label="<?php echo esc_attr__('Eliminar', 'wp-booking-services'); ?>">
                                        <?php echo esc_html__('Eliminar', 'wp-booking-services'); ?>
                                    </a>
                                </span>
                            </div>
                            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php echo esc_html__('Mostrar más detalles', 'wp-booking-services'); ?></span></button>
                        </td>
                        <td class="column-price" data-colname="<?php echo esc_attr__('Precio', 'wp-booking-services'); ?>">
                            <span class="price-amount">RD$ <?php echo esc_html(number_format($service->price, 2)); ?></span>
                        </td>
                        <td class="column-date" data-colname="<?php echo esc_attr__('Fecha', 'wp-booking-services'); ?>">
                            <?php echo esc_html(date('d/m/Y', strtotime($service->service_date))); ?>
                        </td>
                        <td class="column-status" data-colname="<?php echo esc_attr__('Estado', 'wp-booking-services'); ?>">
                            <?php if ($service->status === 'active'): ?>
                                <span class="status-active post-state"><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html__('Activo', 'wp-booking-services'); ?></span>
                            <?php else: ?>
                                <span class="status-inactive post-state"><span class="dashicons dashicons-marker"></span> <?php echo esc_html__('Inactivo', 'wp-booking-services'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-actions" data-colname="<?php echo esc_attr__('Acciones', 'wp-booking-services'); ?>">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-services&action=edit&id=' . $service->id)); ?>" class="button button-small">
                                <span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Editar', 'wp-booking-services'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary sortable desc">
                        <a href="#"><span><?php echo esc_html__('Título', 'wp-booking-services'); ?></span><span class="sorting-indicator"></span></a>
                    </th>
                    <th scope="col" class="manage-column column-price sortable desc">
                        <a href="#"><span><?php echo esc_html__('Precio', 'wp-booking-services'); ?></span><span class="sorting-indicator"></span></a>
                    </th>
                    <th scope="col" class="manage-column column-date sortable desc">
                        <a href="#"><span><?php echo esc_html__('Fecha', 'wp-booking-services'); ?></span><span class="sorting-indicator"></span></a>
                    </th>
                    <th scope="col" class="manage-column column-status"><?php echo esc_html__('Estado', 'wp-booking-services'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php echo esc_html__('Acciones', 'wp-booking-services'); ?></th>
                </tr>
            </tfoot>
        </table>
        
        <div class="tablenav bottom">
            <div class="tablenav-pages one-page">
                <span class="displaying-num"><?php echo sprintf(_n('%s elemento', '%s elementos', count($services), 'wp-booking-services'), number_format_i18n(count($services))); ?></span>
            </div>
            <br class="clear">
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// Guardar servicio
function wbs_save_service() {
    global $wpdb;

    if (isset($_POST['wbs_service_nonce']) && wp_verify_nonce($_POST['wbs_service_nonce'], 'wbs_service_nonce')) {
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $title = sanitize_text_field($_POST['title']);
        $description = wp_kses_post($_POST['description']);
        $main_image = esc_url_raw($_POST['main_image_url']);
        $gallery_images = isset($_POST['gallery_images']) ? array_map('esc_url_raw', $_POST['gallery_images']) : array();
        $promo_video = esc_url_raw($_POST['promo_video']);
        $max_people = intval($_POST['max_people']);
        $price = floatval($_POST['price']);
        $service_date = sanitize_text_field($_POST['service_date']);
        $status = sanitize_text_field($_POST['status']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $group_id = !empty($_POST['group_id']) ? intval($_POST['group_id']) : null;

        $table_name = $wpdb->prefix . 'wbs_services';

        if ($service_id > 0) {
            // Actualizar servicio existente
            $wpdb->update(
                $table_name,
                array(
                    'title' => $title,
                    'description' => $description,
                    'main_image' => $main_image,
                    'promo_video' => $promo_video,
                    'max_people' => $max_people,
                    'price' => $price,
                    'service_date' => $service_date,
                    'status' => $status,
                    'category_id' => $category_id,
                    'group_id' => $group_id,
                ),
                array('id' => $service_id)
            );
        } else {
            // Insertar nuevo servicio
            $wpdb->insert(
                $table_name,
                array(
                    'title' => $title,
                    'description' => $description,
                    'main_image' => $main_image,
                    'promo_video' => $promo_video,
                    'max_people' => $max_people,
                    'price' => $price,
                    'service_date' => $service_date,
                    'status' => $status,
                    'category_id' => $category_id,
                    'group_id' => $group_id,
                )
            );
            $service_id = $wpdb->insert_id;
        }

        // Guardar imágenes de la galería
        $table_gallery = $wpdb->prefix . 'wbs_service_gallery';
        $wpdb->delete($table_gallery, array('service_id' => $service_id));
        foreach ($gallery_images as $image_url) {
            $wpdb->insert(
                $table_gallery,
                array(
                    'service_id' => $service_id,
                    'image_url' => $image_url,
                )
            );
        }

        // Redirigir después de guardar
        wp_redirect(admin_url('admin.php?page=wbs-services&action=edit&id=' . $service_id . '&message=1'));
        exit;
    }
}
add_action('admin_post_wbs_save_service', 'wbs_save_service');

// Formulario para añadir/editar servicio
function wbs_service_form() {
    global $wpdb;
    $service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $service = null;

    if ($service_id > 0) {
        $table_name = $wpdb->prefix . 'wbs_services';
        $service = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $service_id));
        if ($service) {
            $service->group_id = intval($service->group_id);
        }
    }

    // Registrar scripts y estilos necesarios
    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('wp-jquery-ui-dialog');
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo $service_id ? esc_html__('Editar Servicio', 'wp-booking-services') : esc_html__('Añadir Nuevo Servicio', 'wp-booking-services'); ?></h1>
        <hr class="wp-header-end">
        
        <?php if (isset($_GET['message']) && $_GET['message'] == '1'): ?>
        <div id="message" class="updated notice notice-success is-dismissible">
            <p><?php echo esc_html__('Servicio guardado correctamente.', 'wp-booking-services'); ?></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_html__('Descartar este aviso.', 'wp-booking-services'); ?></span></button>
        </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" id="wbs-service-form" style="min-height: calc(100vh - 200px); margin-bottom: 50px;">
            <?php wp_nonce_field('wbs_service_nonce', 'wbs_service_nonce'); ?>
            <input type="hidden" name="action" value="wbs_save_service">
            <input type="hidden" name="service_id" value="<?php echo esc_attr($service_id); ?>">
            
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div id="titlediv">
                            <div id="titlewrap">
                                <label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo esc_html__('Añadir título', 'wp-booking-services'); ?></label>
                                <input type="text" name="title" size="30" id="title" value="<?php echo $service ? esc_attr($service->title) : ''; ?>" spellcheck="true" autocomplete="off" placeholder="<?php echo esc_attr__('Añadir título', 'wp-booking-services'); ?>" required>
                            </div>
                        </div>
                        
                        <!-- Descripción -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle"><?php echo esc_html__('Descripción', 'wp-booking-services'); ?></h2>
                            </div>
                            <div class="inside">
                                <?php
                                wp_editor(
                                    $service ? $service->description : '',
                                    'description',
                                    array(
                                        'textarea_name' => 'description',
                                        'media_buttons' => true,
                                        'textarea_rows' => 10,
                                        'editor_class' => 'wp-editor-area',
                                        'editor_css' => '',
                                        'teeny' => false
                                    )
                                );
                                ?>
                            </div>
                        </div>
                        
                        <!-- Multimedia -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle"><?php echo esc_html__('Multimedia', 'wp-booking-services'); ?></h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="featured_image"><?php echo esc_html__('Imagen Principal', 'wp-booking-services'); ?></label>
                                        </th>
                                        <td>
                                            <div class="featured-image-preview">
                                                <?php if ($service && $service->main_image): ?>
                                                    <img src="<?php echo esc_url($service->main_image); ?>" style="max-width: 100%; max-height: 200px;">
                                                <?php endif; ?>
                                            </div>
                                            <input type="hidden" name="main_image_url" id="main_image_url" value="<?php echo $service ? esc_attr($service->main_image) : ''; ?>">
                                            <input type="button" id="upload_featured_image" class="button" value="<?php echo esc_attr__('Seleccionar Imagen', 'wp-booking-services'); ?>">
                                            <input type="button" id="remove_featured_image" class="button" value="<?php echo esc_attr__('Eliminar Imagen', 'wp-booking-services'); ?>" <?php echo (!$service || !$service->main_image) ? 'style="display:none;"' : ''; ?>>
                                            <p class="description"><?php echo esc_html__('Imagen destacada para este servicio', 'wp-booking-services'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="gallery"><?php echo esc_html__('Galería de Fotos', 'wp-booking-services'); ?></label>
                                        </th>
                                        <td>
                                            <div class="gallery-container">
                                                <div class="gallery-preview">
                                                    <?php 
                                                    if ($service_id > 0) {
                                                        global $wpdb;
                                                        $table_gallery = $wpdb->prefix . 'wbs_service_gallery';
                                                        $gallery_images = $wpdb->get_results($wpdb->prepare(
                                                            "SELECT * FROM $table_gallery WHERE service_id = %d",
                                                            $service_id
                                                        ));
                                                        
                                                        if ($gallery_images) {
                                                            echo '<div class="gallery-images">';
                                                            foreach ($gallery_images as $image) {
                                                                echo '<div class="gallery-item">';
                                                                echo '<img src="' . esc_url($image->image_url) . '" style="max-width: 100px; max-height: 100px;">';
                                                                echo '<a href="#" class="remove-gallery-image" data-id="' . esc_attr($image->id) . '">' . esc_html__('Eliminar', 'wp-booking-services') . '</a>';
                                                                echo '</div>';
                                                            }
                                                            echo '</div>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                <input type="button" id="upload_gallery_images" class="button" value="<?php echo esc_attr__('Añadir Imágenes', 'wp-booking-services'); ?>">
                                                <p class="description"><?php echo esc_html__('Máximo 5 imágenes', 'wp-booking-services'); ?></p>
                                                <div id="gallery_images_container"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="promo_video"><?php echo esc_html__('Video Promocional', 'wp-booking-services'); ?></label>
                                        </th>
                                        <td>
                                            <input name="promo_video" type="url" id="promo_video" class="regular-text code" value="<?php echo $service ? esc_attr($service->promo_video) : ''; ?>">
                                            <p class="description"><?php echo esc_html__('URL de YouTube (opcional)', 'wp-booking-services'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Categoría -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle"><?php echo esc_html__('Categoría', 'wp-booking-services'); ?></h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="category_id"><?php echo esc_html__('Seleccionar Categoría', 'wp-booking-services'); ?></label>
                                        </th>
                                        <td>
                                            <select name="category_id" id="category_id" class="regular-text">
                                                <option value=""><?php echo esc_html__('-- Seleccionar Categoría --', 'wp-booking-services'); ?></option>
                                                <?php
                                                $table_categories = $wpdb->prefix . 'wbs_categories';
                                                $categories = $wpdb->get_results("SELECT * FROM $table_categories WHERE status = 'active' ORDER BY name ASC");
                                                foreach ($categories as $category) {
                                                    $selected = ($service && $service->category_id == $category->id) ? 'selected' : '';
                                                    echo sprintf(
                                                        '<option value="%s" %s>%s</option>',
                                                        esc_attr($category->id),
                                                        $selected,
                                                        esc_html($category->name)
                                                    );
                                                }
                                                ?>
                                            </select>
                                            <p class="description"><?php echo esc_html__('Selecciona la categoría a la que pertenece este servicio', 'wp-booking-services'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Grupo de Artículos -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle"><?php echo esc_html__('Grupo de Artículos', 'wp-booking-services'); ?></h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="group_id"><?php echo esc_html__('Seleccionar Grupo de Artículos', 'wp-booking-services'); ?></label>
                                        </th>
                                        <td>
                                            <select name="group_id" id="group_id" class="regular-text">
                                                <option value=""><?php echo esc_html__('-- Seleccionar Grupo de Artículos --', 'wp-booking-services'); ?></option>
                                                <?php
                                                $table_groups = $wpdb->prefix . 'wbs_article_groups';
                                                $groups = $wpdb->get_results("SELECT * FROM $table_groups WHERE status = 'active' ORDER BY name ASC");
                                                foreach ($groups as $group) {
                                                    $selected = ($service && $service->group_id == $group->id) ? 'selected' : '';
                                                    echo sprintf(
                                                        '<option value="%s" %s>%s</option>',
                                                        esc_attr($group->id),
                                                        $selected,
                                                        esc_html($group->name)
                                                    );
                                                }
                                                ?>
                                            </select>
                                            <p class="description"><?php echo esc_html__('Selecciona el grupo de artículos asociado a este servicio', 'wp-booking-services'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Detalles del Servicio -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle"><?php echo esc_html__('Detalles del Servicio', 'wp-booking-services'); ?></h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="max_people"><?php echo esc_html__('Cantidad Máxima de Personas', 'wp-booking-services'); ?></label>
                                        </th>
                                        <td>
                                            <input name="max_people" type="number" id="max_people" class="small-text" value="<?php echo $service ? esc_attr($service->max_people) : '1'; ?>" min="1" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="price"><?php echo esc_html__('Precio', 'wp-booking-services'); ?></label>
                                        </th>
                                        <td>
                                            <div class="price-field">
                                                <span class="price-symbol">€</span>
                                                <input name="price" type="number" id="price" class="regular-text" value="<?php echo $service ? esc_attr($service->price) : '0.00'; ?>" step="0.01" min="0" required>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="service_date"><?php echo esc_html__('Fecha del Servicio', 'wp-booking-services'); ?></label>
                                        </th>
                                        <td>
                                            <input name="service_date" type="text" id="service_date" class="regular-text" value="<?php echo $service ? esc_attr($service->service_date) : ''; ?>" required>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar -->
                    <div id="postbox-container-1" class="postbox-container">
                        <div id="submitdiv" class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle"><?php echo esc_html__('Publicar', 'wp-booking-services'); ?></h2>
                            </div>
                            <div class="inside">
                                <div class="submitbox" id="submitpost">
                                    <div id="minor-publishing">
                                        <div id="misc-publishing-actions">
                                            <div class="misc-pub-section">
                                                <label for="status"><?php echo esc_html__('Estado:', 'wp-booking-services'); ?></label>
                                                <select name="status" id="status">
                                                    <option value="active" <?php selected($service ? $service->status : 'active', 'active'); ?>>
                                                        <?php echo esc_html__('Activo', 'wp-booking-services'); ?>
                                                    </option>
                                                    <option value="inactive" <?php selected($service ? $service->status : '', 'inactive'); ?>>
                                                        <?php echo esc_html__('Desactivado', 'wp-booking-services'); ?>
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="major-publishing-actions">
                                        <div id="delete-action">
                                            <?php if ($service_id > 0): ?>
                                            <a href="#" class="submitdelete deletion wbs-delete-service" data-id="<?php echo esc_attr($service_id); ?>"><?php echo esc_html__('Mover a la Papelera', 'wp-booking-services'); ?></a>
                                            <?php endif; ?>
                                        </div>
                                        <div id="publishing-action">
                                            <input type="submit" name="submit" id="publish" class="button button-primary button-large" value="<?php echo esc_attr__('Guardar Servicio', 'wp-booking-services'); ?>">
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Inicializar datepicker
            $('#service_date').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
            
            // Selector de imagen destacada
            $('#upload_featured_image').click(function(e) {
                e.preventDefault();
                
                var image_frame;
                if(image_frame) {
                    image_frame.open();
                    return;
                }
                
                image_frame = wp.media({
                    title: '<?php echo esc_js(__('Seleccionar Imagen Destacada', 'wp-booking-services')); ?>',
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                image_frame.on('select', function() {
                    var attachment = image_frame.state().get('selection').first().toJSON();
                    $('#main_image_url').val(attachment.url);
                    $('.featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; max-height: 200px;">')
                    $('#remove_featured_image').show();
                });
                
                image_frame.open();
            });
            
            // Eliminar imagen destacada
            $('#remove_featured_image').click(function(e) {
                e.preventDefault();
                $('#main_image_url').val('');
                $('.featured-image-preview').html('');
                $(this).hide();
            });
            
            // Selector de galería
            $('#upload_gallery_images').click(function(e) {
                e.preventDefault();
                
                var gallery_frame;
                if(gallery_frame) {
                    gallery_frame.open();
                    return;
                }
                
                gallery_frame = wp.media({
                    title: '<?php echo esc_js(__('Añadir Imágenes a la Galería', 'wp-booking-services')); ?>',
                    multiple: true,
                    library: {
                        type: 'image'
                    }
                });
                
                gallery_frame.on('select', function() {
                    var selection = gallery_frame.state().get('selection');
                    var attachments = [];
                    
                    selection.map(function(attachment) {
                        attachment = attachment.toJSON();
                        attachments.push(attachment);
                        
                        // Añadir campo oculto para cada imagen
                        $('#gallery_images_container').append('<input type="hidden" name="gallery_images[]" value="' + attachment.url + '">')
                        
                        // Mostrar vista previa
                        $('.gallery-preview').append(
                            '<div class="gallery-item">'+ 
                            '<img src="' + attachment.url + '" style="max-width: 100px; max-height: 100px;">'+ 
                            '<a href="#" class="remove-gallery-image" data-url="' + attachment.url + '">'+ 
                            '<?php echo esc_js(__('Eliminar', 'wp-booking-services')); ?>'+ 
                            '</a></div>'
                        );
                    });
                });
                
                gallery_frame.open();
            });
            
            // Eliminar imagen de la galería
            $(document).on('click', '.remove-gallery-image', function(e) {
                e.preventDefault();
                var item = $(this).parent();
                var url = $(this).data('url');
                
                // Eliminar campo oculto
                if (url) {
                    $('#gallery_images_container input[value="' + url + '"]').remove();
                }
                
                // Eliminar vista previa
                item.remove();
            });
        });
        </script>
    </div>    <?php
}