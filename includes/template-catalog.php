<?php
/*
Template Name: Catálogo Servicios Template
*/
if (!defined('ABSPATH')) {
    exit;
}

// Mantener algunas funcionalidades esenciales de WordPress
remove_all_actions('wp_head');
add_action('wp_head', 'wp_enqueue_scripts', 1);
add_action('wp_head', '_wp_render_title_tag', 1);
add_action('wp_head', 'wp_resource_hints', 2);
add_action('wp_head', 'feed_links', 2);
add_action('wp_head', 'feed_links_extra', 3);

// Obtener los servicios de la base de datos
global $wpdb;
$services_table = $wpdb->prefix . 'wbs_services';
$categories_table = $wpdb->prefix . 'wbs_categories';
$gallery_table = $wpdb->prefix . 'wbs_service_gallery';
$article_groups_table = $wpdb->prefix . 'wbs_article_groups';
$articles_table = $wpdb->prefix . 'wbs_articles';
$discounts_table = $wpdb->prefix . 'wbs_discounts';
$service_discounts_table = $wpdb->prefix . 'wbs_service_discounts';

$services = $wpdb->get_results(
    "SELECT s.*, c.name as category_name, ag.id as article_group_id,
    COALESCE(s.main_image, (SELECT image_url FROM {$gallery_table} WHERE service_id = s.id LIMIT 1)) as main_image,
    (SELECT COUNT(*) FROM {$articles_table} a WHERE a.group_id = ag.id) as article_count
    FROM {$services_table} s
    LEFT JOIN {$categories_table} c ON s.category_id = c.id
    LEFT JOIN {$article_groups_table} ag ON s.group_id = ag.id
    WHERE s.status = 'active'"
);

// Obtener las imágenes de la galería para cada servicio (máximo 5 por servicio)
$service_galleries = array();
foreach ($services as $service) {
    $gallery_images = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$gallery_table} WHERE service_id = %d LIMIT 5",
        $service->id
    ));
    $service_galleries[$service->id] = $gallery_images;
}

// Obtener los descuentos para cada servicio
$service_discounts = array();
foreach ($services as $service) {
    $discounts = $wpdb->get_results($wpdb->prepare(
        "SELECT d.* FROM {$discounts_table} d
        JOIN {$service_discounts_table} sd ON d.id = sd.discount_id
        WHERE sd.service_id = %d AND d.status = 'active'",
        $service->id
    ));
    $service_discounts[$service->id] = $discounts;
}

$categories = $wpdb->get_results("SELECT * FROM {$categories_table}");
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff385c;
            --secondary-color: #f7f7f7;
            --text-color: #222222;
            --light-text: #717171;
            --border-radius: 16px;
            --card-shadow: 0 6px 20px rgba(0,0,0,0.1);
            --transition: all 0.2s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fff;
            color: var(--text-color);
            line-height: 1.6;
            letter-spacing: -0.2px;
        }

        .catalog-header {
            padding-top: 3.1rem !important;
            background: white;
            padding: 1.2rem 0;
            box-shadow: 0 1px 0 rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo-container svg {
            height: 32px;
            color: var(--primary-color);
        }

        .logo-text {
            font-weight: 700;
            font-size: 1.4rem;
            margin-left: 0.5rem;
            background: linear-gradient(90deg, #ff385c 0%, #e61e4d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .search-bar {
            display: flex;
            align-items: center;
            border: 1px solid #dddddd;
            border-radius: 40px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.05);
            transition: box-shadow 0.2s;
            overflow: hidden;
            height: 48px;
        }

        .search-bar:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.18);
        }

        .search-input {
            border: none;
            padding: 0 1rem;
            height: 100%;
            flex-grow: 1;
            font-size: 0.95rem;
        }

        .search-input:focus {
            outline: none;
        }

        .search-button {
            background: linear-gradient(90deg, #ff385c 0%, #e61e4d 100%);
            border: none;
            color: white;
            height: 100%;
            padding: 0 1.5rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-filters {
            background-color: white;
            padding: 1.2rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.04);
            margin-bottom: 2rem;
        }

        .filter-scroll {
            display: flex;
            overflow-x: auto;
            gap: 2.5rem;
            padding: 0.5rem 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .filter-scroll::-webkit-scrollbar {
            display: none;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--light-text);
            text-decoration: none;
            min-width: 56px;
            transition: var(--transition);
            opacity: 0.7;
        }

        .filter-item.active {
            color: var(--primary-color);
            opacity: 1;
        }

        .filter-item:hover {
            color: var(--primary-color);
            opacity: 1;
        }

        .filter-item i {
            font-size: 22px;
            margin-bottom: 6px;
        }

        .filter-item span {
            font-size: 12px;
            white-space: nowrap;
            font-weight: 500;
        }

        .service-card {
            background-color: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
            height: 100%;
            border: none;
            box-shadow: 0 0 0 1px rgba(0,0,0,0.04), 0 2px 4px rgba(0,0,0,0.04);
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-shadow);
        }

        .service-image-container {
            position: relative;
            overflow: hidden;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .service-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .service-card:hover .service-image {
            transform: scale(1.05);
        }

        .favorite-button {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            z-index: 2;
        }

        .favorite-button:hover {
            background-color: white;
            transform: scale(1.1);
        }

        .favorite-button i {
            color: #484848;
            font-size: 16px;
        }

        .favorite-button:hover i {
            color: var(--primary-color);
        }

        .service-info {
            padding: 1.2rem 1rem;
        }

        .service-title {
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .service-category {
            color: var(--light-text);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .service-description {
            font-size: 0.9rem;
            line-height: 1.4;
            color: var(--light-text);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .service-price {
            font-weight: 600;
            color: var(--text-color);
            font-size: 1.05rem;
        }

        .price-period {
            font-size: 0.85rem;
            color: var(--light-text);
            font-weight: 400;
        }

        .btn-reserve {
            background: linear-gradient(90deg, #ff385c 0%, #e61e4d 100%);
            color: white;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .btn-reserve:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(230, 30, 77, 0.3);
            color: white;
        }

        .modal-content {
            border-radius: 16px;
        }

        .modal-header {
            background: linear-gradient(90deg, #ff385c 0%, #e61e4d 100%);
            border-radius: 16px 16px 0 0 !important;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #dddddd;
            padding: 0.8rem 1rem;
            transition: var(--transition);
        }
        
        /* Ajuste para los selectores numéricos */
        input[type="number"].form-control {
            padding: 0.8rem 0.5rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #b0b0b0;
            box-shadow: 0 0 0 2px rgba(255, 56, 92, 0.2);
        }
        
        /* Estilos para el checkout paso a paso */
        .checkout-progress {
            margin-bottom: 2rem;
        }
        
        .step {
            text-align: center;
            position: relative;
            width: 20%;
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            color: #717171;
            transition: all 0.3s ease;
        }
        
        .step.active .step-icon {
            background: linear-gradient(90deg, #ff385c 0%, #e61e4d 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(230, 30, 77, 0.3);
        }
        
        .step.completed .step-icon {
            background-color: #28a745;
            color: white;
        }
        
        .step-label {
            font-size: 0.8rem;
            color: #717171;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .checkout-step {
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .extras-container .card {
            border-radius: 10px;
            transition: all 0.2s ease;
            border: 1px solid #eaeaea;
        }
        
        .extras-container .card:hover {
            border-color: #d0d0d0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        /* Estilos para el carrusel de la galería */
        #tourGalleryCarousel {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        #tourGalleryCarousel .carousel-item {
            transition: transform 0.6s ease-in-out;
        }
        
        #tourGalleryCarousel .carousel-control-prev,
        #tourGalleryCarousel .carousel-control-next {
            width: 10%;
            opacity: 0.7;
            background: rgba(0,0,0,0.2);
            border-radius: 0;
            height: 100%;
        }
        
        #tourGalleryCarousel .carousel-control-prev:hover,
        #tourGalleryCarousel .carousel-control-next:hover {
            opacity: 1;
            background: rgba(0,0,0,0.3);
        }
        
        #tourGalleryCarousel .carousel-indicators {
            margin-bottom: 0.5rem;
        }
        
        #tourGalleryCarousel .carousel-indicators button {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.7);
            border: none;
            margin: 0 3px;
        }
        
        #tourGalleryCarousel .carousel-indicators button.active {
            background-color: white;
            transform: scale(1.2);
        }

        @media (max-width: 768px) {
            .service-card {
                margin-bottom: 1.5rem;
            }
            
            .search-bar {
                width: 100%;
                margin-top: 1rem;
            }
            
            #tourGalleryCarousel {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="catalog-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="logo-container">
                    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="presentation" focusable="false">
                        <path d="M16 1c2.008 0 3.463.963 4.751 3.269l.533 1.025c1.954 3.83 6.114 12.54 7.1 14.836l.145.353c.667 1.591.91 2.472.96 3.396l.01.415.001.228c0 4.062-2.877 6.478-6.357 6.478-2.224 0-4.556-1.258-6.709-3.386l-.257-.26-.172-.179h-.011l-.176.185c-2.044 2.1-4.267 3.42-6.414 3.615l-.28.019-.267.006C5.377 31 2.5 28.584 2.5 24.522l.005-.469c.026-.928.23-1.768.83-3.244l.216-.524c.966-2.298 6.083-12.989 7.707-16.034C12.537 1.963 13.992 1 16 1zm0 2c-1.239 0-2.053.539-2.987 2.21l-.523 1.008c-1.926 3.776-6.06 12.43-7.031 14.692l-.345.836c-.427 1.071-.573 1.655-.605 2.24l-.009.33v.206c0 2.329 1.607 4.39 4.5 4.39.897 0 1.942-.725 3.036-1.677l.322-.292.339-.302.38-.324.359-.298c.306-.25.664-.538 1.064-.825l.326-.237.345-.242.208-.144c1.1-.76 2.162-1.348 2.598-1.54l.347-.673c-.566.182-1.156.57-1.856 1.044l-.354.243-.504.35-.349.242-.852.585-.34.232-.75.49-.668.424c-1.192.757-2.805 1.615-3.875 1.615-1.968 0-2.5-1.487-2.5-2.39 0-.236.046-.469.138-.694l.412-.913c.966-2.243 5.493-11.61 6.925-14.516l.248-.513c.683-1.414 1.209-1.98 2.053-1.98 1.246 0 2.253 1.2 3.96 4.5l.623 1.248c1.688 3.373 4.913 9.848 6.027 12.085l.19.387.18.367.106.213c.473.95.578 1.519.631 2.174l.013.349.004.203c0 2.329-1.607 4.39-4.5 4.39-1.009 0-2.015-.37-3.077-1.022l-.334-.207-.416-.28-.457-.324-.323-.24-.339-.25-.346-.256-.33-.243c-.362-.265-.703-.505-1.003-.71l-.255-.176-.615-.423-.47-.321-.431-.291c-.527-.35-1.3-.74-1.955-1.083l.076-.147c.798.435 1.436.772 1.96 1.107l.398.267.35.242.402.273c.321.216.669.448 1.046.693l.324.213.346.227.317.208c.922.607 1.862 1.051 2.525 1.051 1.968 0 2.5-1.487 2.5-2.39 0-.287-.07-.57-.2-.834l-.13-.251-.302-.61c-.977-1.952-4.337-8.693-5.617-11.256l-.541-1.084c-1.069-2.146-1.788-2.875-2.78-2.875z" fill="currentColor"></path>
                    </svg>
                    <span class="logo-text">Booking</span>
                </div>
                <div class="search-bar mt-md-0 mt-2 col-12 col-md-6">
                    <input type="text" id="search-input" class="search-input" placeholder="Buscar servicios...">
                    <button class="search-button" type="button">
                        <i class="fas fa-search"></i>
                        <span class="ms-2 d-none d-md-inline">Buscar</span>
                    </button>
                </div>
                <div class="d-none d-md-block">
                    <select id="sort-filter" class="form-select border-0 bg-light">
                        <option value="name-asc">Nombre (A-Z)</option>
                        <option value="name-desc">Nombre (Z-A)</option>
                        <option value="price-asc">Precio (Menor a Mayor)</option>
                        <option value="price-desc">Precio (Mayor a Menor)</option>
                    </select>
                </div>
            </div>
        </div>
    </header>

    <div class="search-filters">
        <div class="container">
            <div class="filter-scroll">
                <a href="#" class="filter-item active" data-category="">
                    <i class="fas fa-border-all"></i>
                    <span>Todos</span>
                </a>
                <?php foreach ($categories as $category): ?>
                    <a href="#" class="filter-item" data-category="<?php echo esc_attr($category->id); ?>">
                        <i class="<?php echo esc_attr($category->icon ?? 'fas fa-tag'); ?>"></i>
                        <span><?php echo esc_html($category->name); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row" id="services-grid">
            <?php foreach ($services as $service): ?>
                <div class="col-lg-4 col-md-6 mb-4 service-item" 
                     data-name="<?php echo esc_attr(strtolower($service->title)); ?>"
                     data-category="<?php echo esc_attr($service->category_id); ?>"
                     data-price="<?php echo esc_attr($service->price); ?>">
                    <div class="service-card">
                        <div class="service-image-container">
                            <img src="<?php echo esc_url($service->main_image ? $service->main_image : plugins_url('assets/images/default-service.svg', dirname(__FILE__))); ?>" 
                                 alt="<?php echo esc_attr($service->title); ?>" 
                                 class="service-image">
                            <button class="favorite-button" title="Añadir a favoritos">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <div class="service-info">
                            <div class="service-category mb-1">
                                <i class="fas fa-tag"></i>
                                <span><?php echo esc_html($service->category_name); ?></span>
                            </div>
                            <h3 class="service-title"><?php echo esc_html($service->title); ?></h3>
                            <p class="service-description mb-3"><?php echo wp_trim_words($service->description, 15); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="service-price">$<?php echo number_format($service->price, 2); ?></span>
                                    <span class="price-period">/servicio</span>
                                </div>
                                <button class="btn btn-reserve" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#bookingModal"
                                        data-service-id="<?php echo esc_attr($service->id); ?>"
                                        data-service-name="<?php echo esc_attr($service->title); ?>"
                                        data-service-price="<?php echo esc_attr($service->price); ?>"
                                        data-service-description="<?php echo esc_attr(wp_trim_words($service->description, 30)); ?>"
                                        data-service-category="<?php echo esc_attr($service->category_name); ?>"
                                        data-service-image="<?php echo esc_url($service->main_image ? $service->main_image : plugins_url('assets/images/default-service.svg', dirname(__FILE__))); ?>"
                                        data-has-articles="<?php echo $service->article_count > 0 ? 'true' : 'false'; ?>"
                                        data-article-group-id="<?php echo esc_attr($service->article_group_id); ?>"
                                        data-service-promo-video="<?php echo esc_attr($service->promo_video); ?>"
                                        data-has-gallery="<?php echo !empty($service_galleries[$service->id]) ? 'true' : 'false'; ?>">
                                    Reservar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal de Reserva con Checkout Paso a Paso -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="bookingModalLabel">Reservar Servicio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Indicador de Progreso -->
                    <div class="checkout-progress mb-4">
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar" role="progressbar" style="width: 20%;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2" id="checkout-steps">
                            <div class="step active" data-step="1">
                                <div class="step-icon"><i class="fas fa-map-marked-alt"></i></div>
                                <div class="step-label">Tour</div>
                            </div>
                            <div class="step" data-step="2">
                                <div class="step-icon"><i class="fas fa-info-circle"></i></div>
                                <div class="step-label">Detalles</div>
                            </div>
                            <div class="step" data-step="3">
                                <div class="step-icon"><i class="fas fa-user"></i></div>
                                <div class="step-label">Información</div>
                            </div>
                            <div class="step" data-step="4" id="articles-step" style="display: none;">
                                <div class="step-icon"><i class="fas fa-box"></i></div>
                                <div class="step-label">Artículos</div>
                            </div>
                            <div class="step" data-step="5">
                                <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="step-label">Confirmación</div>
                            </div>
                        </div>
                    </div>
                    
                    <form id="booking-form" class="booking-form needs-validation" novalidate>
                        <input type="hidden" id="service-id" name="service_id">
                        <input type="hidden" id="current-step" value="1">
                        
                        <!-- Paso 1: Detalles del Tour -->
                        <div class="checkout-step" id="step-1" style="display: block;">
                            <h5 class="mb-3">Detalles del Tour</h5>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <img id="tour-image" src="" alt="Imagen del tour" class="img-fluid rounded" style="object-fit: cover; height: 200px; width: 100%;">                                        
                                        </div>
                                        <div class="col-md-8">
                                            <h4 id="tour-title" class="mb-2"></h4>
                                            <div class="mb-2">
                                                <span class="badge bg-primary rounded-pill me-2" id="tour-category"></span>
                                                <span class="text-muted"><i class="fas fa-clock me-1"></i><span id="tour-duration">3 horas</span></span>
                                            </div>
                                            <p id="tour-description" class="mb-3"></p>
                                            <div id="youtube-button-container" style="display: none;" class="mb-3">
                                                <a href="#" id="youtube-promo-link" target="_blank" class="btn btn-danger">
                                                    <i class="fab fa-youtube me-2"></i> Ver Video Promocional
                                                </a>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="h5 mb-0" id="tour-price"></span>
                                                    <span class="text-muted">/persona</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="tour-date" class="form-label fw-medium mb-1">Fecha del Tour</label>
                                    <input type="date" class="form-control" id="tour-date" name="tour_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="tour-participants" class="form-label fw-medium mb-1">Número de Participantes</label>
                                    <input type="number" class="form-control" id="tour-participants" name="tour_participants" min="1" value="1" required>
                                </div>
                            </div>
                            <div class="alert alert-info" id="reservation-message">
                                <i class="fas fa-info-circle me-2"></i>
                                <span>Selecciona la fecha y el número de participantes para continuar con tu reserva.</span>
                            </div>
                            <div class="alert alert-success" id="discount-message" style="display: none;">
                                <i class="fas fa-tag me-2"></i>
                                <span id="discount-text"></span>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn btn-reserve btn-next">Continuar <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                        
                        <!-- Paso 2: Detalles Adicionales -->
                        <div class="checkout-step" id="step-2" style="display: none;">
                            <h5 class="mb-3">Detalles Adicionales</h5>
                            <div class="mb-4">
                                <label for="special-requirements" class="form-label fw-medium mb-1">Requerimientos Especiales</label>
                                <textarea class="form-control" id="special-requirements" name="special_requirements" rows="3" placeholder="Indica si tienes algún requerimiento especial (dieta, accesibilidad, etc.)"></textarea>
                            </div>
                            <div class="mb-4">
                                <h6 class="mb-3">Método de Pago</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment-transfer" value="transfer" checked>
                                    <label class="form-check-label" for="payment-transfer">
                                        <i class="fas fa-university me-2"></i>Transferencia Bancaria
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment-cash" value="cash">
                                    <label class="form-check-label" for="payment-cash">
                                        <i class="fas fa-money-bill-wave me-2"></i>Pago en Efectivo
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary btn-prev"><i class="fas fa-arrow-left me-2"></i> Atrás</button>
                                <button type="button" class="btn btn-reserve btn-next">Continuar <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                        
                        <!-- Paso 3: Información Personal -->
                        <div class="checkout-step" id="step-3" style="display: none;">
                            <h5 class="mb-3">Información Personal</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="client-name" class="form-label fw-medium mb-1">Nombre completo</label>
                                    <input type="text" class="form-control" id="client-name" name="client_name" placeholder="Tu nombre completo" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="client-email" class="form-label fw-medium mb-1">Correo electrónico</label>
                                    <input type="email" class="form-control" id="client-email" name="client_email" placeholder="tu@email.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="client-phone" class="form-label fw-medium mb-1">Teléfono</label>
                                    <input type="tel" class="form-control" id="client-phone" name="client_phone" placeholder="Tu número de teléfono" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="client-country" class="form-label fw-medium mb-1">País</label>
                                    <select class="form-select" id="client-country" name="client_country" required>
                                        <option value="">Selecciona un país</option>
                                        <option value="ES">España</option>
                                        <option value="MX">México</option>
                                        <option value="CO">Colombia</option>
                                        <option value="AR">Argentina</option>
                                        <option value="CL">Chile</option>
                                        <option value="PE">Perú</option>
                                        <option value="US">Estados Unidos</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="terms-check" name="terms_check" required>
                                <label class="form-check-label" for="terms-check">
                                    Acepto los <a href="#" class="text-decoration-none">términos y condiciones</a> y la <a href="#" class="text-decoration-none">política de privacidad</a>
                                </label>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary btn-prev"><i class="fas fa-arrow-left me-2"></i> Atrás</button>
                                <button type="button" class="btn btn-reserve btn-next">Continuar <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                        
                        <!-- Paso 4: Artículos (opcional) -->
                        <div class="checkout-step" id="step-4" style="display: none;">
                            <h5 class="mb-3">Artículos Disponibles</h5>
                            <div class="extras-container mb-4" id="articles-container">
                                <!-- Los artículos se cargarán dinámicamente aquí -->
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <span>Cargando artículos disponibles...</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary btn-prev"><i class="fas fa-arrow-left me-2"></i> Atrás</button>
                                <button type="button" class="btn btn-reserve btn-next">Continuar <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                        
                        <!-- Paso 5: Confirmación -->
                        <div class="checkout-step" id="step-5" style="display: none;">
                            <h5 class="mb-3">Confirmación de Reserva</h5>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h6 class="mb-3 border-bottom pb-2">Detalles del Servicio</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Servicio:</span>
                                        <span class="fw-medium" id="summary-service"></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Categoría:</span>
                                        <span class="fw-medium" id="summary-category"></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Fecha:</span>
                                        <span class="fw-medium" id="summary-date"></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Participantes:</span>
                                        <span class="fw-medium" id="summary-participants"></span>
                                    </div>
                                    
                                    <h6 class="mt-4 mb-3 border-bottom pb-2">Datos del Cliente</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Nombre:</span>
                                        <span class="fw-medium" id="summary-client-name"></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Email:</span>
                                        <span class="fw-medium" id="summary-client-email"></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Teléfono:</span>
                                        <span class="fw-medium" id="summary-client-phone"></span>
                                    </div>
                                    
                                    <h6 class="mt-4 mb-3 border-bottom pb-2">Método de Pago</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Forma de pago:</span>
                                        <span class="fw-medium" id="summary-payment"></span>
                                    </div>
                                    <div id="summary-payment-details" class="mb-2" style="display: none;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="ps-3">Banco:</span>
                                            <span class="fw-medium" id="summary-bank-name"></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="ps-3">Número de cuenta:</span>
                                            <span class="fw-medium" id="summary-account-number"></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="ps-3">Tipo de cuenta:</span>
                                            <span class="fw-medium" id="summary-account-type"></span>
                                        </div>
                                    </div>
                                    
                                    <h6 class="mt-4 mb-3 border-bottom pb-2">Resumen de Costos</h6>
                                    <div id="summary-subtotal-row" class="d-flex justify-content-between mb-2">
                                        <!-- Aquí se mostrará el subtotal -->
                                    </div>
                                    <div id="summary-discount-row" class="d-flex justify-content-between mb-2" style="display: none;">
                                        <!-- Aquí se mostrará el descuento -->
                                    </div>
                                    
                                    <div id="summary-articles-container" class="mb-3">
                                        <!-- Aquí se mostrarán los artículos seleccionados -->
                                    </div>
                                    
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <span class="h6">Total:</span>
                                        <span class="h6" id="summary-total"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <span>Tu reserva está lista para ser confirmada. Haz clic en el botón "Confirmar Reserva" para finalizar.</span>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary btn-prev"><i class="fas fa-arrow-left me-2"></i> Atrás</button>
                                <button type="submit" class="btn btn-reserve">Confirmar Reserva <i class="fas fa-check ms-2"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const bookingModal = document.getElementById('bookingModal');
        const modalBody = bookingModal.querySelector('.modal-body');
        
        // Inicializar el contenido del modal
        modalBody.innerHTML = `
            <!-- Indicador de Progreso -->
            <div class="checkout-progress mb-4">
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar" role="progressbar" style="width: 20%;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between mt-2" id="checkout-steps">
                    <div class="step active" data-step="1">
                        <div class="step-icon"><i class="fas fa-map-marked-alt"></i></div>
                        <div class="step-label">Tour</div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-icon"><i class="fas fa-info-circle"></i></div>
                        <div class="step-label">Detalles</div>
                    </div>
                    <div class="step" data-step="3">
                        <div class="step-icon"><i class="fas fa-user"></i></div>
                        <div class="step-label">Información</div>
                    </div>
                    <div class="step" data-step="4" id="articles-step" style="display: none;">
                        <div class="step-icon"><i class="fas fa-box"></i></div>
                        <div class="step-label">Artículos</div>
                    </div>
                    <div class="step" data-step="5">
                        <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="step-label">Confirmación</div>
                    </div>
                </div>
            </div>
            
            <form id="booking-form" class="booking-form needs-validation" novalidate>
                <input type="hidden" id="service-id" name="service_id">
                <input type="hidden" id="current-step" value="1">
                
                <!-- Paso 1: Detalles del Tour -->
                <div class="checkout-step" id="step-1" style="display: block;">
                    <h5 class="mb-3">Detalles del Tour</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <!-- Carrusel de imágenes -->
                                    <div id="tourGalleryCarousel" class="carousel slide" data-bs-ride="carousel">
                                        <div class="carousel-indicators" id="carousel-indicators"></div>
                                        <div class="carousel-inner" id="carousel-inner">
                                            <!-- Las imágenes se cargarán dinámicamente aquí -->
                                            <div class="carousel-item active">
                                                <img id="tour-image" src="" alt="Imagen del tour" class="d-block w-100 rounded" style="object-fit: cover; height: 200px;">
                                            </div>
                                        </div>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#tourGalleryCarousel" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Anterior</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#tourGalleryCarousel" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Siguiente</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <h4 id="tour-title" class="mb-2"></h4>
                                    <div class="mb-2">
                                        <span class="badge bg-primary rounded-pill me-2" id="tour-category"></span>
                                        <span class="text-muted"><i class="fas fa-calendar me-1"></i><span id="tour-date-display">Fecha del tour</span></span>
                                    </div>
                                    <p id="tour-description" class="mb-3"></p>
                                    <div id="youtube-button-container" style="display: none;" class="mb-3">
                                        <a href="#" id="youtube-promo-link" target="_blank" class="btn btn-danger">
                                            <i class="fab fa-youtube me-2"></i> Ver Video Promocional
                                        </a>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="h5 mb-0" id="tour-price"></span>
                                            <span class="text-muted">/persona</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label for="tour-participants" class="form-label fw-medium mb-1">Número de Participantes</label>
                            <input type="number" class="form-control" id="tour-participants" name="tour_participants" min="1" value="1" required>
                        </div>
                    </div>
                    <div class="alert alert-info" id="reservation-message-modal">
                        <i class="fas fa-info-circle me-2"></i>
                        <span>Selecciona la fecha y el número de participantes para continuar con tu reserva.</span>
                    </div>
                    <div class="alert alert-success" id="discount-message-modal" style="display: none;">
                        <i class="fas fa-tag me-2"></i>
                        <span id="discount-text-modal"></span>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-reserve btn-next">Continuar <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>
                
                <!-- Paso 2: Detalles Adicionales -->
                <div class="checkout-step" id="step-2" style="display: none;">
                    <h5 class="mb-3">Detalles Adicionales</h5>
                    <div class="mb-4">
                        <h6 class="mb-3">Método de Pago</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="payment-transfer" value="transfer" checked>
                            <label class="form-check-label" for="payment-transfer">
                                <i class="fas fa-university me-2"></i>Transferencia Bancaria
                            </label>
                        </div>
                        <div id="bank-transfer-details" class="mt-3 ps-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="bank-name" class="form-label fw-medium mb-1">Banco</label>
                                    <input type="text" class="form-control" id="bank-name" name="bank_name">
                                </div>
                                <div class="col-md-4">
                                    <label for="account-number" class="form-label fw-medium mb-1">Número de Cuenta</label>
                                    <input type="text" class="form-control" id="account-number" name="account_number">
                                </div>
                                <div class="col-md-4">
                                    <label for="account-type" class="form-label fw-medium mb-1">Tipo de Cuenta</label>
                                    <select class="form-select" id="account-type" name="account_type">
                                        <option value="">Seleccionar...</option>
                                        <option value="corriente">Corriente</option>
                                        <option value="ahorro">Ahorro</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="radio" name="payment_method" id="payment-cash" value="cash">
                            <label class="form-check-label" for="payment-cash">
                                <i class="fas fa-money-bill-wave me-2"></i>Pago en Efectivo
                            </label>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-prev"><i class="fas fa-arrow-left me-2"></i> Atrás</button>
                        <button type="button" class="btn btn-reserve btn-next">Continuar <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>
                
                <!-- Paso 3: Información Personal -->
                <div class="checkout-step" id="step-3" style="display: none;">
                    <h5 class="mb-3">Información Personal</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="client-name" class="form-label fw-medium mb-1">Nombre completo</label>
                            <input type="text" class="form-control" id="client-name" name="client_name" placeholder="Tu nombre completo" required>
                        </div>
                        <div class="col-md-6">
                            <label for="client-email" class="form-label fw-medium mb-1">Correo electrónico</label>
                            <input type="email" class="form-control" id="client-email" name="client_email" placeholder="tu@email.com" required>
                        </div>
                        <div class="col-md-6">
                            <label for="client-phone" class="form-label fw-medium mb-1">Teléfono</label>
                            <input type="tel" class="form-control" id="client-phone" name="client_phone" placeholder="Tu número de teléfono" required>
                        </div>
                        <div class="col-md-6">
                            <label for="client-country" class="form-label fw-medium mb-1">País</label>
                            <select class="form-select" id="client-country" name="client_country" required>
                                <option value="">Selecciona un país</option>
                                <option value="ES">España</option>
                                <option value="MX">México</option>
                                <option value="CO">Colombia</option>
                                <option value="AR">Argentina</option>
                                <option value="CL">Chile</option>
                                <option value="PE">Perú</option>
                                <option value="US">Estados Unidos</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="terms-check" name="terms_check" required>
                        <label class="form-check-label" for="terms-check">
                            Acepto los <a href="#" class="text-decoration-none">términos y condiciones</a> y la <a href="#" class="text-decoration-none">política de privacidad</a>
                        </label>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-prev"><i class="fas fa-arrow-left me-2"></i> Atrás</button>
                        <button type="button" class="btn btn-reserve btn-next">Continuar <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>
                
                <!-- Paso 4: Artículos (opcional) -->
                <div class="checkout-step" id="step-4" style="display: none;">
                    <h5 class="mb-3">Artículos Disponibles</h5>
                    <div class="extras-container mb-4" id="articles-container">
                        <!-- Los artículos se cargarán dinámicamente aquí -->
                        <div class="alert alert-info loading-articles">
                            <i class="fas fa-info-circle me-2"></i>
                            <span>Cargando artículos disponibles...</span>
                        </div>
                        <div id="articles-list" class="row g-3" style="display: none;">
                            <!-- Aquí se cargarán los artículos del grupo -->
                        </div>
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Subtotal artículos:</h6>
                            <span class="h6" id="articles-subtotal">$0.00</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-prev"><i class="fas fa-arrow-left me-2"></i> Atrás</button>
                        <button type="button" class="btn btn-reserve btn-next">Continuar <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>
                
                <!-- Paso 5: Confirmación -->
                <div class="checkout-step" id="step-5" style="display: none;">
                    <h5 class="mb-3">Confirmación de Reserva</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="mb-3">Resumen de tu reserva</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Servicio:</span>
                                <span class="fw-medium" id="summary-service"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Fecha:</span>
                                <span class="fw-medium" id="summary-date"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Participantes:</span>
                                <span class="fw-medium" id="summary-participants"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Método de pago:</span>
                                <span class="fw-medium" id="summary-payment"></span>
                            </div>
                            <div id="summary-subtotal-row" class="d-flex justify-content-between mb-2">
                                <!-- Aquí se mostrará el subtotal -->
                            </div>
                            <div id="summary-discount-row" class="d-flex justify-content-between mb-2" style="display: none;">
                                <!-- Aquí se mostrará el descuento -->
                            </div>
                            <div id="summary-articles-container">
                                <!-- Aquí se mostrarán los artículos seleccionados -->
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="h6">Total:</span>
                                <span class="h6" id="summary-total"></span>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>Tu reserva está lista para ser confirmada. Haz clic en el botón "Confirmar Reserva" para finalizar.</span>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-prev"><i class="fas fa-arrow-left me-2"></i> Atrás</button>
                        <button type="submit" class="btn btn-reserve">Confirmar Reserva <i class="fas fa-check ms-2"></i></button>
                    </div>
                </div>
            </form>
        `;
        
        const bookingForm = document.getElementById('booking-form');
        const currentStepInput = document.getElementById('current-step');
        const progressBar = bookingModal.querySelector('.progress-bar');
        const steps = bookingModal.querySelectorAll('.step');

        // Función para actualizar el progreso
        function updateProgress(step) {
            const totalSteps = bookingModal.querySelectorAll('.step:not([style*="display: none"])').length;
            const progress = (step / totalSteps) * 100;
            progressBar.style.width = `${progress}%`;
            progressBar.setAttribute('aria-valuenow', progress);

            steps.forEach((stepEl) => {
                const stepNum = parseInt(stepEl.dataset.step);
                stepEl.classList.remove('active', 'completed');
                if (stepNum < step) {
                    stepEl.classList.add('completed');
                } else if (stepNum === step) {
                    stepEl.classList.add('active');
                }
            });
        }

        // Función para cargar las imágenes de la galería en el carrusel
        function loadGalleryImages(serviceId) {
            const carouselInner = document.getElementById('carousel-inner');
            const carouselIndicators = document.getElementById('carousel-indicators');
            
            // Limpiar el carrusel
            carouselInner.innerHTML = '';
            carouselIndicators.innerHTML = '';
            
            // Obtener las imágenes de la galería para este servicio
            let galleryImages = [];
            
            <?php foreach ($services as $service): ?>
            if (serviceId === '<?php echo $service->id; ?>') {
                <?php if (isset($service_galleries[$service->id]) && !empty($service_galleries[$service->id])): ?>
                galleryImages = [
                    '<?php echo esc_url($service->main_image ? $service->main_image : plugins_url('assets/images/default-service.svg', dirname(__FILE__))); ?>',
                    <?php foreach ($service_galleries[$service->id] as $image): ?>
                    '<?php echo esc_url($image->image_url); ?>',
                    <?php endforeach; ?>
                ];
                <?php else: ?>
                galleryImages = ['<?php echo esc_url($service->main_image ? $service->main_image : plugins_url('assets/images/default-service.svg', dirname(__FILE__))); ?>'];
                <?php endif; ?>
            }
            <?php endforeach; ?>
            
            // Si no hay imágenes, mostrar la imagen principal
            if (galleryImages.length === 0) {
                galleryImages = [document.getElementById('tour-image').src];
            }
            
            // Crear los elementos del carrusel
            galleryImages.forEach((imageUrl, index) => {
                // Crear indicador
                const indicator = document.createElement('button');
                indicator.type = 'button';
                indicator.setAttribute('data-bs-target', '#tourGalleryCarousel');
                indicator.setAttribute('data-bs-slide-to', index.toString());
                if (index === 0) {
                    indicator.classList.add('active');
                }
                indicator.setAttribute('aria-current', index === 0 ? 'true' : 'false');
                indicator.setAttribute('aria-label', `Slide ${index + 1}`);
                carouselIndicators.appendChild(indicator);
                
                // Crear item del carrusel
                const carouselItem = document.createElement('div');
                carouselItem.classList.add('carousel-item');
                if (index === 0) {
                    carouselItem.classList.add('active');
                }
                
                const img = document.createElement('img');
                img.src = imageUrl;
                img.classList.add('d-block', 'w-100', 'rounded');
                img.alt = `Imagen ${index + 1} del tour`;
                img.style.objectFit = 'cover';
                img.style.height = '200px';
                
                carouselItem.appendChild(img);
                carouselInner.appendChild(carouselItem);
            });
            
            // Mostrar u ocultar controles del carrusel según la cantidad de imágenes
            const carouselControls = document.querySelectorAll('.carousel-control-prev, .carousel-control-next');
            const carouselIndicatorsContainer = document.getElementById('carousel-indicators');
            
            if (galleryImages.length <= 1) {
                carouselControls.forEach(control => control.style.display = 'none');
                carouselIndicatorsContainer.style.display = 'none';
            } else {
                carouselControls.forEach(control => control.style.display = 'flex');
                carouselIndicatorsContainer.style.display = 'flex';
            }
        }
        
        // Manejador para los botones de reserva
        document.querySelectorAll('.btn-reserve:not(.btn-next):not([type="submit"])').forEach(button => {
            button.addEventListener('click', function() {
                const serviceData = this.dataset;
                document.getElementById('service-id').value = serviceData.serviceId;
                document.getElementById('tour-title').textContent = serviceData.serviceName;
                document.getElementById('tour-category').textContent = serviceData.serviceCategory;
                document.getElementById('tour-description').textContent = serviceData.serviceDescription;
                document.getElementById('tour-price').textContent = `$${parseFloat(serviceData.servicePrice).toFixed(2)}`;
                
                // Cargar las imágenes de la galería en el carrusel
                loadGalleryImages(serviceData.serviceId);
                
                // Mostrar u ocultar el botón de YouTube según corresponda
                const youtubeButtonContainer = document.getElementById('youtube-button-container');
                const youtubePromoLink = document.getElementById('youtube-promo-link');
                
                if (serviceData.servicePromoVideo && serviceData.servicePromoVideo.trim() !== '') {
                    youtubePromoLink.href = serviceData.servicePromoVideo;
                    youtubeButtonContainer.style.display = 'block';
                } else {
                    youtubeButtonContainer.style.display = 'none';
                }
                
                // Mostrar la fecha del tour
                const today = new Date();
                const formattedDate = today.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
                document.getElementById('tour-date-display').textContent = formattedDate;
                
                // Actualizar el resumen
                document.getElementById('summary-service').textContent = serviceData.serviceName;
                
                // No establecer el total aquí, se calculará en updateSummary() con el descuento aplicado

                // Mostrar/ocultar paso de artículos según corresponda
                const articlesStep = document.getElementById('articles-step');
                const hasArticles = serviceData.hasArticles === 'true';
                articlesStep.style.display = hasArticles ? 'block' : 'none';
                
                // Si tiene artículos, cargar los artículos del grupo
                if (hasArticles) {
                    const articleGroupId = serviceData.articleGroupId;
                    loadArticlesFromGroup(articleGroupId);
                }

                // Resetear el formulario y el progreso
                bookingForm.reset();
                currentStepInput.value = '1';
                updateProgress(1);

                // Mostrar solo el primer paso
                bookingModal.querySelectorAll('.checkout-step').forEach(step => step.style.display = 'none');
                document.getElementById('step-1').style.display = 'block';
            });
        });

        // Navegación entre pasos
        bookingModal.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-next') || e.target.classList.contains('btn-prev') || 
                e.target.parentElement.classList.contains('btn-next') || e.target.parentElement.classList.contains('btn-prev')) {
                
                e.preventDefault();
                const button = e.target.classList.contains('btn-next') || e.target.classList.contains('btn-prev') ? 
                              e.target : e.target.parentElement;
                
                const currentStep = parseInt(currentStepInput.value);
                const isNext = button.classList.contains('btn-next');
                const newStep = isNext ? currentStep + 1 : currentStep - 1;

                // Validar el formulario antes de avanzar
                if (isNext && !validateStep(currentStep)) {
                    return;
                }

                // Actualizar paso actual
                currentStepInput.value = newStep;
                updateProgress(newStep);

                // Mostrar el paso correspondiente
                bookingModal.querySelectorAll('.checkout-step').forEach(step => step.style.display = 'none');
                document.getElementById(`step-${newStep}`).style.display = 'block';
                
                // Actualizar resumen en el paso final
                if (newStep === 5) {
                    updateSummary();
                }
            }
        });
        
        // Función para validar cada paso
        function validateStep(step) {
            switch(step) {
                case 1:
                    const participantsInput = document.getElementById('tour-participants');
                    if (!participantsInput.value || participantsInput.value < 1) {
                        alert('Por favor, indica el número de participantes');
                        participantsInput.focus();
                        return false;
                    }
                    return true;
                case 3:
                    const nameInput = document.getElementById('client-name');
                    const emailInput = document.getElementById('client-email');
                    const phoneInput = document.getElementById('client-phone');
                    const countryInput = document.getElementById('client-country');
                    const termsCheck = document.getElementById('terms-check');
                    
                    if (!nameInput.value) {
                        alert('Por favor, ingresa tu nombre completo');
                        nameInput.focus();
                        return false;
                    }
                    if (!emailInput.value || !emailInput.value.includes('@')) {
                        alert('Por favor, ingresa un correo electrónico válido');
                        emailInput.focus();
                        return false;
                    }
                    if (!phoneInput.value) {
                        alert('Por favor, ingresa tu número de teléfono');
                        phoneInput.focus();
                        return false;
                    }
                    if (!countryInput.value) {
                        alert('Por favor, selecciona tu país');
                        countryInput.focus();
                        return false;
                    }
                    if (!termsCheck.checked) {
                        alert('Debes aceptar los términos y condiciones para continuar');
                        termsCheck.focus();
                        return false;
                    }
                    return true;
                default:
                    return true;
            }
        }
        
        // Función para verificar si aplica algún descuento
        function checkDiscounts(participants) {
            const serviceId = document.getElementById('service-id').value;
            let discountApplied = null;
            
            // Obtener los descuentos disponibles para este servicio
            <?php foreach ($services as $service): ?>
            if (serviceId === '<?php echo $service->id; ?>') {
                <?php if (isset($service_discounts[$service->id]) && !empty($service_discounts[$service->id])): ?>
                    <?php foreach ($service_discounts[$service->id] as $discount): ?>
                        <?php if ($discount->condition_type === 'people'): ?>
                        if (parseInt(participants) > <?php echo $discount->condition_value; ?> && !discountApplied) {
                            discountApplied = {
                                type: '<?php echo $discount->discount_type; ?>',
                                value: <?php echo $discount->discount_value; ?>,
                                title: '<?php echo $discount->title; ?>',
                                description: '<?php echo $discount->description; ?>',
                                conditionValue: <?php echo $discount->condition_value; ?>,
                                conditionType: 'people'
                            };
                        }
                        <?php endif; ?>
                        <?php if ($discount->condition_type === 'price'): ?>
                        // Verificar descuento por monto total
                        const servicePrice = document.getElementById('tour-price').textContent;
                        const price = parseFloat(servicePrice.replace('$', ''));
                        const participantsCount = parseInt(participants);
                        const subtotal = price * participantsCount;
                        
                        if (subtotal > <?php echo $discount->condition_value; ?> && !discountApplied) {
                            discountApplied = {
                                type: '<?php echo $discount->discount_type; ?>',
                                value: <?php echo $discount->discount_value; ?>,
                                title: '<?php echo $discount->title; ?>',
                                description: '<?php echo $discount->description; ?>',
                                conditionValue: <?php echo $discount->condition_value; ?>,
                                conditionType: 'price'
                            };
                        }
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            }
            <?php endforeach; ?>
            
            return discountApplied;
        }
        
        // Función para actualizar el mensaje de descuento
        function updateDiscountMessage(participants) {
            const reservationMsg = document.getElementById('reservation-message');
            const discountMsg = document.getElementById('discount-message');
            const discountText = document.getElementById('discount-text');
            
            // También actualizar los elementos del modal
            const reservationMsgModal = document.getElementById('reservation-message-modal');
            const discountMsgModal = document.getElementById('discount-message-modal');
            const discountTextModal = document.getElementById('discount-text-modal');
            
            const discount = checkDiscounts(participants);
            
            if (discount) {
                // Mostrar mensaje de descuento
                let message = '';
                
                if (discount.conditionType === 'people') {
                    message = `¡Genial! Por reservar para más de ${discount.conditionValue} personas, `;
                } else if (discount.conditionType === 'price') {
                    message = `¡Genial! Por un monto mayor a $${discount.conditionValue.toFixed(2)}, `;
                }
                
                if (discount.type === 'percentage') {
                    message += `obtienes un ${discount.value}% de descuento`;
                } else {
                    message += `obtienes un descuento de $${discount.value.toFixed(2)}`;
                }
                
                if (discount.description) {
                    message += `. ${discount.description}`;
                }
                
                // Calcular y mostrar el precio con descuento
                const servicePrice = document.getElementById('tour-price').textContent;
                const price = parseFloat(servicePrice.replace('$', ''));
                const participantsCount = parseInt(participants);
                const subtotal = price * participantsCount;
                const discountAmount = getDiscountAmount(price, participantsCount, discount);
                const finalPrice = subtotal - discountAmount;
                
                // Añadir información de precio al mensaje
                message += `<br><span class="text-decoration-line-through text-muted">$${subtotal.toFixed(2)}</span> <span class="fw-bold">$${finalPrice.toFixed(2)}</span>`;
                
                // Actualizar mensaje en la página principal
                if (discountText) {
                    discountText.innerHTML = message;
                    reservationMsg.style.display = 'none';
                    discountMsg.style.display = 'block';
                }
                
                // Actualizar mensaje en el modal
                if (discountTextModal) {
                    discountTextModal.innerHTML = message;
                    reservationMsgModal.style.display = 'none';
                    discountMsgModal.style.display = 'block';
                }
                
                return discount;
            } else {
                // Mostrar mensaje normal en la página principal
                if (reservationMsg) {
                    reservationMsg.style.display = 'block';
                    discountMsg.style.display = 'none';
                }
                
                // Mostrar mensaje normal en el modal
                if (reservationMsgModal) {
                    reservationMsgModal.style.display = 'block';
                    discountMsgModal.style.display = 'none';
                }
                
                return null;
            }
        }
        
        // Función para calcular el precio con descuento
        function calculatePriceWithDiscount(basePrice, participants, discount) {
            if (!discount) return basePrice * participants;
            
            const subtotal = basePrice * participants;
            
            if (discount.type === 'percentage') {
                const discountAmount = subtotal * (discount.value / 100);
                return subtotal - discountAmount;
            } else {
                return subtotal - discount.value;
            }
        }
        
        // Función para obtener el monto del descuento
        function getDiscountAmount(basePrice, participants, discount) {
            if (!discount) return 0;
            
            const subtotal = basePrice * participants;
            
            if (discount.type === 'percentage') {
                return subtotal * (discount.value / 100);
            } else {
                return discount.value;
            }
        }
        
        // Función para actualizar el resumen
        function updateSummary() {
            // Obtener datos del servicio
            const serviceName = document.getElementById('tour-title').textContent;
            const serviceCategory = document.getElementById('tour-category').textContent;
            const serviceDate = document.getElementById('tour-date-display').textContent;
            const participants = document.getElementById('tour-participants').value || 1;
            const servicePrice = document.getElementById('tour-price').textContent;
            
            // Obtener datos del cliente
            const clientName = document.getElementById('client-name').value;
            const clientEmail = document.getElementById('client-email').value;
            const clientPhone = document.getElementById('client-phone').value;
            
            // Obtener método de pago
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'cash';
            const paymentMethodText = paymentMethod === 'transfer' ? 'Transferencia Bancaria' : 'Pago en Efectivo';
            
            // Actualizar información del servicio
            document.getElementById('summary-service').textContent = serviceName;
            if (document.getElementById('summary-category')) {
                document.getElementById('summary-category').textContent = serviceCategory;
            }
            document.getElementById('summary-date').textContent = serviceDate;
            document.getElementById('summary-participants').textContent = participants;
            
            // Actualizar información del cliente si existen los elementos
            if (document.getElementById('summary-client-name')) {
                document.getElementById('summary-client-name').textContent = clientName || 'No especificado';
            }
            if (document.getElementById('summary-client-email')) {
                document.getElementById('summary-client-email').textContent = clientEmail || 'No especificado';
            }
            if (document.getElementById('summary-client-phone')) {
                document.getElementById('summary-client-phone').textContent = clientPhone || 'No especificado';
            }
            
            // Actualizar método de pago
            document.getElementById('summary-payment').textContent = paymentMethodText;
            
            // Mostrar detalles de transferencia bancaria si aplica
            const paymentDetailsContainer = document.getElementById('summary-payment-details');
            if (paymentDetailsContainer) {
                if (paymentMethod === 'transfer') {
                    const bankName = document.getElementById('bank-name')?.value || '';
                    const accountNumber = document.getElementById('account-number')?.value || '';
                    const accountType = document.getElementById('account-type')?.value || '';
                    
                    if (document.getElementById('summary-bank-name')) {
                        document.getElementById('summary-bank-name').textContent = bankName || 'No especificado';
                    }
                    if (document.getElementById('summary-account-number')) {
                        document.getElementById('summary-account-number').textContent = accountNumber || 'No especificado';
                    }
                    if (document.getElementById('summary-account-type')) {
                        document.getElementById('summary-account-type').textContent = accountType || 'No especificado';
                    }
                    
                    paymentDetailsContainer.style.display = 'block';
                } else {
                    paymentDetailsContainer.style.display = 'none';
                }
            }
            
            // Verificar si aplica algún descuento
            const discount = checkDiscounts(participants);
            
            // Calcular el total
            const price = parseFloat(servicePrice.replace('$', ''));
            const subtotal = price * parseInt(participants);
            let participantsTotal = subtotal;
            let discountAmount = 0;
            
            // Actualizar subtotal
            const subtotalRow = document.getElementById('summary-subtotal-row');
            if (subtotalRow) {
                subtotalRow.innerHTML = `
                    <span>Subtotal (${participants} x $${price.toFixed(2)}):</span>
                    <span>$${subtotal.toFixed(2)}</span>
                `;
            }
            
            // Mostrar información de descuento si aplica
            const discountRow = document.getElementById('summary-discount-row');
            
            if (discountRow) {
                if (discount) {
                    // Calcular el monto del descuento
                    if (discount.type === 'percentage') {
                        discountAmount = subtotal * (discount.value / 100);
                    } else {
                        discountAmount = discount.value;
                    }
                    
                    participantsTotal = subtotal - discountAmount;
                    
                    let discountText = '';
                    if (discount.type === 'percentage') {
                        discountText = `Descuento (${discount.value}%)`;
                    } else {
                        discountText = 'Descuento';
                    }
                    
                    // Añadir información sobre la condición del descuento
                    if (discount.conditionType === 'people') {
                        discountText += ` por más de ${discount.conditionValue} personas`;
                    } else if (discount.conditionType === 'price') {
                        discountText += ` por monto mayor a $${discount.conditionValue.toFixed(2)}`;
                    }
                    
                    // Añadir el título del descuento si está disponible
                    if (discount.title) {
                        discountText = `${discount.title} (${discountText})`;
                    }
                    
                    discountRow.innerHTML = `
                        <span class="text-success">${discountText}:</span>
                        <span class="text-success">-$${discountAmount.toFixed(2)}</span>
                    `;
                    
                    // Asegurarse de que el descuento sea visible
                    discountRow.style.display = 'flex';
                } else {
                    discountRow.style.display = 'none';
                }
            }
            
            // Calcular el subtotal de artículos en tiempo real
            let articlesTotal = 0;
            const selectedArticles = [];
            
            // Obtener todos los artículos seleccionados
            document.querySelectorAll('.article-item').forEach(item => {
                const quantity = parseInt(item.querySelector('.article-quantity').value);
                if (quantity > 0) {
                    const name = item.querySelector('.article-name').textContent;
                    const price = parseFloat(item.querySelector('.article-price').dataset.price);
                    selectedArticles.push({ name, quantity, price });
                    
                    // Sumar al total de artículos si el precio no es 0
                    if (price > 0) {
                        articlesTotal += price * quantity;
                    }
                }
            });
            
            // Actualizar el resumen con los artículos seleccionados
            const articlesContainer = document.getElementById('summary-articles-container');
            if (articlesContainer) {
                articlesContainer.innerHTML = '';
                
                // Mostrar los artículos seleccionados en el resumen
                if (selectedArticles.length > 0) {
                    const articlesTitle = document.createElement('h6');
                    articlesTitle.className = 'mt-3 mb-2 border-bottom pb-2';
                    articlesTitle.textContent = 'Artículos Seleccionados';
                    articlesContainer.appendChild(articlesTitle);
                    
                    const articlesList = document.createElement('div');
                    articlesList.className = 'mt-2';
                    
                    selectedArticles.forEach(article => {
                        const articleRow = document.createElement('div');
                        articleRow.className = 'd-flex justify-content-between mb-1';
                        
                        // Mostrar "INCLUIDO" para artículos con precio 0
                        if (article.price === 0) {
                            articleRow.innerHTML = `
                                <span>${article.quantity}x ${article.name}</span>
                                <span class="fw-bold text-success">INCLUIDO</span>
                            `;
                        } else {
                            const articleTotal = article.price * article.quantity;
                            articleRow.innerHTML = `
                                <span>${article.quantity}x ${article.name}</span>
                                <span>$${articleTotal.toFixed(2)}</span>
                            `;
                        }
                        
                        articlesList.appendChild(articleRow);
                    });
                    
                    articlesContainer.appendChild(articlesList);
                    
                    // Mostrar subtotal de artículos si hay artículos con precio
                    if (articlesTotal > 0) {
                        const articlesSubtotalRow = document.createElement('div');
                        articlesSubtotalRow.className = 'd-flex justify-content-between mt-2';
                        articlesSubtotalRow.innerHTML = `
                            <span class="fw-medium">Subtotal artículos:</span>
                            <span>$${articlesTotal.toFixed(2)}</span>
                        `;
                        articlesContainer.appendChild(articlesSubtotalRow);
                    }
                }
            }
            
            // Calcular el total final
            const total = participantsTotal + articlesTotal;
            
            // Actualizar el total en el resumen
            document.getElementById('summary-total').textContent = `$${total.toFixed(2)}`;
            
            // Ya no necesitamos mostrar el precio original tachado junto al precio con descuento
            // porque ahora mostramos el subtotal y el descuento por separado antes del total
        }
        
        // Manejar la visibilidad de los campos de transferencia bancaria
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const bankTransferDetails = document.getElementById('bank-transfer-details');
                if (this.value === 'transfer') {
                    bankTransferDetails.style.display = 'block';
                } else {
                    bankTransferDetails.style.display = 'none';
                }
            });
        });
        
        // Añadir event listener al campo de participantes para verificar descuentos
        document.querySelectorAll('#tour-participants').forEach(input => {
            input.addEventListener('change', function() {
                const discount = updateDiscountMessage(this.value);
                // Actualizar el precio en tiempo real si hay un cambio en el número de participantes
                if (document.getElementById('step-5').style.display === 'block') {
                    updateSummary();
                }
            });
            input.addEventListener('input', function() {
                const discount = updateDiscountMessage(this.value);
                // Actualizar el precio en tiempo real si hay un cambio en el número de participantes
                if (document.getElementById('step-5').style.display === 'block') {
                    updateSummary();
                }
            });
        });
        
        // Inicializar la visibilidad de los campos de transferencia bancaria
        window.addEventListener('DOMContentLoaded', function() {
            const bankTransferDetails = document.getElementById('bank-transfer-details');
            const transferRadio = document.getElementById('payment-transfer');
            if (transferRadio && transferRadio.checked) {
                bankTransferDetails.style.display = 'block';
            } else {
                bankTransferDetails.style.display = 'none';
            }
        });

        // Función para cargar los artículos del grupo
        function loadArticlesFromGroup(groupId) {
            const loadingAlert = document.querySelector('.loading-articles');
            const articlesList = document.getElementById('articles-list');
            
            // Mostrar cargando y ocultar la lista
            loadingAlert.style.display = 'block';
            loadingAlert.innerHTML = `
                <i class="fas fa-spinner fa-spin me-2"></i>
                <span>Cargando artículos disponibles...</span>
            `;
            loadingAlert.classList.remove('alert-danger');
            loadingAlert.classList.add('alert-info');
            articlesList.style.display = 'none';
            articlesList.innerHTML = '';
            
            // Realizar la petición AJAX para obtener los artículos del grupo
            fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=get_group_articles&group_id=${groupId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error de red: ${response.status}`);
                    }
                    return response.json().catch(e => {
                        console.error('Error al parsear JSON:', e);
                        throw new Error('La respuesta del servidor no es un JSON válido');
                    });
                })
                .then(response => {
                    // Ocultar cargando
                    loadingAlert.style.display = 'none';
                    
                    // Verificar si la respuesta contiene un error
                    if (response.success === false) {
                        throw new Error(response.data?.message || response.message || 'Error al cargar los artículos');
                    }
                    
                    // Obtener los artículos de la estructura de respuesta
                    const articles = response.data || [];
                    
                    // Verificar si tenemos artículos
                    if (articles.length > 0) {
                        // Crear elementos para cada artículo
                        articles.forEach(article => {
                            // Asegurarse de que los valores sean correctos
                            const articleId = article.id || 0;
                            const articleName = article.name || 'Artículo sin nombre';
                            const articleDesc = article.description || 'Sin descripción';
                            const articlePrice = parseFloat(article.price) || 0;
                            
                            const articleElement = document.createElement('div');
                            articleElement.className = 'col-md-6 col-lg-4';
                            articleElement.innerHTML = `
                                <div class="card h-100 article-item" data-article-id="${articleId}">
                                    <div class="card-body">
                                        <h6 class="card-title article-name">${articleName}</h6>
                                        <p class="card-text small text-muted">${articleDesc}</p>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            ${articlePrice === 0 ? 
                                                `<span class="article-price fw-bold text-success" data-price="${articlePrice}">INCLUIDO</span>` : 
                                                `<span class="article-price" data-price="${articlePrice}">$${articlePrice.toFixed(2)}</span>`
                                            }
                                            <div class="quantity-control d-flex align-items-center ${articlePrice === 0 ? 'invisible' : ''}">
                                                <button type="button" class="btn btn-sm btn-outline-secondary decrease-quantity" ${articlePrice === 0 ? 'disabled' : ''}>-</button>
                                                <input type="number" class="form-control form-control-sm mx-2 text-center article-quantity" value="${articlePrice === 0 ? '1' : '0'}" min="0" max="99" style="width: 50px;" ${articlePrice === 0 ? 'readonly' : ''}>
                                                <button type="button" class="btn btn-sm btn-outline-secondary increase-quantity" ${articlePrice === 0 ? 'disabled' : ''}>+</button>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-end">
                                            <span class="article-subtotal">$0.00</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                            articlesList.appendChild(articleElement);
                        });
                        
                        // Mostrar la lista de artículos
                        articlesList.style.display = 'flex';
                        
                        // Agregar event listeners para los controles de cantidad
                        addQuantityControlListeners();
                        
                        // Actualizar subtotales iniciales
                        updateArticlesSubtotal();
                    } else {
                        // No hay artículos disponibles
                        loadingAlert.innerHTML = `
                            <i class="fas fa-info-circle me-2"></i>
                            <span>No hay artículos disponibles para este servicio.</span>
                        `;
                        loadingAlert.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar los artículos:', error);
                    loadingAlert.innerHTML = `
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span>Error al cargar los artículos: ${error.message}</span>
                    `;
                    loadingAlert.classList.remove('alert-info');
                    loadingAlert.classList.add('alert-danger');
                    loadingAlert.style.display = 'block';
                    
                    // Mostrar mensaje de error específico en la consola para depuración
                    console.log('Detalles del error:', error.message, error.name, error);
                    
                    // Intentar recuperarse del error
                    articlesList.innerHTML = '<div class="col-12"><div class="alert alert-warning">No se pudieron cargar los artículos. Por favor, intente nuevamente más tarde.</div></div>';
                    articlesList.style.display = 'flex';
                });
        }
        
        // Función para agregar event listeners a los controles de cantidad
        function addQuantityControlListeners() {
            // Botones de incremento
            document.querySelectorAll('.increase-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('.article-quantity');
                    const currentValue = parseInt(input.value);
                    if (currentValue < parseInt(input.max)) {
                        input.value = currentValue + 1;
                        // Disparar evento de cambio para actualizar subtotales
                        input.dispatchEvent(new Event('change'));
                    }
                });
            });
            
            // Botones de decremento
            document.querySelectorAll('.decrease-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('.article-quantity');
                    const currentValue = parseInt(input.value);
                    if (currentValue > parseInt(input.min)) {
                        input.value = currentValue - 1;
                        // Disparar evento de cambio para actualizar subtotales
                        input.dispatchEvent(new Event('change'));
                    }
                });
            });
            
            // Inputs de cantidad
            document.querySelectorAll('.article-quantity').forEach(input => {
                input.addEventListener('change', function() {
                    // Asegurar que el valor esté dentro de los límites
                    let value = parseInt(this.value);
                    if (isNaN(value) || value < parseInt(this.min)) {
                        value = parseInt(this.min);
                    } else if (value > parseInt(this.max)) {
                        value = parseInt(this.max);
                    }
                    this.value = value;
                    
                    // Actualizar subtotal del artículo
                    const articleItem = this.closest('.article-item');
                    const price = parseFloat(articleItem.querySelector('.article-price').dataset.price);
                    const subtotal = price * value;
                    articleItem.querySelector('.article-subtotal').textContent = `$${subtotal.toFixed(2)}`;
                    
                    // Actualizar subtotal general de artículos y el resumen si es necesario
                    updateArticlesSubtotal();
                });
            });
        }
        
        // Función para actualizar el subtotal de todos los artículos
        function updateArticlesSubtotal() {
            let subtotal = 0;
            document.querySelectorAll('.article-item').forEach(item => {
                const quantity = parseInt(item.querySelector('.article-quantity').value);
                const price = parseFloat(item.querySelector('.article-price').dataset.price);
                // Solo sumar al subtotal si el precio no es 0
                if (price > 0) {
                    subtotal += price * quantity;
                }
            });
            
            document.getElementById('articles-subtotal').textContent = `$${subtotal.toFixed(2)}`;
            
            // Si estamos en el paso de confirmación, actualizar el resumen
            if (document.getElementById('step-5').style.display === 'block') {
                updateSummary();
            }
        }
        
        // Prevenir cierre del modal al hacer clic fuera o con ESC
        bookingModal.addEventListener('hide.bs.modal', function(e) {
            if (!confirm('¿Estás seguro de que deseas cancelar la reserva?')) {
                e.preventDefault();
            }
        });

        // Manejar el envío del formulario
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Aquí iría la lógica para procesar la reserva
            alert('¡Reserva completada con éxito!');
            bootstrap.Modal.getInstance(bookingModal).hide();
        });
    });
    </script>