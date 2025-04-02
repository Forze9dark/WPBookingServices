<?php
if (!defined('ABSPATH')) {
    exit;
}

function wbs_categories_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wbs_categories';

    // Procesar el formulario de creación/edición
    if (isset($_POST['submit_category'])) {
        $name = sanitize_text_field($_POST['category_name']);
        $description = sanitize_textarea_field($_POST['category_description']);
        
        if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
            // Actualizar categoría existente
            $wpdb->update(
                $table_name,
                array(
                    'name' => $name,
                    'description' => $description
                ),
                array('id' => $_POST['category_id']),
                array('%s', '%s'),
                array('%d')
            );
            echo '<div class="notice notice-success"><p>' . __('Categoría actualizada correctamente.', 'wp-booking-services') . '</p></div>';
        } else {
            // Insertar nueva categoría
            $wpdb->insert(
                $table_name,
                array(
                    'name' => $name,
                    'description' => $description,
                    'status' => 'active'
                ),
                array('%s', '%s', '%s')
            );
            echo '<div class="notice notice-success"><p>' . __('Categoría creada correctamente.', 'wp-booking-services') . '</p></div>';
        }
    }

    // Eliminar categoría
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $wpdb->delete($table_name, array('id' => $_GET['id']), array('%d'));
        echo '<div class="notice notice-success"><p>' . __('Categoría eliminada correctamente.', 'wp-booking-services') . '</p></div>';
    }

    // Obtener categoría para editar
    $category_to_edit = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $category_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_GET['id']));
    }

    // Mostrar el formulario
    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">' . __('Categorías', 'wp-booking-services') . '</h1>';
    echo '<hr class="wp-header-end">';
    
    // Formulario de categoría
    echo '<div class="card" style="max-width: 600px; margin-bottom: 20px;">';
    echo '<form method="post" action="">';
    if ($category_to_edit) {
        echo '<input type="hidden" name="category_id" value="' . esc_attr($category_to_edit->id) . '">';
    }
    echo '<h2>' . ($category_to_edit ? __('Editar Categoría', 'wp-booking-services') : __('Añadir Nueva Categoría', 'wp-booking-services')) . '</h2>';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="category_name">' . __('Nombre', 'wp-booking-services') . '</label></th>';
    echo '<td><input type="text" id="category_name" name="category_name" class="regular-text" value="' . ($category_to_edit ? esc_attr($category_to_edit->name) : '') . '" required></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="category_description">' . __('Descripción', 'wp-booking-services') . '</label></th>';
    echo '<td><textarea id="category_description" name="category_description" class="large-text" rows="5">' . ($category_to_edit ? esc_textarea($category_to_edit->description) : '') . '</textarea></td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="submit_category" class="button button-primary" value="' . ($category_to_edit ? __('Actualizar Categoría', 'wp-booking-services') : __('Añadir Categoría', 'wp-booking-services')) . '">';
    echo '</p>';
    echo '</form>';
    echo '</div>';

    // Listar categorías existentes
    $categories = $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");
    
    echo '<h2>' . __('Categorías Existentes', 'wp-booking-services') . '</h2>';
    if ($categories) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Nombre', 'wp-booking-services') . '</th>';
        echo '<th>' . __('Descripción', 'wp-booking-services') . '</th>';
        echo '<th>' . __('Estado', 'wp-booking-services') . '</th>';
        echo '<th>' . __('Acciones', 'wp-booking-services') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($categories as $category) {
            echo '<tr>';
            echo '<td>' . esc_html($category->name) . '</td>';
            echo '<td>' . esc_html($category->description) . '</td>';
            echo '<td>' . esc_html($category->status) . '</td>';
            echo '<td>';
            echo '<a href="?page=wbs-categories&action=edit&id=' . $category->id . '" class="button button-small">' . __('Editar', 'wp-booking-services') . '</a> ';
            echo '<a href="?page=wbs-categories&action=delete&id=' . $category->id . '" class="button button-small" onclick="return confirm(\'¿Estás seguro de que deseas eliminar esta categoría?\');">' . __('Eliminar', 'wp-booking-services') . '</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>' . __('No hay categorías disponibles.', 'wp-booking-services') . '</p>';
    }
    
    echo '</div>';
}