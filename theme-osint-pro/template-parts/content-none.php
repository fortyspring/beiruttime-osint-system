<?php
/**
 * Template part for displaying no search results
 */
?>

<section class="no-results not-found">
    <header class="page-header">
        <h1 class="page-title"><?php _e('لم يتم العثور على شيء!', 'osint-pro-theme'); ?></h1>
    </header>

    <div class="page-content">
        <?php if (is_search()) : ?>
            <p><?php _e('نأسف، لا توجد نتائج مطابقة لبحثك. حاول استخدام كلمات مختلفة.', 'osint-pro-theme'); ?></p>
            <?php get_search_form(); ?>

        <?php else : ?>
            <p><?php _e('يبدو أنه لا يوجد شيء في هذا الموقع. تحقق من الروابط التالية:', 'osint-pro-theme'); ?></p>

            <ul>
                <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php _e('الصفحة الرئيسية', 'osint-pro-theme'); ?></a></li>
                <li><a href="<?php echo esc_url(get_post_type_archive_link('post')); ?>"><?php _e('المقالات', 'osint-pro-theme'); ?></a></li>
                <?php wp_list_pages(array('title_li' => '')); ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
