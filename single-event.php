<?php get_header(); ?>
<?php if (have_posts()):
    while (have_posts()):
        the_post(); ?>
        <div class="event">
            <h2><?php the_title(); ?></h2>
            <p><?php echo get_post_meta(get_the_ID(), "_event_date", true); ?></p>
            <p><?php the_content(); ?></p>  
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p><?php _e('Sorry, no events to display.', 'html5blank'); ?></p>
<?php endif; ?>
<?php get_footer(); ?>