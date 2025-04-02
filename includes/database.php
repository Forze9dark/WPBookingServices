<?php
if (!defined('ABSPATH')) {
    exit;
}
function wbs_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de grupos de artículos
    $table_article_groups = $wpdb->prefix . 'wbs_article_groups';
    $sql_article_groups = "CREATE TABLE IF NOT EXISTS $table_article_groups (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        status varchar(20) NOT NULL DEFAULT 'active',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Tabla de categorías
    $table_categories = $wpdb->prefix . 'wbs_categories';
    $sql_categories = "CREATE TABLE IF NOT EXISTS $table_categories (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        status varchar(20) NOT NULL DEFAULT 'active',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Tabla de servicios
    $table_services = $wpdb->prefix . 'wbs_services';
    $sql_services = "CREATE TABLE IF NOT EXISTS $table_services (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description longtext NOT NULL,
        main_image varchar(255) NOT NULL,
        promo_video varchar(255),
        max_people int NOT NULL,
        price decimal(10,2) NOT NULL,
        service_date date NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        category_id bigint(20),
        group_id bigint(20),
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Añadir relación con grupos si no existe
    $sql_add_group_relation = "ALTER TABLE $table_services
        ADD FOREIGN KEY (group_id) REFERENCES $table_article_groups(id) ON DELETE SET NULL;";

    // Tabla de galería de fotos
    $table_gallery = $wpdb->prefix . 'wbs_service_gallery';
    $sql_gallery = "CREATE TABLE IF NOT EXISTS $table_gallery (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        service_id bigint(20) NOT NULL,
        image_url varchar(255) NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (service_id) REFERENCES $table_services(id) ON DELETE CASCADE
    ) $charset_collate;";

    // Tabla de artículos
    $table_articles = $wpdb->prefix . 'wbs_articles';
    $sql_articles = "CREATE TABLE IF NOT EXISTS $table_articles (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        group_id bigint(20) NOT NULL,
        name varchar(255) NOT NULL,
        description text,
        price decimal(10,2) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (group_id) REFERENCES $table_article_groups(id) ON DELETE CASCADE
    ) $charset_collate;";

    // Añadir relación con categorías si no existe
    $sql_add_category_relation = "ALTER TABLE $table_services
        ADD FOREIGN KEY (category_id) REFERENCES $table_categories(id) ON DELETE SET NULL;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_article_groups);
    dbDelta($sql_categories);
    dbDelta($sql_services);
    dbDelta($sql_gallery);
    dbDelta($sql_articles);
    $wpdb->query($sql_add_category_relation);
    $wpdb->query($sql_add_group_relation);
}