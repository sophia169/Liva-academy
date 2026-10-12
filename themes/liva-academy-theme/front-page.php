<?php
get_header();
$placement = liva_page_url('placement-test', '#');
$my_learning = liva_page_url('my-learning', get_post_type_archive_link('liva_course') ?: '#');
?>
<section class="liva-home-hero">
    <div class="liva-shell liva-home-hero-grid">
        <div class="liva-home-hero-copy">
            <h1>Empowering Minds<br>Inspiring <span>Futures</span></h1>
            <p>Self-paced video courses, live online classes, books and ebooks — everything you need to go from A1 to fluency, all in one place.</p>
            <a class="liva-btn liva-btn-primary" href="<?php echo esc_url($my_learning); ?>">Find Your Courses</a>
            <a class="liva-home-placement-link" href="<?php echo esc_url($placement); ?>">Take placement test →</a>
        </div>
        <div class="liva-home-hero-art" aria-hidden="true">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/homepage-hero.png'); ?>" alt="">
        </div>
    </div>
</section>

<section class="liva-home-section" id="courses">
    <div class="liva-shell">
        <div class="liva-home-section-heading">
            <span class="liva-home-kicker">Courses</span>
            <h2>Choose Perfect Academic Courses</h2>
            <p>Explore comprehensive video lessons designed for all fluency levels</p>
        </div>
        <?php if (shortcode_exists('liva_course_grid')) echo do_shortcode('[liva_course_grid limit="4" context="home"]'); ?>
        <div class="liva-home-center-action">
            <a class="liva-btn liva-btn-primary" href="<?php echo esc_url($my_learning); ?>">See All Courses</a>
        </div>
    </div>
</section>

<section class="liva-home-section liva-home-how" id="how-it-works">
    <div class="liva-shell">
        <div class="liva-home-section-heading">
            <span class="liva-home-kicker">How it works</span>
            <h2>Start learning in 3 steps</h2>
            <p>No Vietnamese knowledge needed. We guide you from the very first word.</p>
        </div>
        <div class="liva-home-journey-grid">
            <div class="liva-home-steps">
                <article class="liva-home-step"><span class="num">1</span><div><h3>Take the free placement test</h3><p>30 questions, 15 minutes. We assess your vocabulary, grammar, and listening to find your exact level.</p></div></article>
                <article class="liva-home-step"><span class="num">2</span><div><h3>Get your personalized course recommendation</h3><p>Based on your score, we recommend the most suitable course. You can enrol directly from the results page.</p></div></article>
                <article class="liva-home-step"><span class="num">3</span><div><h3>Learn at your own pace — or go live</h3><p>Study video lessons anytime, download materials, and track your progress. Or book a live class with a teacher.</p></div></article>
            </div>
            <aside class="liva-home-journey-card">
                <h3>Your learning journey</h3>
                <div>Placement test</div>
                <div>Enrolled in online courses based on your level</div>
                <div>Booked live class</div>
            </aside>
        </div>
    </div>
</section>

<section class="liva-home-classroom" id="classroom">
    <div class="liva-shell">
        <span class="liva-home-kicker">Online classroom</span>
        <h2>Practice speaking live with a Vietnamese teacher</h2>
        <p>Video call sessions — 1-on-1 or small groups. Instant pronunciation feedback, flexible schedule, any timezone.</p>
        <a class="liva-btn liva-btn-primary" href="<?php echo esc_url(liva_page_url('online-classroom', '#')); ?>">Reserve a class</a>
    </div>
</section>

<section class="liva-home-section liva-home-reviews">
    <div class="liva-shell">
        <div class="liva-home-section-heading">
            <span class="liva-home-kicker">Student reviews</span>
            <h2>What our students say</h2>
            <p>Explore feedback from learners across all fluency levels</p>
        </div>
        <div class="liva-home-review-grid">
            <?php for ($i=0; $i<3; $i++): ?>
            <article class="liva-home-review-card">
                <div class="liva-home-review-top"><strong>Great Quality!</strong><span>❞</span></div>
                <div class="liva-home-stars">★★★★★</div>
                <p>Placeholder review copy for the final student testimonial content.</p>
                <div class="liva-home-review-person">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/testimonial-avatar.png'); ?>" alt="">
                    <div><strong>Student name</strong><small>Learner</small></div>
                </div>
            </article>
            <?php endfor; ?>
        </div>
    </div>
</section>

<section class="liva-home-bottom-cta">
    <div class="liva-shell liva-home-bottom-cta-inner">
        <div><h3>Not sure where to start?</h3><p>Take our free 15-minute placement test and get an instant recommendation for the course that fits your level perfectly.</p></div>
        <a class="liva-btn liva-btn-light liva-home-cta-button" href="<?php echo esc_url($placement); ?>">▣ &nbsp; Take the free test</a>
    </div>
</section>
<?php get_footer(); ?>
