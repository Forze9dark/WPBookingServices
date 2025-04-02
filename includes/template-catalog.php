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

$services = $wpdb->get_results(
    "SELECT s.*, c.name as category_name, 
    COALESCE(s.main_image, (SELECT image_url FROM {$gallery_table} WHERE service_id = s.id LIMIT 1)) as main_image
    FROM {$services_table} s
    LEFT JOIN {$categories_table} c ON s.category_id = c.id
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
            --primary-color: #4a90e2;
            --secondary-color: #f5f5f5;
            --text-color: #333;
            --border-radius: 8px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color);
            line-height: 1.6;
        }

        .catalog-header {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            padding: 3rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            color: white;
        }

        .search-filters {
            background-color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .service-card {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .service-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .service-card:hover .service-image {
            transform: scale(1.05);
        }

        .service-info {
            padding: 1.5rem;
        }

        .service-title {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .service-category {
            color: var(--primary-color);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .service-price {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .btn-reserve {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .btn-reserve:hover {
            background-color: #357abd;
            color: white;
        }

        .modal-content {
            border-radius: var(--border-radius);
        }

        .booking-form {
            padding: 1rem;
        }

        @media (max-width: 768px) {
            .service-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="catalog-header">
        <div class="container">
            <h1 class="text-center mb-4 fw-bold">Catálogo de Servicios</h1>
            <p class="text-center text-white-50 mb-0">Descubre nuestra selección de servicios profesionales</p>
        </div>
    </header>

    <div class="container py-5">
        <!-- Filtros y Búsqueda -->
        <div class="search-filters mb-4 p-4 bg-white rounded-3 shadow-sm">
            <div class="row g-3">
                <div class="col-md-4 mb-3">
                    <input type="text" id="search-input" class="form-control" placeholder="Buscar servicios...">
                </div>
                <div class="col-md-3 mb-3">
                    <select id="category-filter" class="form-select">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo esc_attr($category->id); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <select id="sort-filter" class="form-select">
                        <option value="name-asc">Nombre (A-Z)</option>
                        <option value="name-desc">Nombre (Z-A)</option>
                        <option value="price-asc">Precio (Menor a Mayor)</option>
                        <option value="price-desc">Precio (Mayor a Menor)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Grid de Servicios -->
        <div class="row" id="services-grid">
            <?php foreach ($services as $service): ?>
                <div class="col-lg-4 col-md-6 mb-4 service-item" 
                     data-name="<?php echo esc_attr(strtolower($service->title)); ?>"
                     data-category="<?php echo esc_attr($service->category_id); ?>"
                     data-price="<?php echo esc_attr($service->price); ?>">
                    <div class="service-card">
                        <div class="position-relative overflow-hidden">
                            <img src="<?php echo esc_url($service->main_image ? $service->main_image : plugins_url('assets/images/default-service.svg', dirname(__FILE__))); ?>" 
                                 alt="<?php echo esc_attr($service->name); ?>" 
                                 class="service-image">
                            <div class="service-category position-absolute top-0 end-0 m-3 px-3 py-1 rounded-pill bg-white shadow-sm">
                                <i class="fas fa-tag text-primary"></i> 
                                <span class="ms-1"><?php echo esc_html($service->category_name); ?></span>
                            </div>
                        </div>
                        <div class="service-info">
                            <h3 class="service-title fw-bold mb-3"><?php echo esc_html($service->title); ?></h3>
                            <p class="service-description text-muted mb-4"><?php echo wp_trim_words($service->description, 20); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="service-price h4 mb-0">$<?php echo number_format($service->price, 2); ?></span>
                                <button class="btn btn-reserve" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#bookingModal"
                                        data-service-id="<?php echo esc_attr($service->id); ?>"
                                        data-service-name="<?php echo esc_attr($service->title); ?>"
                                        data-service-price="<?php echo esc_attr($service->price); ?>">
                                    Reservar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal de Reserva -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="bookingModalLabel">Reservar Servicio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="booking-form" class="booking-form needs-validation" novalidate>
                        <input type="hidden" id="service-id" name="service_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="client-name" name="client_name" placeholder="Tu nombre" required>
                                    <label for="client-name">Nombre</label>
                                    <div class="invalid-feedback">Por favor, ingresa tu nombre.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="client-email" name="client_email" placeholder="tu@email.com" required>
                                    <label for="client-email">Email</label>
                                    <div class="invalid-feedback">Por favor, ingresa un email válido.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="booking-date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                                    <label for="booking-date">Fecha</label>
                                    <div class="invalid-feedback">Por favor, selecciona una fecha válida.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="time" class="form-control" id="booking-time" name="booking_time" required>
                                    <label for="booking-time">Hora</label>
                                    <div class="invalid-feedback">Por favor, selecciona una hora válida.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="booking-notes" name="booking_notes" placeholder="Notas adicionales" style="height: 100px"></textarea>
                                    <label for="booking-notes">Notas adicionales</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" form="booking-form" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-check me-2"></i>Confirmar Reserva
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php wp_footer(); ?>
    <!-- Bootstrap JS y Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const categoryFilter = document.getElementById('category-filter');
            const sortFilter = document.getElementById('sort-filter');
            const servicesGrid = document.getElementById('services-grid');
            const serviceItems = document.querySelectorAll('.service-item');

            function filterServices() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedCategory = categoryFilter.value;
                const sortValue = sortFilter.value;

                // Convertir NodeList a Array para poder ordenar
                let servicesArray = Array.from(serviceItems);

                // Ordenar servicios
                servicesArray.sort((a, b) => {
                    if (sortValue === 'name-asc') {
                        return a.dataset.name.localeCompare(b.dataset.name);
                    } else if (sortValue === 'name-desc') {
                        return b.dataset.name.localeCompare(a.dataset.name);
                    } else if (sortValue === 'price-asc') {
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    } else if (sortValue === 'price-desc') {
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    }
                });

                servicesArray.forEach(item => {
                    const name = item.dataset.name;
                    const category = item.dataset.category;
                    
                    const matchesSearch = name.includes(searchTerm);
                    const matchesCategory = !selectedCategory || category === selectedCategory;

                    if (matchesSearch && matchesCategory) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });

                // Reordenar elementos en el DOM
                servicesGrid.innerHTML = '';
                servicesArray.forEach(item => servicesGrid.appendChild(item));
            }

            searchInput.addEventListener('input', filterServices);
            categoryFilter.addEventListener('change', filterServices);
            sortFilter.addEventListener('change', filterServices);

            // Modal de Reserva
            const bookingModal = document.getElementById('bookingModal');
            const serviceIdInput = document.getElementById('service-id');
            const bookingForm = document.getElementById('booking-form');

            bookingModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const serviceId = button.getAttribute('data-service-id');
                const serviceName = button.getAttribute('data-service-name');
                
                serviceIdInput.value = serviceId;
                document.getElementById('bookingModalLabel').textContent = 'Reservar: ' + serviceName;
            });

            bookingForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    this.classList.add('was-validated');
                    return;
                }
                
                // Aquí se implementará la lógica de envío del formulario
                const successAlert = document.createElement('div');
                successAlert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-4';
                successAlert.setAttribute('role', 'alert');
                successAlert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>¡Éxito!</strong> Tu reserva ha sido enviada correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(successAlert);
                
                this.reset();
                this.classList.remove('was-validated');
                bootstrap.Modal.getInstance(bookingModal).hide();
                
                // Remover la alerta después de 5 segundos
                setTimeout(() => {
                    successAlert.remove();
                }, 5000);
            });
            
            // Validación de fecha mínima
            const bookingDate = document.getElementById('booking-date');
            bookingDate.min = new Date().toISOString().split('T')[0];
        });
    </script>
</body>
</html>