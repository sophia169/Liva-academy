<?php get_header(); ?>
<?php while (have_posts()) : the_post(); ?>
    <article class="liva-page-content <?php echo liva_is_app_page() ? 'liva-page-content-app' : ''; ?>">
        <?php the_content(); ?>
    </article>
<?php endwhile; ?>
<?php get_footer(); ?>
