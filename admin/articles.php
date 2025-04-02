<?php
if (!defined('ABSPATH')) {
    exit;
}

// Registrar estilos de administración
function wbs_articles_admin_styles() {
    wp_enqueue_style('dashicons');
    wp_enqueue_style('wbs-admin-styles', plugin_dir_url(__FILE__) . '../assets/css/admin-style.css', array(), '1.0.0');
}
add_action('admin_enqueue_scripts', 'wbs_articles_admin_styles');

// Página principal de artículos
function wbs_articles_page() {
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'groups';

    switch ($action) {
        case 'add':
        case 'edit':
            if ($type === 'groups') {
                wbs_article_group_form();
            } else {
                wbs_article_form();
            }
            break;
        default:
            if ($type === 'groups') {
                wbs_article_groups_list();
            } else {
                wbs_articles_list();
            }
            break;
    }
}

// Mostrar lista de grupos de artículos
function wbs_article_groups_list() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wbs_article_groups';
    $table_articles = $wpdb->prefix . 'wbs_articles';
    $groups = $wpdb->get_results("SELECT g.*, COUNT(a.id) as article_count 
        FROM $table_name g 
        LEFT JOIN $table_articles a ON g.id = a.group_id 
        GROUP BY g.id 
        ORDER BY g.created_at DESC");
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html__('Grupos de Artículos', 'wp-booking-services'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-articles&action=add&type=groups')); ?>" class="page-title-action">
            <?php echo esc_html__('Añadir Nuevo Grupo', 'wp-booking-services'); ?>
        </a>
        <hr class="wp-header-end">
        
        <?php if (empty($groups)): ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('No hay grupos de artículos disponibles. ¡Añade uno nuevo!', 'wp-booking-services'); ?></p>
        </div>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th scope="col"><?php echo esc_html__('Nombre', 'wp-booking-services'); ?></th>
                    <th scope="col"><?php echo esc_html__('Descripción', 'wp-booking-services'); ?></th>
                    <th scope="col"><?php echo esc_html__('Estado', 'wp-booking-services'); ?></th>
                    <th scope="col"><?php echo esc_html__('Artículos', 'wp-booking-services'); ?></th>
                    <th scope="col"><?php echo esc_html__('Acciones', 'wp-booking-services'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groups as $group): ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-articles&type=articles&group_id=' . $group->id)); ?>">
                                    <?php echo esc_html($group->name); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo esc_html($group->description); ?></td>
                        <td>
                            <?php if ($group->status === 'active'): ?>
                                <span class="status-active"><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html__('Activo', 'wp-booking-services'); ?></span>
                            <?php else: ?>
                                <span class="status-inactive"><span class="dashicons dashicons-marker"></span> <?php echo esc_html__('Inactivo', 'wp-booking-services'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-articles&action=edit&type=groups&id=' . $group->id)); ?>" class="button button-small">
                                <span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Editar', 'wp-booking-services'); ?>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-articles&type=articles&group_id=' . $group->id)); ?>" class="button button-small">
                                <span class="dashicons dashicons-list-view"></span> <?php echo esc_html__('Ver Artículos', 'wp-booking-services'); ?>
                            </a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wbs_delete_article_group&id=' . $group->id), 'delete_article_group_' . $group->id); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js(__('¿Estás seguro de que deseas eliminar este grupo? Esta acción no se puede deshacer.', 'wp-booking-services')); ?>')">
                                <span class="dashicons dashicons-trash"></span> <?php echo esc_html__('Eliminar', 'wp-booking-services'); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo esc_html($group->article_count); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}

// Formulario para añadir/editar grupo de artículos
function wbs_article_group_form() {
    global $wpdb;
    $group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $group = null;

    if ($group_id > 0) {
        $table_name = $wpdb->prefix . 'wbs_article_groups';
        $group = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $group_id));
    }
    ?>
    <div class="wrap">
        <h1><?php echo $group_id ? esc_html__('Editar Grupo de Artículos', 'wp-booking-services') : esc_html__('Añadir Nuevo Grupo de Artículos', 'wp-booking-services'); ?></h1>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('wbs_article_group_nonce', 'wbs_article_group_nonce'); ?>
            <input type="hidden" name="action" value="wbs_save_article_group">
            <input type="hidden" name="group_id" value="<?php echo esc_attr($group_id); ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="name"><?php echo esc_html__('Nombre', 'wp-booking-services'); ?></label>
                    </th>
                    <td>
                        <input name="name" type="text" id="name" value="<?php echo $group ? esc_attr($group->name) : ''; ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="description"><?php echo esc_html__('Descripción', 'wp-booking-services'); ?></label>
                    </th>
                    <td>
                        <textarea name="description" id="description" class="large-text" rows="5"><?php echo $group ? esc_textarea($group->description) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="status"><?php echo esc_html__('Estado', 'wp-booking-services'); ?></label>
                    </th>
                    <td>
                        <select name="status" id="status">
                            <option value="active" <?php echo ($group && $group->status === 'active') ? 'selected' : ''; ?>>
                                <?php echo esc_html__('Activo', 'wp-booking-services'); ?>
                            </option>
                            <option value="inactive" <?php echo ($group && $group->status === 'inactive') ? 'selected' : ''; ?>>
                                <?php echo esc_html__('Inactivo', 'wp-booking-services'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Guardar grupo de artículos
function wbs_save_article_group() {
    if (isset($_POST['wbs_article_group_nonce']) && wp_verify_nonce($_POST['wbs_article_group_nonce'], 'wbs_article_group_nonce')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wbs_article_groups';
        
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        if ($group_id > 0) {
            $wpdb->update($table_name, $data, array('id' => $group_id));
        } else {
            $wpdb->insert($table_name, $data);
            $group_id = $wpdb->insert_id;
        }
        
        wp_redirect(admin_url('admin.php?page=wbs-articles&type=groups&message=1'));
        exit;
    }
}
add_action('admin_post_wbs_save_article_group', 'wbs_save_article_group');

// Eliminar grupo de artículos
function wbs_delete_article_group() {
    if (isset($_GET['id']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'delete_article_group_' . $_GET['id'])) {
        global $wpdb;
        $group_id = intval($_GET['id']);
        
        // Primero eliminamos los artículos asociados
        $articles_table = $wpdb->prefix . 'wbs_articles';
        $wpdb->delete($articles_table, array('group_id' => $group_id));
        
        // Luego eliminamos el grupo
        $groups_table = $wpdb->prefix . 'wbs_article_groups';
        $wpdb->delete($groups_table, array('id' => $group_id));
        
        wp_redirect(admin_url('admin.php?page=wbs-articles&type=groups&message=2'));
        exit;
    }
    wp_die(__('No tienes permiso para realizar esta acción.', 'wp-booking-services'));
}
add_action('admin_post_wbs_delete_article_group', 'wbs_delete_article_group');

// Eliminar artículo individual
function wbs_delete_article() {
    if (isset($_GET['id']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'delete_article_' . $_GET['id'])) {
        global $wpdb;
        $article_id = intval($_GET['id']);
        $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
        
        // Eliminamos el artículo
        $articles_table = $wpdb->prefix . 'wbs_articles';
        $wpdb->delete($articles_table, array('id' => $article_id));
        
        wp_redirect(admin_url('admin.php?page=wbs-articles&type=articles&group_id=' . $group_id . '&message=2'));
        exit;
    }
    wp_die(__('No tienes permiso para realizar esta acción.', 'wp-booking-services'));
}
add_action('admin_post_wbs_delete_article', 'wbs_delete_article');

// Mostrar lista de artículos
function wbs_articles_list() {
    global $wpdb;
    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
    
    if ($group_id === 0) {
        wp_redirect(admin_url('admin.php?page=wbs-articles&type=groups'));
        exit;
    }
    
    $table_articles = $wpdb->prefix . 'wbs_articles';
    $table_groups = $wpdb->prefix . 'wbs_article_groups';
    $group = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_groups WHERE id = %d", $group_id));
    $articles = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_articles WHERE group_id = %d ORDER BY created_at DESC", $group_id));
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo sprintf(esc_html__('Artículos en %s', 'wp-booking-services'), esc_html($group->name)); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-articles&action=add&type=articles&group_id=' . $group_id)); ?>" class="page-title-action">
            <?php echo esc_html__('Añadir Nuevo Artículo', 'wp-booking-services'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-articles&type=groups')); ?>" class="page-title-action">
            <?php echo esc_html__('← Volver a Grupos', 'wp-booking-services'); ?>
        </a>
        <hr class="wp-header-end">
        
        <?php if (empty($articles)): ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('No hay artículos disponibles en este grupo. ¡Añade uno nuevo!', 'wp-booking-services'); ?></p>
        </div>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th scope="col"><?php echo esc_html__('Nombre', 'wp-booking-services'); ?></th>
                    <th scope="col"><?php echo esc_html__('Descripción', 'wp-booking-services'); ?></th>
                    <th scope="col"><?php echo esc_html__('Precio', 'wp-booking-services'); ?></th>
                    <th scope="col"><?php echo esc_html__('Estado', 'wp-booking-services'); ?></th>
                    <th scope="col"><?php echo esc_html__('Acciones', 'wp-booking-services'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-articles&action=edit&type=articles&id=' . $article->id)); ?>">
                                    <?php echo esc_html($article->name); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo esc_html($article->description); ?></td>
                        <td>RD$ <?php echo esc_html(number_format($article->price, 2)); ?></td>
                        <td>
                            <?php if ($article->status === 'active'): ?>
                                <span class="status-active"><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html__('Activo', 'wp-booking-services'); ?></span>
                            <?php else: ?>
                                <span class="status-inactive"><span class="dashicons dashicons-marker"></span> <?php echo esc_html__('Inactivo', 'wp-booking-services'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wbs-articles&action=edit&type=articles&id=' . $article->id)); ?>" class="button button-small">
                                <span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Editar', 'wp-booking-services'); ?>
                            </a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wbs_delete_article&id=' . $article->id . '&group_id=' . $group_id), 'delete_article_' . $article->id); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js(__('¿Estás seguro de que deseas eliminar este artículo? Esta acción no se puede deshacer.', 'wp-booking-services')); ?>')">
                                <span class="dashicons dashicons-trash"></span> <?php echo esc_html__('Eliminar', 'wp-booking-services'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}

// Formulario para añadir/editar artículo
function wbs_article_form() {
    global $wpdb;
    $article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
    $article = null;

    if ($article_id > 0) {
        $table_name = $wpdb->prefix . 'wbs_articles';
        $article = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $article_id));
        $group_id = $article->group_id;
    }

    if ($group_id === 0) {
        wp_redirect(admin_url('admin.php?page=wbs-articles&type=groups'));
        exit;
    }
    ?>
    <div class="wrap">
        <h1><?php echo $article_id ? esc_html__('Editar Artículo', 'wp-booking-services') : esc_html__('Añadir Nuevo Artículo', 'wp-booking-services'); ?></h1>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('wbs_article_nonce', 'wbs_article_nonce'); ?>
            <input type="hidden" name="action" value="wbs_save_article">
            <input type="hidden" name="article_id" value="<?php echo esc_attr($article_id); ?>">
            <input type="hidden" name="group_id" value="<?php echo esc_attr($group_id); ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="name"><?php echo esc_html__('Nombre', 'wp-booking-services'); ?></label>
                    </th>
                    <td>
                        <input name="name" type="text" id="name" value="<?php echo $article ? esc_attr($article->name) : ''; ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="description"><?php echo esc_html__('Descripción', 'wp-booking-services'); ?></label>
                    </th>
                    <td>
                        <textarea name="description" id="description" class="large-text" rows="5"><?php echo $article ? esc_textarea($article->description) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="price"><?php echo esc_html__('Precio', 'wp-booking-services'); ?></label>
                    </th>
                    <td>
                        <input name="price" type="number" id="price" value="<?php echo $article ? esc_attr($article->price) : ''; ?>" class="regular-text" step="0.01" min="0" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="status"><?php echo esc_html__('Estado', 'wp-booking-services'); ?></label>
                    </th>
                    <td>
                        <select name="status" id="status">
                            <option value="active" <?php echo ($article && $article->status === 'active') ? 'selected' : ''; ?>>
                                <?php echo esc_html__('Activo', 'wp-booking-services'); ?>
                            </option>
                            <option value="inactive" <?php echo ($article && $article->status === 'inactive') ? 'selected' : ''; ?>>
                                <?php echo esc_html__('Inactivo', 'wp-booking-services'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Guardar artículo
function wbs_save_article() {
    if (isset($_POST['wbs_article_nonce']) && wp_verify_nonce($_POST['wbs_article_nonce'], 'wbs_article_nonce')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wbs_articles';
        
        $article_id = isset($_POST['article_id']) ? intval($_POST['article_id']) : 0;
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        
        if ($group_id === 0) {
            wp_redirect(admin_url('admin.php?page=wbs-articles&type=groups'));
            exit;
        }
        
        $data = array(
            'group_id' => $group_id,
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'price' => floatval($_POST['price']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        if ($article_id > 0) {
            $wpdb->update($table_name, $data, array('id' => $article_id));
        } else {
            $wpdb->insert($table_name, $data);
            $article_id = $wpdb->insert_id;
        }
        
        wp_redirect(admin_url('admin.php?page=wbs-articles&type=articles&group_id=' . $group_id . '&message=1'));
        exit;
    }
}
add_action('admin_post_wbs_save_article', 'wbs_save_article');