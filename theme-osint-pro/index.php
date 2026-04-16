<?php
/**
 * The main template file
 */

get_header();
?>

<div class="home-page">
    <?php if (is_front_page() && !osint_pro_is_premium_user()) : ?>
        <section class="subscription-banner">
            <div class="container">
                <h2><?php _e('احصل على وصول كامل للمحتوى المميز', 'osint-pro-theme'); ?></h2>
                <p><?php _e('اشترك الآن للوصول إلى تقارير OSINT الحصرية والتحليلات المتقدمة', 'osint-pro-theme'); ?></p>
                <?php echo do_shortcode('[osint_subscribe text="اشترك الآن"]'); ?>
            </div>
        </section>
    <?php endif; ?>

    <?php
    get_template_part('template-parts/content', 'index');
    ?>
</div>

<?php
get_footer();
