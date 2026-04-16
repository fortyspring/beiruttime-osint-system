/**
 * OSINT Pro Theme - Main JavaScript
 */

(function($) {
    'use strict';

    // Mobile Menu Toggle
    $(document).ready(function() {
        $('.menu-toggle').on('click', function() {
            $('.main-navigation').toggleClass('active');
            $(this).attr('aria-expanded', function(i, attr) {
                return attr === 'true' ? 'false' : 'true';
            });
        });

        // Close mobile menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.main-navigation, .menu-toggle').length) {
                $('.main-navigation').removeClass('active');
                $('.menu-toggle').attr('aria-expanded', 'false');
            }
        });

        // Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this.hash);
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 800);
            }
        });

        // Lazy loading for images
        if ('loading' in HTMLImageElement.prototype) {
            $('img').attr('loading', 'lazy');
        }

        // Add loading state to forms
        $('form').on('submit', function() {
            $(this).addClass('loading');
        });

        // Premium content unlock animation
        if ($('.premium-lock').length) {
            $('.subscribe-btn').on('click', function() {
                $('.lock-message').addClass('pulse');
            });
        }

        // Ad click tracking (Google Analytics)
        $('.ad-space a').on('click', function() {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'click', {
                    'event_category': 'Advertisement',
                    'event_label': $(this).attr('href'),
                    'transport_type': 'beacon'
                });
            }
        });

        // Share buttons tracking
        $('.share-buttons a').on('click', function() {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'share', {
                    'event_category': 'Social',
                    'event_label': $(this).attr('class'),
                    'content_type': 'article',
                    'item_id': window.location.href
                });
            }
        });
    });

    // Window load events
    $(window).on('load', function() {
        // Remove loading states
        $('body').removeClass('loading');

        // Masonry layout for posts grid (if needed)
        if (typeof $.fn.masonry === 'function') {
            $('.posts-grid').masonry({
                itemSelector: '.post-card',
                columnWidth: '.post-card',
                percentPosition: true
            });
        }
    });

    // Handle AJAX subscription checks
    window.checkSubscriptionStatus = function() {
        $.ajax({
            url: osintProAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'check_subscription_status',
                nonce: osintProAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data.is_premium) {
                    $('.premium-lock').each(function() {
                        $(this).fadeOut(300, function() {
                            $(this).removeClass('premium-lock').fadeIn(300);
                        });
                    });
                }
            }
        });
    };

    // Check subscription status on page load for logged-in users
    if (typeof osintProAjax !== 'undefined' && $('body').hasClass('logged-in')) {
        setTimeout(checkSubscriptionStatus, 1000);
    }

})(jQuery);
