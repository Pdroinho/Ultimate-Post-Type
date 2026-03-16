jQuery(window).on('elementor/frontend/init', function () {
    elementorFrontend.hooks.addAction('frontend/element_ready/upt_product_gallery.default', function ($scope, $) {
        
        const $wrapper = $scope.find('.fc-gallery-wrapper');
        if ($wrapper.hasClass('is-loaded') || $wrapper.length === 0) return;

        const config = $wrapper.data('config');
        const mainId = $wrapper.find('.fc-main-swiper').attr('id');
        const thumbsId = $wrapper.find('.fc-thumbs-swiper').attr('id');
        const isSingle = $wrapper.hasClass('fc-single-image-mode');

        // Referência correta para as setas (scoped)
        const $nextBtn = $scope.find('.fc-button-next');
        const $prevBtn = $scope.find('.fc-button-prev');

        const initGallery = () => {
            let thumbsSwiper = null;

            if (!isSingle && thumbsId) {
                thumbsSwiper = new Swiper('#' + thumbsId, {
                    spaceBetween: config.gap,
                    slidesPerView: config.thumbs_mobile,
                    breakpoints: {
                        768: { slidesPerView: config.thumbs_tablet },
                        1024: { slidesPerView: config.thumbs_desktop }
                    },
                    freeMode: true,
                    watchSlidesProgress: true,
                    normalizeSlideIndex: true,
                    preventClicks: false,
                    preventClicksPropagation: false
                });
            }

            if (mainId) {
                new Swiper('#' + mainId, {
                    spaceBetween: 20, // Espaço extra para não cortar sombras
                    effect: 'fade',
                    fadeEffect: { crossFade: true },
                    navigation: {
                        nextEl: $nextBtn[0],
                        prevEl: $prevBtn[0],
                    },
                    thumbs: {
                        swiper: thumbsSwiper
                    },
                    on: {
                        init: function () {
                            setTimeout(() => {
                                $wrapper.removeClass('is-loading').addClass('is-loaded');
                            }, 50);
                        }
                    },
                    autoHeight: true,
                    preventClicks: false,
                    preventClicksPropagation: false
                });
            } else {
                 $wrapper.removeClass('is-loading').addClass('is-loaded');
            }
        };

        if ( typeof Swiper === 'undefined' ) {
            console.error('upt Gallery: Swiper JS not found.');
            $wrapper.removeClass('is-loading');
        } else {
            initGallery();
        }
    });
});
