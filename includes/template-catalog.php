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

$services = $wpdb->get_results(
    "SELECT s.*, c.name as category_name, ag.id as article_group_id,
    COALESCE(s.main_image, (SELECT image_url FROM {$gallery_table} WHERE service_id = s.id LIMIT 1)) as main_image,
    (SELECT COUNT(*) FROM {$articles_table} a WHERE a.group_id = ag.id) as article_count
    FROM {$services_table} s
    LEFT JOIN {$categories_table} c ON s.category_id = c.id
    LEFT JOIN {$article_groups_table} ag ON s.group_id = ag.id
    WHERE s.status = 'active'"
);

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

        @media (max-width: 768px) {
            .service-card {
                margin-bottom: 1.5rem;
            }
            
            .search-bar {
                width: 100%;
                margin-top: 1rem;
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
                                        data-article-group-id="<?php echo esc_attr($service->article_group_id); ?>">
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
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <span>Selecciona la fecha y el número de participantes para continuar con tu reserva.</span>
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
                                    <img id="tour-image" src="" alt="Imagen del tour" class="img-fluid rounded" style="object-fit: cover; height: 200px; width: 100%;">                                        
                                </div>
                                <div class="col-md-8">
                                    <h4 id="tour-title" class="mb-2"></h4>
                                    <div class="mb-2">
                                        <span class="badge bg-primary rounded-pill me-2" id="tour-category"></span>
                                        <span class="text-muted"><i class="fas fa-clock me-1"></i><span id="tour-duration">3 horas</span></span>
                                    </div>
                                    <p id="tour-description" class="mb-3"></p>
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
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <span>Selecciona la fecha y el número de participantes para continuar con tu reserva.</span>
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

        // Manejador para los botones de reserva
        document.querySelectorAll('.btn-reserve:not(.btn-next):not([type="submit"])').forEach(button => {
            button.addEventListener('click', function() {
                const serviceData = this.dataset;
                document.getElementById('service-id').value = serviceData.serviceId;
                document.getElementById('tour-title').textContent = serviceData.serviceName;
                document.getElementById('tour-category').textContent = serviceData.serviceCategory;
                document.getElementById('tour-description').textContent = serviceData.serviceDescription;
                document.getElementById('tour-price').textContent = `$${parseFloat(serviceData.servicePrice).toFixed(2)}`;
                document.getElementById('tour-image').src = serviceData.serviceImage;
                
                // Actualizar el resumen
                document.getElementById('summary-service').textContent = serviceData.serviceName;
                document.getElementById('summary-total').textContent = `$${parseFloat(serviceData.servicePrice).toFixed(2)}`;

                // Mostrar/ocultar paso de artículos según corresponda
                const articlesStep = document.getElementById('articles-step');
                articlesStep.style.display = serviceData.hasArticles === 'true' ? 'block' : 'none';

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
                    const dateInput = document.getElementById('tour-date');
                    const participantsInput = document.getElementById('tour-participants');
                    if (!dateInput.value) {
                        alert('Por favor, selecciona una fecha para el tour');
                        dateInput.focus();
                        return false;
                    }
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
        
        // Función para actualizar el resumen
        function updateSummary() {
            const serviceName = document.getElementById('tour-title').textContent;
            const serviceDate = document.getElementById('tour-date').value;
            const participants = document.getElementById('tour-participants').value;
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            const servicePrice = document.getElementById('tour-price').textContent;
            
            document.getElementById('summary-service').textContent = serviceName;
            document.getElementById('summary-date').textContent = new Date(serviceDate).toLocaleDateString();
            document.getElementById('summary-participants').textContent = participants;
            document.getElementById('summary-payment').textContent = paymentMethod === 'transfer' ? 'Transferencia Bancaria' : 'Pago en Efectivo';
            
            // Calcular el total
            const price = parseFloat(servicePrice.replace('$', ''));
            const total = price * parseInt(participants);
            document.getElementById('summary-total').textContent = `$${total.toFixed(2)}`;
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