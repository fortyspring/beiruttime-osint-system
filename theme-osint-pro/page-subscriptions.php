<?php
/**
 * Template Name: صفحة الاشتراكات
 * Description: صفحة مخصصة لعرض خطط الاشتراك المدفوع
 */

get_header();
?>

<div class="subscriptions-page">
    <div class="container">
        <header class="page-header">
            <h1 class="page-title"><?php _e('خطط الاشتراك المميز', 'osint-pro-theme'); ?></h1>
            <p class="page-description"><?php _e('اختر الخطة المناسبة لك للوصول إلى المحتوى الحصري', 'osint-pro-theme'); ?></p>
        </header>

        <?php osint_pro_display_ad('content'); ?>

        <div class="pricing-table">
            <?php
            if (class_exists('WooCommerce')) {
                $args = array(
                    'post_type'      => 'product',
                    'posts_per_page' => -1,
                    'meta_query'     => array(
                        array(
                            'key'     => '_product_type',
                            'value'   => 'subscription',
                            'compare' => '='
                        )
                    )
                );

                $subscriptions = new WP_Query($args);

                if ($subscriptions->have_posts()) :
                    echo '<div class="pricing-grid">';
                    
                    while ($subscriptions->have_posts()) :
                        $subscriptions->the_post();
                        global $product;
                        ?>
                        <div class="pricing-card">
                            <div class="pricing-header">
                                <h3><?php the_title(); ?></h3>
                                <div class="pricing-price">
                                    <?php echo $product->get_price_html(); ?>
                                </div>
                            </div>
                            
                            <div class="pricing-features">
                                <?php
                                $features = get_post_meta(get_the_ID(), '_subscription_features', true);
                                if ($features) {
                                    foreach ($features as $feature) {
                                        echo '<p>✓ ' . esc_html($feature) . '</p>';
                                    }
                                }
                                ?>
                            </div>
                            
                            <div class="pricing-action">
                                <a href="<?php echo $product->add_to_cart_url(); ?>" class="subscribe-btn">
                                    <?php _e('اشترك الآن', 'osint-pro-theme'); ?>
                                </a>
                            </div>
                        </div>
                        <?php
                    endwhile;
                    
                    echo '</div>';
                    wp_reset_postdata();
                else :
                    ?>
                    <div class="no-subscriptions">
                        <p><?php _e('لا توجد خطط اشتراك متاحة حالياً.', 'osint-pro-theme'); ?></p>
                        <p><?php _e('يرجى التواصل معنا للمزيد من المعلومات.', 'osint-pro-theme'); ?></p>
                    </div>
                    <?php
                endif;
            } else {
                ?>
                <div class="woocommerce-notice">
                    <p><?php _e('يرجى تثبيت وتفعيل WooCommerce لعرض خطط الاشتراك.', 'osint-pro-theme'); ?></p>
                </div>
                <?php
            }
            ?>
        </div>

        <section class="subscription-benefits">
            <h2><?php _e('لماذا تشترك معنا؟', 'osint-pro-theme'); ?></h2>
            
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">📊</div>
                    <h3><?php _e('تقارير حصرية', 'osint-pro-theme'); ?></h3>
                    <p><?php _e('احصل على تقارير OSINT متقدمة وحصرية للمشتركين فقط', 'osint-pro-theme'); ?></p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">🔔</div>
                    <h3><?php _e('تنبيهات مبكرة', 'osint-pro-theme'); ?></h3>
                    <p><?php _e('كن أول من يعرف بالتهديدات والتطورات الأمنية', 'osint-pro-theme'); ?></p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">📈</div>
                    <h3><?php _e('تحليلات متقدمة', 'osint-pro-theme'); ?></h3>
                    <p><?php _e('وصول كامل لأدوات التحليل والبيانات المتقدمة', 'osint-pro-theme'); ?></p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">🎯</div>
                    <h3><?php _e('محتوى مخصص', 'osint-pro-theme'); ?></h3>
                    <p><?php _e('محتوى مصمم خصيصاً لاحتياجاتك المهنية', 'osint-pro-theme'); ?></p>
                </div>
            </div>
        </section>

        <?php if (!is_user_logged_in()) : ?>
            <section class="login-prompt">
                <p><?php _e('لديك حساب بالفعل؟', 'osint-pro-theme'); ?> 
                    <a href="<?php echo wp_login_url(get_permalink()); ?>"><?php _e('سجل دخولك', 'osint-pro-theme'); ?></a>
                </p>
            </section>
        <?php endif; ?>
    </div>
</div>

<style>
.pricing-table {
    padding: 40px 0;
}

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.pricing-card {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
    border: 2px solid transparent;
}

.pricing-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    border-color: #667eea;
}

.pricing-header h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: #333;
}

.pricing-price {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 20px;
}

.pricing-features {
    margin: 30px 0;
    text-align: right;
}

.pricing-features p {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    color: #666;
}

.pricing-action .subscribe-btn {
    width: 100%;
    display: block;
}

.subscription-benefits {
    margin-top: 60px;
    text-align: center;
}

.subscription-benefits h2 {
    font-size: 2rem;
    margin-bottom: 40px;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.benefit-card {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.benefit-icon {
    font-size: 3rem;
    margin-bottom: 15px;
}

.benefit-card h3 {
    margin-bottom: 10px;
    color: #333;
}

.benefit-card p {
    color: #666;
    line-height: 1.6;
}

.login-prompt {
    text-align: center;
    margin-top: 40px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .pricing-grid {
        grid-template-columns: 1fr;
    }
    
    .benefits-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
get_footer();
