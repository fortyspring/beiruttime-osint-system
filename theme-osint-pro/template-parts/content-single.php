<?php
/**
 * Template part for displaying premium/locked content
 */

$is_premium_content = get_post_meta(get_the_ID(), '_osint_premium_content', true);
$content_locked = osint_pro_lock_content(get_the_content(), $is_premium_content);
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>

        <?php if ($is_premium_content) : ?>
            <span class="premium-badge">
                <?php _e('محتوى مميز 🔒', 'osint-pro-theme'); ?>
            </span>
        <?php endif; ?>

        <div class="entry-meta">
            <span class="posted-on"><?php echo get_the_date(); ?></span>
            <span class="byline"><?php the_author(); ?></span>
        </div>
    </header>

    <?php if (has_post_thumbnail()) : ?>
        <div class="post-thumbnail-wrapper">
            <?php the_post_thumbnail('osint-large'); ?>
        </div>
    <?php endif; ?>

    <div class="entry-content">
        <?php echo $content_locked; ?>
    </div>

    <footer class="entry-footer">
        <?php
        wp_link_pages(array(
            'before' => '<div class="page-links">' . __('الصفحات:', 'osint-pro-theme'),
            'after'  => '</div>',
        ));
        ?>

        <div class="entry-tags">
            <?php the_tags('<span class="tag-links">' . __('الأوسمة:', 'osint-pro-theme') . ' ', ', ', '</span>'); ?>
        </div>

        <div class="share-buttons">
            <h4><?php _e('شارك هذا المقال:', 'osint-pro-theme'); ?></h4>
            <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode(get_the_title()); ?>&url=<?php the_permalink(); ?>" 
               class="share-twitter" target="_blank">Twitter</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink(); ?>" 
               class="share-facebook" target="_blank">Facebook</a>
            <a href="https://wa.me/?text=<?php echo urlencode(get_the_title() . ' ' . get_the_permalink()); ?>" 
               class="share-whatsapp" target="_blank">WhatsApp</a>
        </div>
    </footer>

    <?php if (osint_pro_is_premium_user()) : ?>
        <div class="premium-user-notice">
            <p><?php _e('شكراً لاشتراكك! أنت تشاهد المحتوى المميز.', 'osint-pro-theme'); ?></p>
        </div>
    <?php endif; ?>

    <?php osint_pro_display_ad('content'); ?>
</article>

<nav class="navigation post-navigation">
    <h2 class="screen-reader-text"><?php _e('تنقل المنشورات', 'osint-pro-theme'); ?></h2>
    <div class="nav-links">
        <?php
        the_post_navigation(array(
            'prev_text' => '<span class="nav-subtitle">' . __('السابق:', 'osint-pro-theme') . '</span> <span class="nav-title">%title</span>',
            'next_text' => '<span class="nav-subtitle">' . __('التالي:', 'osint-pro-theme') . '</span> <span class="nav-title">%title</span>',
        ));
        ?>
    </div>
</nav>

<?php
if (comments_open() || get_comments_number()) :
    comments_template();
endif;
?>
