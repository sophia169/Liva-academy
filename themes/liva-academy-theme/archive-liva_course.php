<?php get_header(); ?>
<section class="liva-section"><div class="liva-shell">
<div class="liva-section-heading"><div><span class="liva-eyebrow">Courses</span><h1>All courses</h1><p>Browse the current learning catalogue.</p></div></div>
<?php if (shortcode_exists('liva_course_grid')) echo do_shortcode('[liva_course_grid limit="24"]'); ?>
</div></section>
<?php get_footer(); ?>
