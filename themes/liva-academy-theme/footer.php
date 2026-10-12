</main>
<footer class="liva-site-footer" id="company">
    <div class="liva-shell liva-footer-grid">
        <div class="liva-footer-brand">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/liva-logo-white.png'); ?>" alt="Liva Academy">
            <p>The complete Vietnamese language learning platform — courses, live classes, books, and ebooks for all levels.</p>
            <div class="liva-footer-social">● &nbsp; ● &nbsp; ●</div>
        </div>
        <div>
            <h3>Learning</h3>
            <a href="<?php echo esc_url(liva_page_url('my-learning', home_url('/#courses'))); ?>">Courses</a>
            <a href="<?php echo esc_url(liva_page_url('online-classroom', '#')); ?>">Online classroom</a>
            <a href="<?php echo esc_url(liva_page_url('placement-test', '#')); ?>">Placement Test</a>
        </div>
        <div>
            <h3>Account</h3>
            <a href="<?php echo esc_url(liva_page_url('login', wp_login_url())); ?>">Login / Register</a>
            <a href="<?php echo esc_url(liva_page_url('my-courses', '#')); ?>">My courses</a>
            <a href="#">My library</a>
            <a href="<?php echo esc_url(liva_page_url('order-history', '#')); ?>">Order history</a>
        </div>
        <div>
            <h3>Company</h3>
            <a href="#">About us</a>
            <a href="#">Contact us</a>
            <a href="#">Help center</a>
        </div>
    </div>
    <div class="liva-shell liva-footer-bottom"><span>© <?php echo esc_html(date('Y')); ?> Liva Academy. All rights reserved.</span><span>Ho Chi Minh City, Vietnam · support@livaacademy.com</span></div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
