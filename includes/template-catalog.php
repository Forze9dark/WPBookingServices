<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bookingModal = document.getElementById('bookingModal');
        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const wpNonce = '<?php echo wp_create_nonce('save_reservation'); ?>';

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
                const hasArticles = serviceData.hasArticles === 'true' && serviceData.articleGroupId && serviceData.articleGroupId !== 'null';
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

                // Verificar si debemos saltar el paso de artículos
                const articlesStep = document.getElementById('articles-step');
                const hasArticles = articlesStep.style.display !== 'none';
                
                // Si vamos del paso 3 al 4 pero no hay artículos, saltar al paso 5
                if (isNext && currentStep === 3 && newStep === 4 && !hasArticles) {
                    currentStepInput.value = 5;
                    updateProgress(5);
                    bookingModal.querySelectorAll('.checkout-step').forEach(step => step.style.display = 'none');
                    document.getElementById('step-5').style.display = 'block';
                    updateSummary();
                    return;
                }
                
                // Si vamos del paso 5 al 4 pero no hay artículos, volver al paso 3
                if (!isNext && currentStep === 5 && newStep === 4 && !hasArticles) {
                    currentStepInput.value = 3;
                    updateProgress(3);
                    bookingModal.querySelectorAll('.checkout-step').forEach(step => step.style.display = 'none');
                    document.getElementById('step-3').style.display = 'block';
                    return;
                }
                
                // Actualizar paso actual para casos normales
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
        
        // Función para calcular el monto del descuento
        function getDiscountAmount(basePrice, participants, discount) {
            if (!discount) return 0;
            
            const subtotal = basePrice * participants;
            
            if (discount.type === 'percentage') {
                return subtotal * (discount.value / 100);
            } else {
                return discount.value;
            }
        }

        // Función para calcular el precio con descuento
        function calculatePriceWithDiscount(basePrice, participants, discount) {
            if (!discount) return basePrice * participants;
            
            const subtotal = basePrice * participants;
            const discountAmount = getDiscountAmount(basePrice, participants, discount);
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
            // Obtener elementos del DOM
            const tourTitleElement = document.getElementById('tour-title');
            const tourCategoryElement = document.getElementById('tour-category');
            const tourDateElement = document.getElementById('tour-date-display');
            const tourParticipantsElement = document.getElementById('tour-participants');
            const tourPriceElement = document.getElementById('tour-price');

            // Verificar que todos los elementos existan antes de acceder a sus propiedades
            if (!tourTitleElement || !tourCategoryElement || !tourDateElement || 
                !tourParticipantsElement || !tourPriceElement) {
                console.error('No se pudieron encontrar todos los elementos necesarios para actualizar el resumen');
                return;
            }

            // Obtener datos del servicio
            const serviceName = tourTitleElement.textContent;
            const serviceCategory = tourCategoryElement.textContent;
            const serviceDate = tourDateElement.textContent;
            const participants = tourParticipantsElement.value || 1;
            const servicePrice = tourPriceElement.textContent;
            
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
            
            try {
                // Validar que todos los campos requeridos estén completos
                const requiredFields = {
                    'tour-participants': 'Número de participantes',
                    'client-name': 'Nombre del cliente',
                    'client-email': 'Email del cliente',
                    'client-phone': 'Teléfono del cliente'
                    // El campo 'tour-date' no es requerido, se usará la fecha actual si no está presente
                };

                for (const [fieldId, fieldName] of Object.entries(requiredFields)) {
                    const field = document.getElementById(fieldId);
                    if (!field || !field.value.trim()) {
                        throw new Error(`Por favor, complete el campo ${fieldName}`);
                    }
                }

                // Obtener los datos del formulario para mostrarlos en la confirmación
                const serviceName = document.getElementById('service-name').textContent;
                // Usar la fecha actual si no hay un campo de fecha específico o está vacío
                const today = new Date();
                const formattedDate = today.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
                const tourDateField = document.getElementById('tour-date');
                const serviceDate = (tourDateField && tourDateField.value.trim()) ? tourDateField.value : formattedDate;
                const participants = document.getElementById('tour-participants').value;
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                const totalAmount = document.getElementById('summary-total').textContent;
                const servicePrice = parseFloat(document.getElementById('tour-price').textContent.replace('$', ''));
                
                // Actualizar el modal de confirmación con los datos de la reserva
                document.getElementById('confirmation-service').textContent = serviceName;
                document.getElementById('confirmation-date').textContent = serviceDate.includes('-') ? new Date(serviceDate).toLocaleDateString() : serviceDate;
                document.getElementById('confirmation-participants').textContent = participants;
                document.getElementById('confirmation-payment').textContent = paymentMethod === 'transfer' ? 'Transferencia Bancaria' : 'Pago en Efectivo';
                
                // Actualizar información del cliente en la confirmación
                document.getElementById('confirmation-client-name').textContent = document.getElementById('client-name').value;
                document.getElementById('confirmation-client-email').textContent = document.getElementById('client-email').value;
                document.getElementById('confirmation-client-phone').textContent = document.getElementById('client-phone').value;
                
                // Actualizar el texto del subtotal para incluir el número de participantes
                const subtotalElement = document.getElementById('confirmation-subtotal');
                subtotalElement.textContent = totalAmount;
                
                // Actualizar el texto que muestra el cálculo del subtotal
                const subtotalParent = subtotalElement.parentElement.previousElementSibling;
                subtotalParent.textContent = `Subtotal (${participants} x $${servicePrice.toFixed(2)}):`;
            
            document.getElementById('confirmation-total').textContent = totalAmount;

                // Mostrar detalles de transferencia bancaria si aplica
                const paymentDetailsSection = document.getElementById('confirmation-payment-details');
                if (paymentDetailsSection) {
                    if (paymentMethod === 'transfer') {
                        document.getElementById('confirmation-bank-name').textContent = document.getElementById('bank-name').value || 'No especificado';
                        document.getElementById('confirmation-account-number').textContent = document.getElementById('account-number').value || 'No especificado';
                        document.getElementById('confirmation-account-type').textContent = document.getElementById('account-type').value || 'No especificado';
                        paymentDetailsSection.style.display = 'block';
                    } else {
                        paymentDetailsSection.style.display = 'none';
                    }
                }

                // Ocultar el modal de reserva y mostrar el modal de confirmación con una transición suave
                const bookingModalInstance = bootstrap.Modal.getInstance(bookingModal);
                bookingModalInstance.hide();

                // Esperar a que se complete la animación de cierre antes de mostrar el siguiente modal
                bookingModal.addEventListener('hidden.bs.modal', function showConfirmation() {
                    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
                    confirmationModal.show();
                    // Remover el event listener después de usarlo
                    bookingModal.removeEventListener('hidden.bs.modal', showConfirmation);
                });

            } catch (error) {
                // Mostrar mensaje de error al usuario
                formAlert(error.message);

                // La función formAlert ya maneja la eliminación del mensaje
            }
        });
    });
    
    // Evento para el botón de cerrar en el modal de confirmación
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('close-confirmation')) {
            document.getElementById('close-confirmation').addEventListener('click', function() {
                bootstrap.Modal.getInstance(document.getElementById('confirmationModal')).hide();
                // Recargar la página para mostrar el catálogo nuevamente
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            });
        }
    });
    </script>
    
    <!-- Modal de Éxito -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">¡Reserva Completada!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                    <h4>¡Gracias por tu reserva!</h4>
                    <p>Tu reserva ha sido registrada exitosamente y está en estado <strong>pendiente</strong>.</p>
                    <p>Te contactaremos pronto para confirmar los detalles.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Reserva -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white fw-bold" id="confirmationModalLabel">Confirmación de Reserva</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-column align-items-center mb-4">
                        <div class="checkout-progress d-flex justify-content-center w-100 mb-4">
                            <div class="step completed">
                                <div class="step-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="step-label">Tipo</div>
                            </div>
                            <div class="step completed">
                                <div class="step-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="step-label">Detalles</div>
                            </div>
                            <div class="step completed">
                                <div class="step-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="step-label">Información</div>
                            </div>
                            <div class="step completed">
                                <div class="step-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="step-label">Confirmación</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h4>Confirmación de Reserva</h4>
                        <p class="text-muted">Resumen de tu reserva</p>
                    </div>
                    
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Servicio:</span>
                                <span class="fw-medium" id="confirmation-service">Tour 2</span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Fecha:</span>
                                <span class="fw-medium" id="confirmation-date">21/05/2025</span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Participantes:</span>
                                <span class="fw-medium" id="confirmation-participants">10</span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Método de pago:</span>
                                <span class="fw-medium" id="confirmation-payment">Pago en Efectivo</span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal (10 x $200.00):</span>
                                <span class="fw-medium" id="confirmation-subtotal">$2000.00</span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between">
                                <span class="h6">Total:</span>
                                <span class="h6" id="confirmation-total">$2000.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>¡Gracias por tu reserva! Hemos enviado un correo electrónico con los detalles de tu confirmación.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="close-confirmation" class="btn btn-reserve">Confirmar Reserva <i class="fas fa-check ms-2"></i></button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para guardar la reserva
        async function saveReservation(serviceId, reservationData) {
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'save_reservation',
                        service_id: serviceId,
                        reservation_date: reservationData.date,
                        participants: reservationData.participants,
                        payment_method: reservationData.paymentMethod,
                        total_amount: reservationData.totalAmount,
                        _ajax_nonce: wpNonce
                    })
                });

                if (!response.ok) {
                    throw new Error('Error al guardar la reserva');
                }

                const data = await response.json();
                if (data.success) {
                    // Cerrar el modal de confirmación
                    const confirmationModal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
                    confirmationModal.hide();

                    // Mostrar el modal de éxito
                    setTimeout(() => {
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                    }, 500);
                } else {
                    throw new Error(data.message || 'Error al guardar la reserva');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Hubo un error al procesar tu reserva. Por favor, intenta nuevamente.');
            }
        }

        // Manejar el clic en el botón de confirmar reserva
        document.getElementById('close-confirmation').addEventListener('click', function() {
            const serviceId = document.querySelector('#service-id').value;
            const reservationData = {
                date: document.getElementById('confirmation-date').textContent,
                participants: document.getElementById('confirmation-participants').textContent,
                paymentMethod: document.getElementById('confirmation-payment').textContent.includes('Efectivo') ? 'cash' : 'transfer',
                totalAmount: document.getElementById('confirmation-total').textContent.replace('$', '')
            };
            saveReservation(serviceId, reservationData);
        });

        // Cerrar modal de éxito y recargar página
        document.querySelector('#successModal .btn-primary').addEventListener('click', function() {
            setTimeout(() => {
                window.location.reload();
            }, 500);
        });
    });
    </script>
    