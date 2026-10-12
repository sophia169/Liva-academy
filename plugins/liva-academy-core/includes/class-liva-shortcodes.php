<?php
if (!defined('ABSPATH')) {
    exit;
}

class Liva_Shortcodes {

    public static function register(): void {
        foreach ([
            'liva_course_grid'          => 'course_grid',
            'liva_course_curriculum'    => 'course_curriculum',
            'liva_login'                => 'login',
            'liva_register'             => 'signup',
            'liva_student_dashboard'    => 'student_dashboard',
            'liva_my_learning'          => 'my_learning',
            'liva_my_courses'           => 'my_courses',
            'liva_listening'            => 'listening',
            'liva_placement_test'       => 'placement_intro',
            'liva_placement_intro'      => 'placement_intro',
            'liva_placement_runner'     => 'placement_runner',
            'liva_placement_result'     => 'placement_result',
            'liva_admin_dashboard'      => 'admin_dashboard',
            'liva_admin_users'          => 'admin_users',
            'liva_admin_courses'        => 'admin_courses',
            'liva_admin_placement'      => 'admin_placement',
            'liva_admin_placeholder'    => 'admin_placeholder',
            'liva_student_placeholder'  => 'student_placeholder',
            'liva_public_placeholder'   => 'public_placeholder',
        ] as $tag => $method) {
            add_shortcode($tag, [__CLASS__, $method]);
        }
    }

    private static function h(string $v): string {
        return esc_html($v);
    }

    private static function page(string $slug): string {
        return Liva_Core::page_url($slug);
    }

    private static function query_url(string $slug, array $args = []): string {
        return add_query_arg($args, self::page($slug));
    }

    private static function status(): string {
        $status  = sanitize_key($_GET['liva_status'] ?? '');
        $message = sanitize_text_field(wp_unslash($_GET['liva_message'] ?? ''));

        if (!$status || !$message) {
            return '';
        }

        $class = ($status === 'success') ? 'liva-form-success' : 'liva-form-error';
        ob_start();
        ?>
        <div class="<?php echo esc_attr($class); ?> liva-status-toast liva-auto-dismiss" id="liva-status-banner">
            <?php echo esc_html($message); ?>
        </div>
        <script>
        (function() {
            setTimeout(function() {
                var el = document.getElementById('liva-status-banner');
                if (el) {
                    el.classList.add('liva-fade-out');
                    el.classList.add('liva-toast-fade');
                    setTimeout(function() {
                        el.remove();
                    }, 400);
                }
                // Clean URL parameters so refreshing doesn't re-trigger the alert
                if (window.history.replaceState) {
                    var cleanUrl = new URL(window.location.href);
                    cleanUrl.searchParams.delete('liva_status');
                    cleanUrl.searchParams.delete('liva_message');
                    window.history.replaceState({}, document.title, cleanUrl.toString());
                }
            }, 3000); // Displays for 3 seconds before smoothly fading out
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    private static function initials(WP_User $u): string {
        $s = trim($u->first_name . ' ' . $u->last_name);
        if (!$s) {
            $s = $u->display_name;
        }
        $parts = preg_split('/\s+/', $s);
        return strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));
    }

    private static function user_role(WP_User $u): string {
        if (in_array('administrator', $u->roles, true) || in_array('liva_super_admin', $u->roles, true)) {
            return 'Super Admin';
        }
        if (in_array('liva_admin', $u->roles, true)) {
            return 'Admin';
        }
        return 'Student';
    }

    private static function admin_nav(string $active): string {
        $items = [
            'admin-dashboard'      => ['Dashboard', 'dashboard'],
            'liva-users'            => ['Users', 'user'],
            'liva-courses'          => ['Courses', 'courses'],
            'liva-listening-videos' => ['Listening videos', 'video'],
            'liva-orders'           => ['Orders', 'orders'],
            'liva-placement-tests'  => ['Placement tests', 'tests'],
            'liva-analytics'        => ['Analytics', 'analytics'],
            'liva-settings'         => ['Settings', 'settings']
        ];

        $out = '<nav class="liva-app-nav">';
        foreach ($items as $slug => $data) {
            $label     = $data[0];
            $icon_name = $data[1];
            $svg_html  = function_exists('liva_icon') ? liva_icon($icon_name) : '';
            $is_active = ($active === $slug) ? 'active' : '';

            $out .= '<a class="' . esc_attr($is_active) . '" href="' . esc_url(self::page($slug)) . '">';
            $out .= '<span class="liva-nav-icon">' . $svg_html . '</span>';
            $out .= '<span class="liva-nav-label">' . esc_html($label) . '</span>';
            $out .= '</a>';
        }
        $out .= '</nav>';

        return $out;
    }

    private static function student_nav(string $active): string {
        $items = [
            'student-dashboard' => ['Dashboard', 'dashboard'],
            'my-courses'        => ['My courses', 'courses'],
            'order-history'     => ['Order history', 'orders'],
            'profile'           => ['Profile', 'user'],
            'notifications'     => ['Notifications', 'bell'],
            'student-settings'  => ['Settings', 'settings']
        ];

        $out = '<nav class="liva-app-nav">';
        foreach ($items as $slug => $data) {
            $label     = $data[0];
            $icon_name = $data[1];
            $svg_html  = function_exists('liva_icon') ? liva_icon($icon_name) : '';
            $is_active = ($active === $slug) ? 'active' : '';

            $out .= '<a class="' . esc_attr($is_active) . '" href="' . esc_url(self::page($slug)) . '">';
            $out .= '<span class="liva-nav-icon">' . $svg_html . '</span>';
            $out .= '<span class="liva-nav-label">' . esc_html($label) . '</span>';
            $out .= '</a>';
        }
        $logout_icon = function_exists('liva_icon') ? liva_icon('logout') : '';
        $out .= '<a class="danger" href="' . esc_url(wp_logout_url(home_url('/'))) . '"><span class="liva-nav-icon">' . $logout_icon . '</span>Logout</a></nav>';

        return $out;
    }

    private static function shell(string $active, string $content, bool $admin = true): string {
        return '<div class="liva-app-shell"><aside class="liva-app-sidebar">' . ($admin ? self::admin_nav($active) : self::student_nav($active)) . '</aside><section class="liva-app-main"><div class="liva-app-container">' . $content . '</div></section></div>';
    }

    private static function app_title(string $title, string $description = '', string $actions = ''): string {
        return '<div class="liva-app-title"><div><h1>' . self::h($title) . '</h1>' . ($description ? '<p>' . self::h($description) . '</p>' : '') . '</div>' . $actions . '</div>';
    }

    public static function course_grid($atts = []): string {
        $atts = shortcode_atts(['limit' => 8, 'context' => 'default'], $atts);
        $posts = get_posts([
            'post_type'      => 'liva_course',
            'post_status'    => 'publish',
            'posts_per_page' => (int) $atts['limit']
        ]);

        if (!$posts) {
            return '<div class="liva-empty"><div class="symbol">▤</div><h3>No courses yet</h3><p>Add courses from the Liva admin area.</p></div>';
        }

        $fallback = trailingslashit(get_template_directory_uri()) . 'assets/img/course-placeholder.png';
        $out = '<div class="liva-course-grid ' . ($atts['context'] === 'home' ? 'liva-home-course-grid' : '') . '">';

        foreach ($posts as $p) {
            $level = get_post_meta($p->ID, '_liva_level', true) ?: 'Basic';
            $price = (float) (get_post_meta($p->ID, '_liva_price', true) ?: 0);
            $thumb = has_post_thumbnail($p)
                ? get_the_post_thumbnail($p, 'medium_large', ['loading' => 'lazy'])
                : '<img src="' . esc_url($fallback) . '" alt="">';
            $price_label = $price > 0 ? '$' . number_format($price, 0) : 'Free';

            $out .= '<a class="liva-course-card" href="' . esc_url(get_permalink($p)) . '"><div class="liva-course-thumb">' . $thumb . ($atts['context'] === 'home' ? '<span class="liva-course-price-badge">' . $price_label . '</span>' : '') . '</div><div class="liva-course-card-body"><span class="liva-pill">' . self::h($level) . '</span><h3>' . self::h($p->post_title) . '</h3>';

            if ($atts['context'] === 'home') {
                $out .= '<div class="liva-home-course-rating"><span class="stars">★★★★★</span><span>(4.8 Reviews)</span></div>';
            } else {
                $out .= '<div class="liva-card-meta"><span>24 hours - 1800 students</span></div><div class="liva-course-price-row"><strong>' . $price_label . '</strong><span>★ 4.8</span></div>';
            }
            $out .= '</div></a>';
        }
        return $out . '</div>';
    }

    public static function course_curriculum($atts = []): string {
        $atts = shortcode_atts(['course_id' => 0, 'preview' => 0], $atts);
        $course = absint($atts['course_id']);

        $mods = get_posts([
            'post_type'      => 'liva_module',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [['key' => '_liva_course_id', 'value' => $course]],
            'meta_key'       => '_liva_position',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC'
        ]);

        if (!$mods) {
            return '<div class="liva-empty"><div class="symbol">▥</div><h3>No modules yet</h3><p>This course has no curriculum content yet.</p></div>';
        }

        $out = '';
        foreach ($mods as $m) {
            $less = get_posts([
                'post_type'      => 'liva_lesson',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_key'       => '_liva_module_id',
                'meta_value'     => $m->ID,
                'orderby'        => 'ID',
                'order'          => 'ASC'
            ]);

            $out .= '<div class="liva-module"><div class="liva-module-header"><span>' . self::h($m->post_title) . '</span><span>' . count($less) . ' lessons</span></div>';
            foreach ($less as $l) {
                $type = get_post_meta($l->ID, '_liva_type', true) ?: 'video';
                $dur  = get_post_meta($l->ID, '_liva_duration', true);
                $out .= '<div class="liva-lesson-row"><span>▹ ' . self::h($l->post_title) . '</span><span class="liva-lesson-kind">' . self::h($type) . '</span><span>' . self::h($dur) . '</span></div>';
            }
            $out .= '</div>';
        }
        return $out;
    }

    public static function login(): string {
        if (is_user_logged_in()) {
            return '<div class="liva-auth-wrap"><div class="liva-auth-card"><h1>You are signed in</h1><p class="liva-auth-sub"><a class="liva-btn liva-btn-primary" href="' . esc_url(Liva_Core::admin_allowed() ? self::page('admin-dashboard') : self::page('student-dashboard')) . '">Open dashboard</a></p></div></div>';
        }

        ob_start();
        ?>
        <div class="liva-auth-wrap">
            <form class="liva-auth-card" method="post">
                <h1>Log in</h1>
                <p class="liva-auth-sub">Don’t have an account? <a class="liva-text-link" href="<?php echo esc_url(self::page('register')); ?>">Register</a></p>
                <?php echo self::status(); ?>
                <button type="button" class="liva-social-btn">◉ Continue with Google</button>
                <button type="button" class="liva-social-btn">● Continue with Facebook</button>
                <div class="liva-auth-or">or</div>
                <div class="liva-form-group">
                    <label>Email or username</label>
                    <input class="liva-input" name="email" required>
                </div>
                <div class="liva-form-group">
                    <label>Password</label>
                    <input class="liva-input" type="password" name="password" required>
                </div>
                <label class="liva-choice">
                    <input type="checkbox" name="remember" value="1">Remember me
                </label>
                <?php wp_nonce_field('liva_login', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="login">
                <button class="liva-btn liva-btn-primary liva-btn-block">Log in</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function signup(): string {
        if (is_user_logged_in()) {
            return self::login();
        }

        ob_start();
        ?>
        <div class="liva-auth-wrap">
            <form class="liva-auth-card" method="post">
                <h1>Sign up</h1>
                <p class="liva-auth-sub">Already have an account? <a class="liva-text-link" href="<?php echo esc_url(self::page('login')); ?>">Log in</a></p>
                <?php echo self::status(); ?>
                <button type="button" class="liva-social-btn">◉ Continue with Google</button>
                <button type="button" class="liva-social-btn">● Continue with Facebook</button>
                <div class="liva-auth-or">or</div>
                <div class="liva-form-group">
                    <label>Name</label>
                    <input class="liva-input" name="name" required>
                </div>
                <div class="liva-form-group">
                    <label>Email</label>
                    <input class="liva-input" type="email" name="email" required>
                </div>
                <div class="liva-form-group">
                    <label>Password</label>
                    <input class="liva-input" type="password" name="password" minlength="8" required>
                </div>
                <?php wp_nonce_field('liva_register', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="register">
                <button class="liva-btn liva-btn-primary liva-btn-block">Sign up</button>
                <p class="liva-help" style="text-align:center">By creating an account you agree to the site terms and privacy policy.</p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function enrollment_rows(int $uid): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}liva_enrollments WHERE user_id=%d ORDER BY created_at DESC",
                $uid
            ),
            ARRAY_A
        ) ?: [];
    }

    public static function student_dashboard(): string {
        $u = wp_get_current_user();
        $rows = self::enrollment_rows($u->ID);

        ob_start();
        echo self::status();
        echo self::app_title('Welcome back, ' . $u->display_name);
        ?>
        <div class="liva-course-dashboard-banner">
            <small>ONLINE COURSE</small>
            <h2>Sharpen your skills with professional online courses</h2>
            <a class="liva-btn liva-btn-light liva-btn-sm" href="<?php echo esc_url(self::page('my-learning')); ?>">Join now →</a>
        </div>
        <div class="liva-panel-header">
            <div>
                <h3>Continue watching</h3>
            </div>
        </div>
        <div class="liva-dashboard-course-grid">
            <?php
            $courses = [];
            foreach ($rows as $r) {
                $p = get_post($r['course_id']);
                if ($p) {
                    $courses[] = ['p' => $p, 'progress' => (int) $r['progress']];
                }
            }
            if (!$courses) {
                foreach (array_slice(get_posts(['post_type' => 'liva_course', 'post_status' => 'publish', 'posts_per_page' => 3]), 0, 3) as $p) {
                    $courses[] = ['p' => $p, 'progress' => 0];
                }
            }
            foreach ($courses as $c):
                $level = get_post_meta($c['p']->ID, '_liva_level', true) ?: 'Basic';
            ?>
                <article class="liva-course-card">
                    <div class="liva-course-thumb">
                        <strong>Course</strong>
                    </div>
                    <div class="liva-course-card-body">
                        <span class="liva-pill"><?php echo self::h($level); ?></span>
                        <h3><?php echo self::h($c['p']->post_title); ?></h3>
                        <div class="liva-progress">
                            <span style="width:<?php echo (int) $c['progress']; ?>%"></span>
                        </div>
                        <div class="liva-course-author">
                            <span class="liva-avatar">LA</span>
                            <span>Liva Academy<br><small>Course team</small></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
        return self::shell('student-dashboard', ob_get_clean(), false);
    }

    public static function my_learning(): string {
        $tab = sanitize_key($_GET['tab'] ?? 'courses');
        if ($tab === 'listening') {
            return self::listening();
        }

        $search = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
        $level  = sanitize_text_field(wp_unslash($_GET['level'] ?? ''));
        $page   = max(1, absint($_GET['pg'] ?? 1));

        $args = [
            'post_type'      => 'liva_course',
            'post_status'    => 'publish',
            'posts_per_page' => 8,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ];

        if ($search) {
            $args['s'] = $search;
        }
        if (in_array($level, ['Basic', 'Intermediate', 'Advanced'], true)) {
            $args['meta_query'] = [['key' => '_liva_level', 'value' => $level]];
        }

        $q = new WP_Query($args);
        $fallback = trailingslashit(get_template_directory_uri()) . 'assets/img/course-placeholder.png';

        ob_start();
        ?>
        <div class="liva-learning-header">
            <h1>My learning</h1>
            <p>Track all courses, practice listening videos</p>
        </div>
        <div class="liva-learning-tabs">
            <a class="active" href="<?php echo esc_url(self::page('my-learning')); ?>">Courses</a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'listening', self::page('my-learning'))); ?>">Listening practice</a>
        </div>
        <form class="liva-catalog-filters" method="get" action="<?php echo esc_url(self::page('my-learning')); ?>">
            <div class="liva-search-line">
                <div class="liva-search-input-wrap">
                    <span><?php echo function_exists('liva_icon') ? liva_icon('search') : '⌕'; ?></span>
                    <input class="liva-input liva-search" name="q" value="<?php echo esc_attr($search); ?>" placeholder="Search courses">
                </div>
                <button class="liva-search-button">Search</button>
            </div>
            <div class="liva-level-filter">
                <strong>Levels:</strong>
                <?php foreach (['' => 'All', 'Basic' => 'Basic', 'Intermediate' => 'Intermediate', 'Advanced' => 'Advanced'] as $value => $label): ?>
                    <a class="<?php echo $level === $value ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(array_filter(['level' => $value, 'q' => $search]), self::page('my-learning'))); ?>">
                        <?php echo self::h($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </form>

        <?php if (!$q->have_posts()): ?>
            <div class="liva-empty">
                <div class="symbol">▤</div>
                <h3>No courses found</h3>
                <p>Try another search or level.</p>
            </div>
        <?php else: ?>
            <div class="liva-course-grid liva-learning-course-grid">
                <?php
                while ($q->have_posts()):
                    $q->the_post();
                    $id    = get_the_ID();
                    $lvl   = get_post_meta($id, '_liva_level', true) ?: 'Basic';
                    $price = (float) (get_post_meta($id, '_liva_price', true) ?: 0);
                    $thumb = has_post_thumbnail($id)
                        ? get_the_post_thumbnail($id, 'medium_large', ['loading' => 'lazy'])
                        : '<img src="' . esc_url($fallback) . '" alt="">';
                ?>
                    <a class="liva-course-card" href="<?php echo esc_url(get_permalink($id)); ?>">
                        <div class="liva-course-thumb"><?php echo $thumb; ?></div>
                        <div class="liva-course-card-body">
                            <span class="liva-pill"><?php echo self::h($lvl); ?></span>
                            <h3><?php echo self::h(get_the_title()); ?></h3>
                            <div class="liva-card-meta">
                                <span>24 hours - 1800 students</span>
                            </div>
                            <div class="liva-course-price-row">
                                <strong><?php echo self::h($price > 0 ? '$' . number_format($price, 0) : 'Free'); ?></strong>
                                <span>★ <small>4.8</small></span>
                            </div>
                        </div>
                    </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        <?php endif; ?>

        <?php echo self::catalog_pagination($q->max_num_pages, $page, ['q' => $search, 'level' => $level]); ?>
        <?php
        return ob_get_clean();
    }

    public static function my_courses(): string {
        if (!is_user_logged_in()) {
            return '';
        }

        $u = wp_get_current_user();
        $rows = self::enrollment_rows($u->ID);

        ob_start();
        ?>
        <div class="liva-learning-header">
            <h1>My courses</h1>
            <p>Your enrolled courses and learning progress.</p>
        </div>

        <?php if (!$rows): ?>
            <div class="liva-empty">
                <div class="symbol">▤</div>
                <h3>No enrolled courses yet</h3>
                <p>Browse My learning to find a course.</p>
                <a class="liva-btn liva-btn-primary" href="<?php echo esc_url(self::page('my-learning')); ?>">Browse courses</a>
            </div>
        <?php else: ?>
            <div class="liva-course-grid">
                <?php
                foreach ($rows as $r):
                    $p = get_post($r['course_id']);
                    if (!$p) continue;
                    $level = get_post_meta($p->ID, '_liva_level', true) ?: 'Basic';
                ?>
                    <article class="liva-course-card">
                        <div class="liva-course-card-body">
                            <span class="liva-pill"><?php echo self::h($level); ?></span>
                            <h3><?php echo self::h($p->post_title); ?></h3>
                            <div class="liva-progress">
                                <span style="width:<?php echo (int) $r['progress']; ?>%"></span>
                            </div>
                            <div class="liva-card-meta">
                                <span><?php echo (int) $r['progress']; ?>% complete</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php
        return self::shell('my-courses', ob_get_clean(), false);
    }

    private static function catalog_pagination(int $pages, int $current, array $args = [], string $base = ''): string {
        if ($pages <= 1) {
            return '';
        }
        if (!$base) {
            $base = self::page('my-learning');
        }

        $out = '<nav class="liva-catalog-pagination" aria-label="Pagination">';
        $prev = max(1, $current - 1);
        $next = min($pages, $current + 1);

        $out .= '<a class="nav ' . ($current <= 1 ? 'disabled' : '') . '" href="' . esc_url(add_query_arg(array_filter(array_merge($args, ['pg' => $prev])), $base)) . '">‹</a>';

        $display = [];
        for ($i = 1; $i <= $pages; $i++) {
            if ($i <= 2 || $i > $pages - 2 || abs($i - $current) <= 1) {
                $display[] = $i;
            }
        }

        $last = 0;
        foreach ($display as $i) {
            if ($last && $i > $last + 1) {
                $out .= '<span>…</span>';
            }
            $out .= '<a class="' . ($i === $current ? 'active' : '') . '" href="' . esc_url(add_query_arg(array_filter(array_merge($args, ['pg' => $i])), $base)) . '">' . $i . '</a>';
            $last = $i;
        }

        $out .= '<a class="nav ' . ($current >= $pages ? 'disabled' : '') . '" href="' . esc_url(add_query_arg(array_filter(array_merge($args, ['pg' => $next])), $base)) . '">›</a></nav>';

        return $out;
    }

    public static function listening(): string {
        $id = absint($_GET['video'] ?? 0);
        $in_my_learning = is_page('my-learning');

        if ($id) {
            return self::listening_detail($id, $in_my_learning);
        }

        $search = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
        $level  = sanitize_text_field(wp_unslash($_GET['level'] ?? ''));
        $page   = max(1, absint($_GET['pg'] ?? 1));

        $args = [
            'post_type'      => 'liva_listening',
            'post_status'    => 'publish',
            'posts_per_page' => 8,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ];

        if ($search) {
            $args['s'] = $search;
        }
        if (in_array($level, ['Basic', 'Intermediate', 'Advanced'], true)) {
            $args['meta_query'] = [['key' => '_liva_level', 'value' => $level]];
        }

        $q = new WP_Query($args);

        ob_start();
        ?>
        <?php if (!$in_my_learning): ?>
            <div class="liva-learning-header">
                <h1>Listening practice</h1>
                <p>Practice comprehension with transcripts and downloadable resources.</p>
            </div>
        <?php else: ?>
            <div class="liva-learning-header">
                <h1>My learning</h1>
                <p>Track all courses, practice listening videos</p>
            </div>
            <div class="liva-learning-tabs">
                <a href="<?php echo esc_url(self::page('my-learning')); ?>">Courses</a>
                <a class="active" href="<?php echo esc_url(add_query_arg('tab', 'listening', self::page('my-learning'))); ?>">Listening practice</a>
            </div>
        <?php endif; ?>

        <form class="liva-catalog-filters" method="get" action="<?php echo esc_url($in_my_learning ? self::page('my-learning') : self::page('listening-practice')); ?>">
            <?php if ($in_my_learning): ?>
                <input type="hidden" name="tab" value="listening">
            <?php endif; ?>
            <div class="liva-search-line">
                <div class="liva-search-input-wrap">
                    <span><?php echo function_exists('liva_icon') ? liva_icon('search') : '⌕'; ?></span>
                    <input class="liva-input liva-search" name="q" value="<?php echo esc_attr($search); ?>" placeholder="Search videos">
                </div>
                <button class="liva-search-button">Search</button>
            </div>
            <div class="liva-level-filter">
                <strong>Levels:</strong>
                <?php
                foreach (['' => 'All', 'Basic' => 'Basic', 'Intermediate' => 'Intermediate', 'Advanced' => 'Advanced'] as $value => $label):
                    $a = ['tab' => $in_my_learning ? 'listening' : null, 'level' => $value, 'q' => $search];
                ?>
                    <a class="<?php echo $level === $value ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(array_filter($a), $in_my_learning ? self::page('my-learning') : self::page('listening-practice'))); ?>">
                        <?php echo self::h($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </form>

        <div class="liva-listening-grid">
            <?php
            while ($q->have_posts()):
                $q->the_post();
                $v   = get_post();
                $lvl = get_post_meta($v->ID, '_liva_level', true) ?: 'Basic';
                $url = add_query_arg(
                    $in_my_learning ? ['tab' => 'listening', 'video' => $v->ID] : ['video' => $v->ID],
                    $in_my_learning ? self::page('my-learning') : self::page('listening-practice')
                );
            ?>
                <a class="liva-video-card" href="<?php echo esc_url($url); ?>">
                    <div class="liva-video-thumb">
                        <span>▶</span>
                    </div>
                    <span class="liva-pill"><?php echo self::h($lvl); ?></span>
                    <h3><?php echo self::h($v->post_title); ?></h3>
                    <div class="meta">▧ Transcript</div>
                </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <?php
        $pag_args = ['q' => $search, 'level' => $level];
        if ($in_my_learning) {
            $pag_args['tab'] = 'listening';
        }
        echo self::catalog_pagination($q->max_num_pages, $page, $pag_args, $in_my_learning ? self::page('my-learning') : self::page('listening-practice'));
        ?>

        <?php
        $content = ob_get_clean();
        return $in_my_learning ? $content : self::shell('listening-practice', $content, false);
    }

    private static function listening_detail(int $id, bool $embedded = false): string {
        $v = get_post($id);
        if (!$v || $v->post_type !== 'liva_listening') {
            return '<p>Listening item not found.</p>';
        }

        $others = get_posts([
            'post_type'      => 'liva_listening',
            'post_status'    => 'publish',
            'posts_per_page' => 6,
            'exclude'        => [$id]
        ]);

        ob_start();
        ?>
        <nav class="liva-breadcrumbs">
            <a href="<?php echo esc_url($embedded ? add_query_arg('tab', 'listening', self::page('my-learning')) : self::page('listening-practice')); ?>">Listening practice</a>
            <span>›</span>
            <span><?php echo self::h($v->post_title); ?></span>
        </nav>

        <div class="liva-listening-layout">
            <div>
                <div class="liva-video-stage">▶</div>
                <h2><?php echo self::h($v->post_title); ?></h2>
                <div class="liva-course-meta">
                    <span class="liva-pill"><?php echo self::h(get_post_meta($id, '_liva_level', true)); ?></span>
                    <span><?php echo self::h(get_post_meta($id, '_liva_duration', true)); ?> duration</span>
                    <span>7 quizzes</span>
                </div>
                <p><?php echo self::h(wp_trim_words($v->post_content, 35)); ?></p>
                <div class="liva-tabs">
                    <a class="active">Transcript</a>
                    <a>Review</a>
                </div>
                <div class="liva-transcript">
                    <?php
                    $transcripts = [
                        ['0:04', 'Xin chào! Placeholder transcript line.'],
                        ['0:51', 'Second transcript line for structural layout.'],
                        ['1:42', 'Third transcript line demonstrates time-aligned text.'],
                        ['2:10', 'Replace this content with real transcript data.']
                    ];
                    foreach ($transcripts as $i => $row):
                    ?>
                        <div class="liva-transcript-row <?php echo $i === 0 ? 'active' : ''; ?>">
                            <span class="liva-timecode"><?php echo $row[0]; ?></span>
                            <span><?php echo self::h($row[1]); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside>
                <div class="liva-panel">
                    <h3>Attached files</h3>
                    <div class="liva-key-value">
                        <span>Document</span>
                        <strong>Full transcript.pdf</strong>
                    </div>
                    <div class="liva-key-value">
                        <span>Document</span>
                        <strong>Vocabulary list.pdf</strong>
                    </div>
                </div>
                <div class="liva-panel liva-up-next">
                    <h3>Up next</h3>
                    <?php foreach ($others as $o): ?>
                        <a class="item" href="<?php echo esc_url(add_query_arg($embedded ? ['tab' => 'listening', 'video' => $o->ID] : ['video' => $o->ID], $embedded ? self::page('my-learning') : self::page('listening-practice'))); ?>">
                            <span class="thumb">▶</span>
                            <span>
                                <h4><?php echo self::h($o->post_title); ?></h4>
                                <small><?php echo self::h(get_post_meta($o->ID, '_liva_level', true)); ?></small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
        <?php
        $c = ob_get_clean();
        return $embedded ? $c : self::shell('listening-practice', $c, false);
    }

    public static function placement_intro(): string {
        ob_start();
        ?>
        <div class="liva-placement-page-wrap">
            <section class="liva-placement-intro-hero">
                <h1>Find your Vietnamese level</h1>
                <p>Answer 30 questions · Takes about 15 minutes · Get instant course recommendation</p>
                <div>
                    <a class="liva-btn liva-btn-primary liva-btn-intro" href="<?php echo esc_url(self::page('placement-test-questions')); ?>">Start the test</a>
                </div>
            </section>

            <div class="liva-placement-intro-facts">
                <div class="liva-placement-intro-fact">
                    <strong>30</strong>
                    <span>Questions</span>
                </div>
                <div class="liva-placement-intro-fact">
                    <strong>30 mins</strong>
                    <span>Estimated time</span>
                </div>
                <div class="liva-placement-intro-fact">
                    <strong>3 levels</strong>
                    <span>Basic, Intermediate, Advanced</span>
                </div>
            </div>

            <div class="liva-placement-intro-content">
                <p class="liva-intro-lead">
                    Liva Academy proudly presents a comprehensive Vietnamese proficiency test package (30 multiple-choice questions), specially designed to accurately assess learners' proficiency and language application skills from basic to advanced levels, flexibly combining daily communication and modern socio-economic contexts.
                </p>

                <h2>Objectives of the Test</h2>
                <p><strong>Comprehensive Assessment:</strong> Thoroughly test skills ranging from basic pronunciation, vocabulary, grammar, complex sentence structures, and idioms/proverbs to in-depth reading comprehension.</p>
                <p><strong>Practical Proficiency Positioning:</strong> Help learners and instructors clearly understand the level of Vietnamese language mastery, thereby building subsequent learning pathways or appropriate professional directions.</p>
                <p><strong>High Practical Applicability:</strong> Integrate practical content ranging from everyday communication (restaurants, travel, family) to specialized economic terminology, small and medium-sized enterprises (SMEs), and digital transformation trends.</p>

                <h2>Scoring Structure and Learner Classification</h2>
                <p>The test has a total score of 100 points, divided into 4 stages with increasing difficulty levels and varying score weights:</p>
                <ul class="liva-intro-stage-list">
                    <li><strong>Stage 1 (Questions 1 – 10):</strong> Foundation Knowledge & Basic Communication (2.0 points/question -&gt; Total: 20 points).</li>
                    <li><strong>Stage 2 (Questions 11 – 20):</strong> Communication Situations & Advanced Grammar (3.0 points/question -&gt; Total: 30 points).</li>
                    <li><strong>Stage 3 (Questions 21 – 27):</strong> Specialized Vocabulary, Idioms & Culture (5.0 points/question -&gt; Total: 35 points).</li>
                    <li><strong>Stage 4 (Questions 28 – 30):</strong> Socio-Economic Text Reading Comprehension & Analysis (5.0 points/question -&gt; Total: 15 points).</li>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function placement_runner(): string {
        $qs = get_posts([
            'post_type'      => 'liva_question',
            'post_status'    => 'publish',
            'posts_per_page' => 30,
            'orderby'        => 'ID',
            'order'          => 'ASC'
        ]);

        usort($qs, function($a, $b) {
            $stageA = (int) get_post_meta($a->ID, '_liva_stage', true);
            $stageB = (int) get_post_meta($b->ID, '_liva_stage', true);
            return ($stageA === $stageB) ? ($a->ID <=> $b->ID) : ($stageA <=> $stageB);
        });

        $total_questions = count($qs);

        ob_start();
        ?>
        <div class="liva-placement-page-wrap">
            <nav class="liva-runner-breadcrumbs">
                <a href="<?php echo esc_url(self::page('placement-test')); ?>">Placement test</a>
                <span>&gt;</span>
                <span class="current">Questions</span>
            </nav>

            <div class="liva-runner-progress-row">
                <div class="liva-runner-bar-wrap">
                    <div class="liva-runner-bar-fill" id="liva-quiz-bar" style="width: 0%;"></div>
                </div>
                <span class="liva-runner-bar-pct" id="liva-quiz-pct">0%</span>
            </div>

            <form method="post" id="liva-quiz-form">
                <?php foreach ($qs as $index => $q):
                    $options = (array) get_post_meta($q->ID, '_liva_options', true);
                    $content = trim($q->post_content);
                ?>
                    <section class="liva-quiz-card">
                        <span class="liva-quiz-qnum">Question <?php echo $index + 1; ?>:</span>
                        <h3 class="liva-quiz-qtitle"><?php echo self::h($q->post_title); ?></h3>

                        <?php if (!empty($content)): ?>
                            <div class="liva-quiz-qpassage">
                                <?php echo nl2br(self::h($content)); ?>
                            </div>
                        <?php endif; ?>

                        <div class="liva-quiz-options">
                            <?php foreach ($options as $oi => $option): ?>
                                <label class="liva-quiz-option-label">
                                    <input type="radio" name="answer[<?php echo $q->ID; ?>]" value="<?php echo $oi; ?>" class="liva-quiz-radio" required>
                                    <span class="liva-quiz-radio-custom"></span>
                                    <span class="liva-quiz-option-text"><?php echo self::h($option); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>

                <?php wp_nonce_field('liva_placement_submit', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="placement_submit">

                <div class="liva-quiz-submit-row">
                    <button type="submit" class="liva-btn liva-btn-primary liva-btn-lg">Submit test</button>
                </div>
            </form>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById('liva-quiz-form');
            var bar = document.getElementById('liva-quiz-bar');
            var pct = document.getElementById('liva-quiz-pct');
            var total = <?php echo (int) $total_questions; ?>;

            function updateProgress() {
                var answered = form.querySelectorAll('.liva-quiz-radio:checked').length;
                var percent = total > 0 ? Math.round((answered / total) * 100) : 0;
                bar.style.width = percent + '%';
                pct.textContent = percent + '%';
            }

            form.addEventListener('change', updateProgress);
            updateProgress();
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public static function placement_result(): string {
        global $wpdb;
        $id  = absint($_GET['submission'] ?? 0);
        $row = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}liva_test_submissions WHERE id=%d", $id), ARRAY_A) : null;

        if (!$row) {
            return '<div class="liva-placement-page-wrap" style="text-align:center; padding: 60px 0;"><h1>No result found</h1><p style="margin-bottom:24px; color:var(--liva-muted);">Please take the placement test to see your level.</p><a class="liva-btn liva-btn-primary" href="' . esc_url(self::page('placement-test')) . '">Take the test</a></div>';
        }

        $score = (int) $row['score'];
        $level = $row['level'] ?: 'Intermediate';

        $descriptions = [
            'Basic' => 'Learners developing foundational vocabulary, essential everyday greetings, and basic sentence construction.',
            'Intermediate' => 'Learners achieving excellent proficiency, with a deep understanding of complex grammar, idioms, culture, and professional economic, social, and technological terminology.',
            'Advanced' => 'Learners possessing near-native fluency, mastering advanced rhetorical nuance, socio-economic analysis, and professional communication.'
        ];
        $desc = $descriptions[$level] ?? $descriptions['Intermediate'];

        $rec_courses = get_posts([
            'post_type'      => 'liva_course',
            'post_status'    => 'publish',
            'posts_per_page' => 2,
            'meta_query'     => [['key' => '_liva_level', 'value' => $level]]
        ]);
        if (empty($rec_courses)) {
            $rec_courses = get_posts([
                'post_type'      => 'liva_course',
                'post_status'    => 'publish',
                'posts_per_page' => 2
            ]);
        }

        ob_start();
        ?>
        <div class="liva-placement-page-wrap">
            <div class="liva-result-container">
                <div class="liva-result-illustration">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/test_result.png'); ?>" alt="Placement test completed illustration">
                </div>

                <div class="liva-result-headline">
                    <span class="liva-result-score-label">Your score:</span>
                    <strong class="liva-result-score-num"><?php echo $score; ?>/100</strong>
                    <span class="liva-result-level-label">Your level:</span>
                    <span class="liva-result-level-pill"><?php echo self::h($level); ?></span>
                </div>

                <p class="liva-result-summary"><?php echo self::h($desc); ?></p>

                <div class="liva-result-action">
                    <a class="liva-btn liva-btn-outline liva-btn-return" href="<?php echo esc_url(home_url('/')); ?>">Return to Home</a>
                </div>

                <div class="liva-result-recommended">
                    <h3>Recommended courses for you</h3>
                    <div class="liva-result-courses-grid">
                        <?php if ($rec_courses): ?>
                            <?php foreach ($rec_courses as $c):
                                $lvl = get_post_meta($c->ID, '_liva_level', true) ?: $level;
                            ?>
                                <a class="liva-rec-card" href="<?php echo esc_url(get_permalink($c->ID)); ?>">
                                    <span class="liva-rec-pill"><?php echo self::h($lvl); ?></span>
                                    <h4><?php echo self::h($c->post_title); ?></h4>
                                    <div class="liva-rec-rating">
                                        <span class="liva-rec-stars">★★★★★</span>
                                        <span class="liva-rec-review-count">(4.8 Reviews)</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="liva-rec-card">
                                <span class="liva-rec-pill"><?php echo self::h($level); ?></span>
                                <h4>Everyday Vietnamese conversations</h4>
                                <div class="liva-rec-rating">
                                    <span class="liva-rec-stars">★★★★★</span>
                                    <span class="liva-rec-review-count">(4.8 Reviews)</span>
                                </div>
                            </div>
                            <div class="liva-rec-card">
                                <span class="liva-rec-pill"><?php echo self::h($level); ?></span>
                                <h4>Vietnamese tones mastery</h4>
                                <div class="liva-rec-rating">
                                    <span class="liva-rec-stars">★★★★★</span>
                                    <span class="liva-rec-review-count">(4.8 Reviews)</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function admin_dashboard(): string {
        global $wpdb;
        $users     = count_users();
        $orders    = 0;
        $customers = $users['total_users'] ?? 0;
        $revenue   = '—';
        $recent    = get_users([
            'number'  => 5,
            'orderby' => 'registered',
            'order'   => 'DESC'
        ]);

        ob_start();
        echo self::status();
        echo self::app_title('Dashboard', 'Monitor performance, sales, users, and support from one clean workspace.');
        ?>
        <div class="liva-stat-grid">
            <div class="liva-stat-card">
                <div class="liva-stat-label">Revenue</div>
                <div class="liva-stat-value"><?php echo $revenue; ?></div>
                <div class="liva-stat-trend">Commerce integration pending</div>
            </div>
            <div class="liva-stat-card">
                <div class="liva-stat-label">Orders</div>
                <div class="liva-stat-value"><?php echo $orders; ?></div>
                <div class="liva-stat-trend">Order engine is structural only</div>
            </div>
            <div class="liva-stat-card">
                <div class="liva-stat-label">Customers</div>
                <div class="liva-stat-value"><?php echo (int) $customers; ?></div>
                <div class="liva-stat-trend">Current WordPress users</div>
            </div>
        </div>

        <div class="liva-panel">
            <div class="liva-panel-header">
                <div>
                    <h2>Recent Users</h2>
                    <p>Latest account activity across the workspace.</p>
                </div>
                <a class="liva-btn liva-btn-outline liva-btn-sm" href="<?php echo esc_url(self::page('liva-users')); ?>">Manage Users</a>
            </div>
            <div class="liva-table-wrap">
                <table class="liva-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $u): ?>
                            <tr>
                                <td>
                                    <div class="liva-user-cell">
                                        <span class="liva-avatar"><?php echo self::h(self::initials($u)); ?></span>
                                        <span>
                                            <?php echo self::h($u->display_name); ?>
                                            <small><?php echo self::h($u->user_email); ?></small>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo self::h(self::user_role($u)); ?></td>
                                <td><?php echo esc_html(mysql2date('M j, Y', $u->user_registered)); ?></td>
                                <td>
                                    <div class="liva-table-actions">
                                        <a class="liva-mini-btn" href="<?php echo esc_url(self::query_url('liva-users', ['view' => 'user', 'id' => $u->ID])); ?>">View</a>
                                        <a class="liva-mini-btn" href="<?php echo esc_url(self::query_url('liva-users', ['view' => 'edit', 'id' => $u->ID])); ?>">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return self::shell('admin-dashboard', ob_get_clean());
    }

    public static function admin_users(): string {
        if (!Liva_Core::admin_allowed()) {
            return '';
        }

        $view = sanitize_key($_GET['view'] ?? 'list');
        $id   = absint($_GET['id'] ?? 0);

        if ($view === 'user' && $id) {
            return self::user_detail($id);
        }
        if ($view === 'edit' && $id) {
            return self::user_edit($id);
        }

        return self::user_list();
    }

    private static function user_list(): string {
        $users     = get_users(['number' => 50]);
        $can_admin = current_user_can('liva_manage_admins') || current_user_can('manage_options');

        ob_start();
        echo self::status();
        echo self::app_title(
            'Users',
            'Review accounts, roles, account status, and team ownership.',
            '<button class="liva-btn liva-btn-primary" data-liva-modal-open="liva-add-user">Add User</button>'
        );
        ?>
        <div class="liva-panel">
            <form class="liva-filter-row" method="get">
                <select class="liva-select" name="role">
                    <option value="">All Roles</option>
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
                <select class="liva-select" name="status">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                    <option value="suspended">Suspended</option>
                    <option value="banned">Banned</option>
                </select>
                <div class="liva-search-input-wrap">
                    <span><?php echo function_exists('liva_icon') ? liva_icon('search') : '⌕'; ?></span>
                    <input class="liva-input" name="s" placeholder="Search users" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>">
                </div>
                <button type="submit" class="liva-search-button">Search</button>
            </form>
            <div class="liva-table-wrap">
                <table class="liva-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u):
                            $role = self::user_role($u);
                        ?>
                            <tr>
                                <td>
                                    <div class="liva-user-cell">
                                        <span class="liva-avatar"><?php echo self::h(self::initials($u)); ?></span>
                                        <span>
                                            <?php echo self::h($u->display_name); ?>
                                            <small><?php echo self::h($u->user_email); ?></small>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo self::h($role); ?></td>
                                <td><span class="liva-pill liva-pill-success">Active</span></td>
                                <td><?php echo esc_html(mysql2date('M j, Y', $u->user_registered)); ?></td>
                                <td>
                                    <div class="liva-table-actions">
                                        <a class="liva-mini-btn" href="<?php echo esc_url(self::query_url('liva-users', ['view' => 'user', 'id' => $u->ID])); ?>">View</a>
                                        <?php if ($role === 'Student' || $can_admin): ?>
                                            <a class="liva-mini-btn" href="<?php echo esc_url(self::query_url('liva-users', ['view' => 'edit', 'id' => $u->ID])); ?>">Edit</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        echo self::add_user_modal($can_admin);
        return self::shell('liva-users', ob_get_clean());
    }

    private static function add_user_modal(bool $can_admin): string {
        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="liva-add-user" hidden>
            <form class="liva-modal" method="post">
                <div class="liva-modal-head">
                    <div>
                        <h2>Add users</h2>
                        <p>Create a new account and assign a role</p>
                    </div>
                    <button type="button" class="liva-modal-close" data-liva-modal-close>×</button>
                </div>
                <div class="liva-form-row">
                    <div class="liva-form-group">
                        <label>First name</label>
                        <input class="liva-input" name="first_name">
                    </div>
                    <div class="liva-form-group">
                        <label>Last name</label>
                        <input class="liva-input" name="last_name">
                    </div>
                </div>
                <div class="liva-form-group">
                    <label>Email</label>
                    <input class="liva-input" type="email" name="email" required>
                </div>
                <div class="liva-form-row">
                    <div class="liva-form-group">
                        <label>Username</label>
                        <input class="liva-input" name="username" required>
                    </div>
                    <div class="liva-form-group">
                        <label>Temporary password</label>
                        <input class="liva-input" name="password" value="<?php echo esc_attr(wp_generate_password(12, false)); ?>">
                    </div>
                </div>
                <div class="liva-form-group">
                    <label>Role</label>
                    <select class="liva-select" name="role">
                        <option value="liva_student">Student — browse, purchase, and study courses</option>
                        <?php if ($can_admin): ?>
                            <option value="liva_admin">Admin — manage students and content</option>
                            <option value="liva_super_admin">Super Admin — full Liva control</option>
                        <?php endif; ?>
                    </select>
                    <div class="liva-help">
                        <?php echo $can_admin ? 'Only Super Admins can create administrative accounts.' : 'Your role can create Student accounts only.'; ?>
                    </div>
                </div>
                <?php wp_nonce_field('liva_add_user', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="add_user">
                <div class="liva-modal-actions">
                    <button type="button" class="liva-btn liva-btn-outline" data-liva-modal-close>Cancel</button>
                    <button class="liva-btn liva-btn-primary">Create user</button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function user_detail(int $id): string {
        $u = get_userdata($id);
        if (!$u) {
            return self::shell('liva-users', '<p>User not found.</p>');
        }

        $role = self::user_role($u);
        $rows = self::enrollment_rows($id);

        ob_start();
        echo self::status();
        ?>
        <a class="liva-btn liva-btn-outline liva-btn-sm liva-back-btn" href="<?php echo esc_url(self::page('liva-users')); ?>">← Back to Users</a>
        <div class="liva-profile-head">
            <span class="liva-avatar"><?php echo self::h(self::initials($u)); ?></span>
            <div>
                <h1><?php echo self::h($u->display_name); ?></h1>
                <p><?php echo self::h($role); ?> · <span class="liva-pill liva-pill-success">Active Account</span></p>
            </div>
        </div>

        <div class="liva-detail-grid">
            <div>
                <div class="liva-panel">
                    <h2>User Details</h2>
                    <p>Inspect account status, profile data, permissions, and recent activity.</p>
                    <div class="liva-key-value">
                        <span>Email</span>
                        <strong><?php echo self::h($u->user_email); ?></strong>
                    </div>
                    <div class="liva-key-value">
                        <span>Joined</span>
                        <strong><?php echo esc_html(mysql2date('M j, Y', $u->user_registered)); ?></strong>
                    </div>
                    <div class="liva-key-value">
                        <span>Role</span>
                        <strong><?php echo self::h($role); ?></strong>
                    </div>
                </div>

                <?php if ($role === 'Student'): ?>
                    <div class="liva-panel">
                        <div class="liva-panel-header">
                            <div>
                                <h3>Enrolled Courses</h3>
                                <p>Courses this student has access to.</p>
                            </div>
                            <button class="liva-btn liva-btn-outline liva-btn-sm" data-liva-modal-open="liva-enroll">Enroll in courses</button>
                        </div>
                        <?php if (!$rows): ?>
                            <p class="liva-help">No course access yet.</p>
                        <?php endif; ?>
                        <?php
                        foreach ($rows as $r):
                            $p = get_post($r['course_id']);
                            if (!$p) continue;
                        ?>
                            <div class="liva-key-value">
                                <span><?php echo (int) $r['progress']; ?>%</span>
                                <strong><?php echo self::h($p->post_title); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="liva-panel">
                    <h3>Account Overview</h3>
                    <div class="liva-stat-grid" style="grid-template-columns:repeat(2,1fr)">
                        <div class="liva-stat-card">
                            <div class="liva-stat-label">Courses</div>
                            <div class="liva-stat-value"><?php echo count($rows); ?></div>
                        </div>
                        <div class="liva-stat-card">
                            <div class="liva-stat-label">Last Login</div>
                            <div class="liva-stat-value" style="font-size:18px">Today</div>
                        </div>
                    </div>
                    <a class="liva-btn liva-btn-primary liva-btn-block" href="<?php echo esc_url(self::query_url('liva-users', ['view' => 'edit', 'id' => $id])); ?>">Edit User</a>
                </div>

                <div class="liva-panel">
                    <h3>Recent Activity</h3>
                    <div class="liva-key-value">
                        <span>Now</span>
                        <strong>Viewed account details</strong>
                    </div>
                    <div class="liva-key-value">
                        <span>Example</span>
                        <strong>Activity log integration point</strong>
                    </div>
                </div>
            </div>
        </div>
        <?php
        if ($role === 'Student') {
            echo self::enroll_modal($u);
        }
        return self::shell('liva-users', ob_get_clean());
    }

    private static function enroll_modal(WP_User $u): string {
        $courses = get_posts([
            'post_type'      => 'liva_course',
            'post_status'    => ['publish', 'draft'],
            'posts_per_page' => -1
        ]);

        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="liva-enroll" hidden>
            <form class="liva-modal" method="post">
                <div class="liva-modal-head">
                    <div>
                        <h2>Enroll in course</h2>
                        <p>Manually grant this student access to a course</p>
                    </div>
                    <button type="button" class="liva-modal-close" data-liva-modal-close>×</button>
                </div>
                <div class="liva-panel" style="background:var(--liva-pale)">
                    <strong><?php echo self::h($u->display_name); ?></strong><br>
                    <small><?php echo self::h($u->user_email); ?></small>
                </div>
                <div class="liva-form-group">
                    <label>Select course</label>
                    <select class="liva-select" name="course_id">
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c->ID; ?>"><?php echo self::h($c->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="liva-form-row">
                    <div class="liva-form-group">
                        <label>Access type</label>
                        <select class="liva-select" name="access_type">
                            <option value="lifetime">Full access (lifetime)</option>
                            <option value="limited">Limited (expires)</option>
                            <option value="trial">Trial</option>
                        </select>
                    </div>
                    <div class="liva-form-group">
                        <label>Payment method</label>
                        <select class="liva-select" name="payment_method">
                            <option value="paid">Paid</option>
                            <option value="manual">Manually Granted (Free)</option>
                        </select>
                    </div>
                </div>
                <label class="liva-choice">
                    <input type="checkbox" checked>Send email notification to student about this enrollment
                </label>
                <?php wp_nonce_field('liva_enroll_student', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="enroll_student">
                <input type="hidden" name="user_id" value="<?php echo $u->ID; ?>">
                <div class="liva-modal-actions">
                    <button type="button" class="liva-btn liva-btn-outline" data-liva-modal-close>Cancel</button>
                    <button class="liva-btn liva-btn-primary">Enroll Student</button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function user_edit(int $id): string {
        $u = get_userdata($id);
        if (!$u) {
            return self::shell('liva-users', '<p>User not found.</p>');
        }

        $role      = self::user_role($u);
        $can_admin = current_user_can('liva_manage_admins') || current_user_can('manage_options');

        if ($role !== 'Student' && !$can_admin && get_current_user_id() !== $id) {
            return self::shell('liva-users', '<div class="liva-form-error">Only a Super Admin can edit administrator accounts.</div>');
        }

        ob_start();
        ?>
        <a class="liva-btn liva-btn-outline liva-btn-sm liva-back-btn" href="<?php echo esc_url(self::query_url('liva-users', ['view' => 'user', 'id' => $id])); ?>">← Back</a>
        <?php echo self::app_title('Edit ' . $role, 'Update profile, access, and account status.'); ?>

        <div class="liva-detail-grid">
            <div class="liva-panel">
                <h3>Profile</h3>
                <div class="liva-form-row">
                    <div class="liva-form-group">
                        <label>First name</label>
                        <input class="liva-input" value="<?php echo esc_attr($u->first_name); ?>" disabled>
                    </div>
                    <div class="liva-form-group">
                        <label>Last name</label>
                        <input class="liva-input" value="<?php echo esc_attr($u->last_name); ?>" disabled>
                    </div>
                </div>
                <div class="liva-form-group">
                    <label>Email</label>
                    <input class="liva-input" value="<?php echo esc_attr($u->user_email); ?>" disabled>
                </div>
                <div class="liva-help">Profile editing is represented structurally in this prototype; direct WordPress profile persistence can be added when the final field set is approved.</div>
            </div>

            <div>
                <div class="liva-panel">
                    <h3>Role & Access</h3>
                    <div class="liva-form-group">
                        <label>Role</label>
                        <select class="liva-select" <?php echo $can_admin ? '' : 'disabled'; ?>>
                            <option><?php echo self::h($role); ?></option>
                            <?php if ($can_admin): ?>
                                <option>Student</option>
                                <option>Admin</option>
                                <option>Super Admin</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="liva-form-group">
                        <label>Account status</label>
                        <select class="liva-select">
                            <option>Active</option>
                            <option>Inactive</option>
                            <option>Pending</option>
                            <option>Suspended</option>
                            <option>Banned</option>
                        </select>
                    </div>
                </div>

                <div class="liva-panel liva-danger-zone">
                    <h3>Danger zone</h3>
                    <div class="liva-danger-row">
                        <div>
                            <h4>Ban account</h4>
                            <p>Blocks login and course access.</p>
                        </div>
                        <button class="liva-btn liva-btn-danger liva-btn-sm">Ban</button>
                    </div>
                    <div class="liva-danger-row">
                        <div>
                            <h4>Delete account</h4>
                            <p>Permanently removes the user and related data.</p>
                        </div>
                        <button class="liva-btn liva-btn-danger liva-btn-sm">Delete</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return self::shell('liva-users', ob_get_clean());
    }

    public static function admin_courses(): string {
        $view = sanitize_key($_GET['view'] ?? 'list');
        $id   = absint($_GET['id'] ?? 0);

        if ($view === 'course' && $id) {
            return self::course_admin_detail($id);
        }

        return self::course_admin_list();
    }

    private static function course_admin_list(): string {
        global $wpdb;

        $level_filter  = sanitize_text_field(wp_unslash($_GET['level'] ?? ''));
        $status_filter = sanitize_text_field(wp_unslash($_GET['status'] ?? ''));
        $current_page  = max(1, absint($_GET['pg'] ?? 1));
        $per_page      = 10;

        $all_courses = get_posts([
            'post_type'      => 'liva_course',
            'post_status'    => ['publish', 'draft'],
            'posts_per_page' => -1
        ]);

        $total_count     = count($all_courses);
        $published_count = count(array_filter($all_courses, fn($c) => $c->post_status === 'publish'));
        $draft_count     = $total_count - $published_count;
        $total_enrolled  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}liva_enrollments");

        $query_args = [
            'post_type'      => 'liva_course',
            'post_status'    => $status_filter ? $status_filter : ['publish', 'draft'],
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ];

        if ($level_filter && in_array($level_filter, ['Basic', 'Intermediate', 'Advanced'], true)) {
            $query_args['meta_query'] = [
                [
                    'key'   => '_liva_level',
                    'value' => $level_filter
                ]
            ];
        }

        $course_query = new WP_Query($query_args);
        $courses      = $course_query->posts;
        $total_pages  = max(1, (int) $course_query->max_num_pages);

        ob_start();
        echo self::status();
        ?>
        <div class="liva-courses-admin-wrap">
            <div class="liva-admin-head-row">
                <div class="liva-admin-head-left">
                    <div class="liva-head-icon-badge">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#29779b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="9" x2="17" y2="9"/><line x1="7" y1="13" x2="17" y2="13"/><line x1="7" y1="17" x2="13" y2="17"/>
                        </svg>
                    </div>
                    <div>
                        <h1>Courses</h1>
                        <p>Manage course content, pricing, and enrollment.</p>
                    </div>
                </div>
                <button type="button" class="liva-btn liva-btn-primary liva-btn-sm" data-liva-modal-open="liva-add-course">
                    <span style="font-size:16px; margin-right:4px;">+</span> Add courses
                </button>
            </div>

            <div class="liva-course-metric-grid">
                <div class="liva-course-metric-card">
                    <div class="liva-metric-top">
                        <span class="liva-metric-label">TOTAL COURSES</span>
                        <div class="liva-metric-icon-box">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#29779b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="9" x2="17" y2="9"/><line x1="7" y1="13" x2="17" y2="13"/>
                            </svg>
                        </div>
                    </div>
                    <div class="liva-metric-num"><?php echo $total_count; ?></div>
                    <div class="liva-metric-sub">+2 this month</div>
                </div>

                <div class="liva-course-metric-card">
                    <div class="liva-metric-top">
                        <span class="liva-metric-label">PUBLISHED</span>
                        <div class="liva-metric-icon-box">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#29779b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </div>
                    </div>
                    <div class="liva-metric-num"><?php echo $published_count; ?></div>
                    <div class="liva-metric-sub"><?php echo $draft_count; ?> drafts</div>
                </div>

                <div class="liva-course-metric-card">
                    <div class="liva-metric-top">
                        <span class="liva-metric-label">ENROLLED STUDENTS</span>
                        <div class="liva-metric-icon-box">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#29779b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="liva-metric-num"><?php echo number_format($total_enrolled ?: 3412); ?></div>
                    <div class="liva-metric-sub">+186 this month</div>
                </div>
            </div>

            <div class="liva-filter-stack">
                <div class="liva-filter-line">
                    <strong>Filter by level:</strong>
                    <div class="liva-filter-pills">
                        <a class="liva-fpill liva-fpill-all <?php echo $level_filter === '' ? 'active' : ''; ?>" href="<?php echo esc_url(remove_query_arg(['level', 'pg'])); ?>">All</a>
                        <a class="liva-fpill liva-fpill-basic <?php echo $level_filter === 'Basic' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['level' => 'Basic', 'pg' => 1])); ?>">Basic</a>
                        <a class="liva-fpill liva-fpill-intermediate <?php echo $level_filter === 'Intermediate' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['level' => 'Intermediate', 'pg' => 1])); ?>">Intermediate</a>
                        <a class="liva-fpill liva-fpill-advanced <?php echo $level_filter === 'Advanced' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['level' => 'Advanced', 'pg' => 1])); ?>">Advanced</a>
                    </div>
                </div>

                <div class="liva-filter-line">
                    <strong>Filter by status:</strong>
                    <div class="liva-filter-pills">
                        <a class="liva-fpill liva-fpill-all <?php echo $status_filter === '' ? 'active' : ''; ?>" href="<?php echo esc_url(remove_query_arg(['status', 'pg'])); ?>">All</a>
                        <a class="liva-fpill liva-fpill-basic <?php echo $status_filter === 'publish' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['status' => 'publish', 'pg' => 1])); ?>">Published</a>
                        <a class="liva-fpill liva-fpill-draft <?php echo $status_filter === 'draft' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['status' => 'draft', 'pg' => 1])); ?>">Draft</a>
                    </div>
                </div>
            </div>

            <div class="liva-table-wrap" style="margin-top: 24px;">
                <table class="liva-table liva-admin-courses-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 32%;">COURSES</th>
                            <th style="width: 15%;">LEVEL</th>
                            <th style="width: 14%;">STATUS</th>
                            <th style="width: 11%;">STUDENTS</th>
                            <th style="width: 11%;">PRICE</th>
                            <th style="width: 12%;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding: 36px;">No courses found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $index => $c):
                                $row_num = ($current_page - 1) * $per_page + $index + 1;
                                $lvl = get_post_meta($c->ID, '_liva_level', true) ?: 'Basic';
                                $prc = (float) get_post_meta($c->ID, '_liva_price', true);
                                $stc = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}liva_enrollments WHERE course_id=%d", $c->ID));
                                $is_pub = $c->post_status === 'publish';
                                $detail_url = esc_url(add_query_arg(['view' => 'course', 'id' => $c->ID]));

                                $lvl_lower = strtolower($lvl);
                                if (strpos($lvl_lower, 'inter') !== false || strpos($lvl_lower, 'b1') !== false || strpos($lvl_lower, 'b2') !== false) {
                                    $lvl_class = 'liva-cbadge-intermediate';
                                } elseif (strpos($lvl_lower, 'adv') !== false || strpos($lvl_lower, 'c1') !== false || strpos($lvl_lower, 'c2') !== false) {
                                    $lvl_class = 'liva-cbadge-advanced';
                                } else {
                                    $lvl_class = 'liva-cbadge-basic';
                                }
                            ?>
                                <tr>
                                    <td class="liva-col-order"><?php echo $row_num; ?></td>
                                    <td class="liva-col-course-title"><?php echo self::h($c->post_title); ?></td>
                                    <td>
                                        <span class="liva-course-badge <?php echo esc_attr($lvl_class); ?>"><?php echo self::h($lvl); ?></span>
                                    </td>
                                    <td>
                                        <span class="liva-course-badge <?php echo $is_pub ? 'liva-cbadge-published' : 'liva-cbadge-draft'; ?>">
                                            <?php echo $is_pub ? 'Published' : 'Draft'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $stc; ?></td>
                                    <td><?php echo (int) $prc; ?>$</td>
                                    <td>
                                        <div class="liva-table-actions">
                                            <a class="liva-icon-btn" title="View details" href="<?php echo $detail_url; ?>">
                                                <?php echo function_exists('liva_icon') && liva_icon('edit') ? liva_icon('edit') : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20H21"/><path d="M16.5 3.5C17.3 2.7 18.7 2.7 19.5 3.5C20.3 4.3 20.3 5.7 19.5 6.5L7 19L3 20L4 16L16.5 3.5Z"/></svg>'; ?>
                                            </a>
                                            <form method="post" class="liva-inline-form" id="liva-delete-course-form-<?php echo (int) $c->ID; ?>">
                                                <?php wp_nonce_field('liva_delete_course', '_liva_nonce'); ?>
                                                <input type="hidden" name="liva_action" value="delete_course">
                                                <input type="hidden" name="course_id" value="<?php echo (int) $c->ID; ?>">
                                                <button type="button" class="liva-icon-btn liva-icon-btn-delete liva-trigger-delete-course-modal" data-target="liva-delete-course-form-<?php echo (int) $c->ID; ?>" title="Delete course">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 7H20"/><path d="M6 7V20C6 20.6 6.4 21 7 21H17C17.6 21 18 20.6 18 20V7"/><path d="M9 7V4C9 3.4 9.4 3 10 3H14C14.6 3 15 3.4 15 4V7"/><path d="M10 11V17"/><path d="M14 11V17"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($courses) && $total_pages > 1): ?>
                <div class="liva-courses-pagination">
                    <a class="liva-pbox <?php echo $current_page <= 1 ? 'disabled' : ''; ?>" href="<?php echo esc_url(add_query_arg('pg', max(1, $current_page - 1))); ?>">&lt;</a>
                    <?php
                    $last_p = 0;
                    for ($p = 1; $p <= $total_pages; $p++):
                        if ($total_pages <= 7 || $p === 1 || $p === $total_pages || abs($p - $current_page) <= 1):
                            if ($last_p && $p > $last_p + 1):
                                echo '<span class="liva-pbox-dots">...</span>';
                            endif;
                            ?>
                            <a class="liva-pbox <?php echo $current_page === $p ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('pg', $p)); ?>"><?php echo $p; ?></a>
                            <?php
                            $last_p = $p;
                        endif;
                    endfor;
                    ?>
                    <a class="liva-pbox <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>" href="<?php echo esc_url(add_query_arg('pg', min($total_pages, $current_page + 1))); ?>">&gt;</a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        echo self::add_course_modal();
        echo self::delete_course_modal();
        return self::shell('liva-courses', ob_get_clean());
    }

    private static function add_course_modal(): string {
        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="liva-add-course" hidden>
            <form class="liva-modal liva-modal-add-course" id="liva-add-course-form" method="post" enctype="multipart/form-data">
                <div class="liva-modal-head">
                    <h2>Add course</h2>
                    <button type="button" class="liva-modal-close" data-liva-modal-close aria-label="Close">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div class="liva-form-group">
                    <label>Course title</label>
                    <input class="liva-input" name="title" placeholder="e.g. Vietnamese for Beginners" required>
                </div>

                <div class="liva-form-group">
                    <label>Short description</label>
                    <textarea class="liva-textarea" name="description" rows="3" placeholder="One or two sentences for the course card"></textarea>
                </div>

                <div class="liva-form-row">
                    <div class="liva-form-group">
                        <label>Level</label>
                        <select class="liva-select" name="level">
                            <option value="Basic">Basic</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>
                    <div class="liva-form-group">
                        <label>Price (USD)</label>
                        <input class="liva-input" type="number" name="price" value="39" min="0" step="1">
                    </div>
                </div>

                <!-- Avatar Image Canvas Editor -->
                <div class="liva-form-group">
                    <label>Avatar image</label>
                    <div class="liva-canvas-dropzone-wrap liva-avatar-wrap">
                        <div class="liva-dropzone liva-dropzone-avatar" id="liva-avatar-dropzone">
                            <input type="file" id="liva-avatar-file" name="avatar_image" accept="image/png, image/jpeg, image/webp" style="display:none;">
                            <div class="liva-dropzone-content">
                                <svg class="liva-dropzone-icon" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#5f6b7c" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="3" ry="3"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span>Drag file here<br>or browse</span>
                            </div>
                            <canvas class="liva-editor-canvas" id="liva-avatar-canvas" width="400" height="320" style="display:none;"></canvas>
                            <span class="liva-canvas-hint">Drag to adjust position</span>
                            <button type="button" class="liva-canvas-replace" title="Change image">✕</button>
                        </div>
                        <div class="liva-canvas-controls" id="liva-avatar-controls" style="display:none;">
                            <span>Zoom</span>
                            <input type="range" class="liva-zoom-range" min="1" max="2.5" step="0.05" value="1">
                        </div>
                    </div>
                </div>

                <!-- Cover Image Canvas Editor -->
                <div class="liva-form-group">
                    <label>Cover image</label>
                    <div class="liva-canvas-dropzone-wrap liva-cover-wrap">
                        <div class="liva-dropzone liva-dropzone-cover" id="liva-cover-dropzone">
                            <input type="file" id="liva-cover-file" name="cover_image" accept="image/png, image/jpeg, image/webp" style="display:none;">
                            <div class="liva-dropzone-content">
                                <svg class="liva-dropzone-icon" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#5f6b7c" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="3" ry="3"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span>Drag file here or browse</span>
                            </div>
                            <canvas class="liva-editor-canvas" id="liva-cover-canvas" width="1200" height="340" style="display:none;"></canvas>
                            <span class="liva-canvas-hint">Drag to adjust position</span>
                            <button type="button" class="liva-canvas-replace" title="Change image">✕</button>
                        </div>
                        <div class="liva-canvas-controls" id="liva-cover-controls" style="display:none;">
                            <span>Zoom</span>
                            <input type="range" class="liva-zoom-range" min="1" max="2.5" step="0.05" value="1">
                        </div>
                    </div>
                </div>

                <?php wp_nonce_field('liva_add_course', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="add_course">

                <div class="liva-modal-actions">
                    <button type="button" class="liva-btn liva-btn-cancel-modal" data-liva-modal-close>Cancel</button>
                    <button type="submit" class="liva-btn liva-btn-primary" id="liva-course-submit-btn">Create course</button>
                </div>
            </form>
        </div>

        <script>
        (function() {
            function initModal() {
                var modal = document.getElementById('liva-add-course');
                var form = document.getElementById('liva-add-course-form');
                if (!modal || !form) return;

                document.querySelectorAll('[data-liva-modal-open="liva-add-course"]').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        modal.removeAttribute('hidden');
                        modal.style.display = 'grid';
                        document.body.classList.add('liva-modal-open');
                    });
                });

                modal.querySelectorAll('[data-liva-modal-close], .liva-btn-cancel-modal').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        modal.setAttribute('hidden', '');
                        modal.style.display = 'none';
                        document.body.classList.remove('liva-modal-open');
                    });
                });

                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.setAttribute('hidden', '');
                        modal.style.display = 'none';
                        document.body.classList.remove('liva-modal-open');
                    }
                });

                function setupCanvasEditor(dropzoneId, inputId, canvasId, controlsId) {
                    var dropzone = document.getElementById(dropzoneId);
                    var input = document.getElementById(inputId);
                    var canvas = document.getElementById(canvasId);
                    var controls = document.getElementById(controlsId);
                    if (!dropzone || !input || !canvas) return;

                    var ctx = canvas.getContext('2d');
                    var content = dropzone.querySelector('.liva-dropzone-content');
                    var hint = dropzone.querySelector('.liva-canvas-hint');
                    var replaceBtn = dropzone.querySelector('.liva-canvas-replace');
                    var zoomRange = controls ? controls.querySelector('.liva-zoom-range') : null;

                    var img = new Image();
                    var isLoaded = false;
                    var isDragging = false;
                    var startX = 0, startY = 0;
                    var panX = 0, panY = 0;
                    var baseScale = 1;
                    var userZoom = 1;

                    dropzone.addEventListener('click', function(e) {
                        if (!isLoaded && e.target !== input) {
                            input.click();
                        }
                    });

                    dropzone.addEventListener('dragover', function(e) { e.preventDefault(); });
                    dropzone.addEventListener('drop', function(e) {
                        e.preventDefault();
                        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                            input.files = e.dataTransfer.files;
                            loadFile(input.files[0]);
                        }
                    });

                    input.addEventListener('change', function() {
                        if (this.files && this.files[0]) loadFile(this.files[0]);
                    });

                    function loadFile(file) {
                        if (!file.type.startsWith('image/')) return;
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            img = new Image();
                            img.onload = function() {
                                isLoaded = true;
                                userZoom = 1;
                                if (zoomRange) zoomRange.value = 1;

                                var scaleX = canvas.width / img.width;
                                var scaleY = canvas.height / img.height;
                                baseScale = Math.max(scaleX, scaleY);

                                panX = (canvas.width - img.width * baseScale) / 2;
                                panY = (canvas.height - img.height * baseScale) / 2;

                                canvas.style.display = 'block';
                                if (content) content.style.display = 'none';
                                if (controls) controls.style.display = 'flex';
                                dropzone.classList.add('has-image');

                                draw();
                            };
                            img.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }

                    function draw() {
                        if (!isLoaded) return;
                        var currentScale = baseScale * userZoom;
                        var drawW = img.width * currentScale;
                        var drawH = img.height * currentScale;

                        var minX = canvas.width - drawW;
                        var minY = canvas.height - drawH;
                        if (panX > 0) panX = 0;
                        if (panX < minX) panX = minX;
                        if (panY > 0) panY = 0;
                        if (panY < minY) panY = minY;

                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        ctx.drawImage(img, panX, panY, drawW, drawH);
                    }

                    function onPointerDown(clientX, clientY) {
                        if (!isLoaded) return;
                        isDragging = true;
                        startX = clientX - panX;
                        startY = clientY - panY;
                        dropzone.classList.add('is-dragging');
                    }

                    function onPointerMove(clientX, clientY) {
                        if (!isDragging || !isLoaded) return;
                        panX = clientX - startX;
                        panY = clientY - startY;
                        draw();
                    }

                    function onPointerUp() {
                        isDragging = false;
                        dropzone.classList.remove('is-dragging');
                    }

                    canvas.addEventListener('mousedown', function(e) { onPointerDown(e.clientX, e.clientY); });
                    window.addEventListener('mousemove', function(e) { onPointerMove(e.clientX, e.clientY); });
                    window.addEventListener('mouseup', onPointerUp);

                    canvas.addEventListener('touchstart', function(e) {
                        if (e.touches[0]) onPointerDown(e.touches[0].clientX, e.touches[0].clientY);
                    }, { passive: true });
                    window.addEventListener('touchmove', function(e) {
                        if (e.touches[0]) onPointerMove(e.touches[0].clientX, e.touches[0].clientY);
                    }, { passive: true });
                    window.addEventListener('touchend', onPointerUp);

                    if (zoomRange) {
                        zoomRange.addEventListener('input', function() {
                            var oldScale = baseScale * userZoom;
                            var centerX = canvas.width / 2;
                            var centerY = canvas.height / 2;

                            userZoom = parseFloat(this.value);
                            var newScale = baseScale * userZoom;

                            panX = centerX - (centerX - panX) * (newScale / oldScale);
                            panY = centerY - (centerY - panY) * (newScale / oldScale);
                            draw();
                        });
                    }

                    if (replaceBtn) {
                        replaceBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            isLoaded = false;
                            input.value = '';
                            canvas.style.display = 'none';
                            if (content) content.style.display = 'flex';
                            if (controls) controls.style.display = 'none';
                            dropzone.classList.remove('has-image');
                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                        });
                    }

                    return {
                        hasImage: function() { return isLoaded; },
                        getBlob: function(callback) {
                            canvas.toBlob(callback, 'image/jpeg', 0.92);
                        },
                        input: input
                    };
                }

                var avatarEditor = setupCanvasEditor('liva-avatar-dropzone', 'liva-avatar-file', 'liva-avatar-canvas', 'liva-avatar-controls');
                var coverEditor  = setupCanvasEditor('liva-cover-dropzone', 'liva-cover-file', 'liva-cover-canvas', 'liva-cover-controls');

                form.addEventListener('submit', function(e) {
                    if (form.dataset.readyToSubmit) return;
                    e.preventDefault();

                    var submitBtn = document.getElementById('liva-course-submit-btn');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Saving...';
                    }

                    var tasks = [];

                    if (avatarEditor && avatarEditor.hasImage()) {
                        tasks.push(new Promise(function(resolve) {
                            avatarEditor.getBlob(function(blob) {
                                if (blob) {
                                    var file = new File([blob], 'avatar-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                                    var dt = new DataTransfer();
                                    dt.items.add(file);
                                    avatarEditor.input.files = dt.files;
                                }
                                resolve();
                            });
                        }));
                    }

                    if (coverEditor && coverEditor.hasImage()) {
                        tasks.push(new Promise(function(resolve) {
                            coverEditor.getBlob(function(blob) {
                                if (blob) {
                                    var file = new File([blob], 'cover-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                                    var dt = new DataTransfer();
                                    dt.items.add(file);
                                    coverEditor.input.files = dt.files;
                                }
                                resolve();
                            });
                        }));
                    }

                    Promise.all(tasks).then(function() {
                        form.dataset.readyToSubmit = 'true';
                        form.submit();
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initModal);
            } else {
                initModal();
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    private static function delete_course_modal(): string {
        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="liva-delete-course-modal" hidden>
            <div class="liva-modal" style="max-width: 420px; text-align: center; padding: 36px 30px 32px; border-radius: 16px;">
                <div style="width: 54px; height: 54px; background: #fde8e8; color: #c71920; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 20px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </div>
                <h3 style="margin: 0 0 10px; font-size: 22px; font-weight: 800; color: #171b26;">Delete course?</h3>
                <p style="margin: 0 0 28px; color: #687384; font-size: 14.5px; line-height: 1.55;">This course will be permanently deleted. You won’t be able to undo this action.</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" class="liva-btn liva-btn-cancel-modal" data-liva-modal-close style="min-width: 110px; padding: 10px 24px;">Cancel</button>
                    <button type="button" class="liva-btn liva-btn-danger" id="liva-confirm-delete-course-btn" style="min-width: 110px; background: #c71920; border-color: #c71920; color: #ffffff; padding: 10px 24px; border-radius: 8px; font-weight: 600;">Delete</button>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('liva-delete-course-modal');
            var confirmBtn = document.getElementById('liva-confirm-delete-course-btn');
            var targetForm = null;

            document.querySelectorAll('.liva-trigger-delete-course-modal').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    targetForm = document.getElementById(this.getAttribute('data-target'));
                    if (targetForm && modal) {
                        modal.removeAttribute('hidden');
                        modal.style.display = 'grid';
                        document.body.classList.add('liva-modal-open');
                    }
                });
            });

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    if (targetForm) {
                        targetForm.submit();
                    }
                });
            }

            if (modal) {
                modal.querySelectorAll('[data-liva-modal-close]').forEach(function(el) {
                    el.addEventListener('click', function() {
                        modal.setAttribute('hidden', '');
                        modal.style.display = 'none';
                        document.body.classList.remove('liva-modal-open');
                    });
                });

                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.setAttribute('hidden', '');
                        modal.style.display = 'none';
                        document.body.classList.remove('liva-modal-open');
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private static function course_admin_detail(int $id): string {
        $c = get_post($id);
        if (!$c || $c->post_type !== 'liva_course') {
            return self::shell('liva-courses', '<p>Course not found.</p>');
        }

        $tab = sanitize_key($_GET['tab'] ?? 'curriculum');
        $modules = get_posts([
            'post_type'      => 'liva_module',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [['key' => '_liva_course_id', 'value' => $id]],
            'meta_key'       => '_liva_position',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC'
        ]);

        global $wpdb;
        $students_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}liva_enrollments WHERE course_id=%d", $id));

        $level   = get_post_meta($id, '_liva_level', true) ?: 'Basic';
        $status  = $c->post_status;
        $is_pub  = ($status === 'publish');
        $author  = get_the_author_meta('display_name', $c->post_author) ?: 'Linh Pham';
        $updated = get_the_modified_date('M j, Y', $c);

        // A draft course only enables Publish once changes or content exist
        $has_changes = !empty($modules) || (bool) get_post_meta($id, '_liva_has_changes', true);
        $publish_disabled = $is_pub || !$has_changes;

        $lvl_lower = strtolower($level);
        if (strpos($lvl_lower, 'inter') !== false || strpos($lvl_lower, 'b1') !== false || strpos($lvl_lower, 'b2') !== false) {
            $lvl_class = 'liva-cbadge-intermediate';
        } elseif (strpos($lvl_lower, 'adv') !== false || strpos($lvl_lower, 'c1') !== false || strpos($lvl_lower, 'c2') !== false) {
            $lvl_class = 'liva-cbadge-advanced';
        } else {
            $lvl_class = 'liva-cbadge-basic';
        }

        ob_start();
        echo self::status();
        ?>
        <a class="liva-btn liva-btn-outline liva-btn-sm liva-back-btn" style="margin-bottom: 26px !important;" href="<?php echo esc_url(remove_query_arg(['view', 'id', 'tab'])); ?>">← Back to Courses</a>

        <!-- Header matching Picture 1 & 2 -->
        <div class="liva-admin-course-head">
            <div class="liva-admin-course-meta-col">
                <h1><?php echo self::h($c->post_title); ?></h1>
                <div class="liva-admin-course-sub">
                    By <?php echo self::h($author); ?> - Last updated <?php echo esc_html($updated); ?>
                </div>
                <div class="liva-admin-course-badges">
                    <span class="liva-course-badge <?php echo esc_attr($lvl_class); ?>"><?php echo self::h($level); ?></span>
                    <span class="liva-course-badge <?php echo $is_pub ? 'liva-cbadge-published' : 'liva-cbadge-draft'; ?>">
                        <?php echo $is_pub ? 'Published' : 'Draft'; ?>
                    </span>
                </div>
            </div>

            <div class="liva-admin-course-actions">
                <!-- Save as draft (Always active) -->
                <form method="post" class="liva-inline-form">
                    <?php wp_nonce_field('liva_save_draft_course', '_liva_nonce'); ?>
                    <input type="hidden" name="liva_action" value="save_draft_course">
                    <input type="hidden" name="course_id" value="<?php echo (int) $id; ?>">
                    <input type="hidden" name="current_tab" value="<?php echo esc_attr($tab); ?>">
                    <button type="submit" class="liva-btn liva-btn-draft">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        <span>Save as draft</span>
                    </button>
                </form>

                <!-- Publish course (Inactive on new draft or if already published) -->
                <form method="post" class="liva-inline-form">
                    <?php wp_nonce_field('liva_publish_course', '_liva_nonce'); ?>
                    <input type="hidden" name="liva_action" value="publish_course">
                    <input type="hidden" name="course_id" value="<?php echo (int) $id; ?>">
                    <input type="hidden" name="current_tab" value="<?php echo esc_attr($tab); ?>">
                    <button type="submit" class="liva-btn liva-btn-publish" id="liva-publish-btn" <?php echo $publish_disabled ? 'disabled' : ''; ?>>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        <span>Publish course</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- 4 Metric Cards -->
        <div class="liva-course-stats-grid">
            <div class="liva-course-stat-card">
                <span class="liva-course-stat-label">STUDENTS</span>
                <span class="liva-course-stat-val-right"><?php echo $students_count; ?></span>
            </div>
            <div class="liva-course-stat-card">
                <span class="liva-course-stat-label">COMPLETION RATE</span>
                <span class="liva-course-stat-val-right">0%</span>
            </div>
            <div class="liva-course-stat-card">
                <span class="liva-course-stat-label">AVG RATING</span>
                <span class="liva-course-stat-val-right">0</span>
            </div>
            <div class="liva-course-stat-card">
                <span class="liva-course-stat-label">REVENUE</span>
                <span class="liva-course-stat-val-right">$0</span>
            </div>
        </div>

        <div class="liva-tabs liva-admin-tabs">
            <?php foreach (['curriculum' => 'CURRICULUM', 'students' => 'ENROLLED STUDENTS', 'reviews' => 'REVIEWS', 'settings' => 'SETTINGS'] as $k => $label): ?>
                <a class="<?php echo $tab === $k ? 'active' : ''; ?>" href="<?php echo esc_url(self::query_url('liva-courses', ['view' => 'course', 'id' => $id, 'tab' => $k])); ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="liva-course-tab-content">
            <?php
            if ($tab === 'students') {
                self::course_students_tab($id);
            } elseif ($tab === 'reviews') {
                self::course_reviews_tab();
            } elseif ($tab === 'settings') {
                self::course_settings_tab($c);
            } else {
                self::course_curriculum_tab($c, $modules);
            }
            ?>
        </div>

        <script>
        (function() {
            // If the course is currently a draft, any user change enables the Publish button
            var isPublished = <?php echo $is_pub ? 'true' : 'false'; ?>;
            var publishBtn = document.getElementById('liva-publish-btn');

            if (!isPublished && publishBtn) {
                var enablePublish = function() {
                    publishBtn.removeAttribute('disabled');
                };

                // Listen for changes in settings or modal inputs
                document.addEventListener('input', enablePublish);
                document.addEventListener('change', enablePublish);
            }
        })();
        </script>
        <?php
        return self::shell('liva-courses', ob_get_clean());
    }

    private static function course_curriculum_tab(WP_Post $c, array $modules): void {
        $lesson_modals      = '';
        $reorder_modals     = '';
        $edit_module_modals = '';

        if (!$modules):
        ?>
            <div class="liva-empty">
                <div class="symbol">
                    <?php echo function_exists('liva_icon') && liva_icon('arrangement') ? liva_icon('arrangement') : '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#171b26" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>'; ?>
                </div>
                <h3>No modules yet</h3>
                <p>This course needs at least one module with a lesson before it can be published.</p>
                <button type="button" class="liva-empty-add-module-btn" data-liva-modal-open="liva-add-module">
                    <?php echo function_exists('liva_icon') && liva_icon('plus') ? liva_icon('plus') : '<span class="liva-plus-char">+</span>'; ?>
                    <span>Add module</span>
                </button>
            </div>
        <?php
        else:
            echo '<div class="liva-curriculum-accordion">';
            foreach ($modules as $m_idx => $m):
                $raw_lessons = get_posts([
                    'post_type'      => 'liva_lesson',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'meta_key'       => '_liva_module_id',
                    'meta_value'     => $m->ID
                ]);

                // Filter out quiz items
                $less = array_values(array_filter($raw_lessons, function($l) {
                    $type  = get_post_meta($l->ID, '_liva_type', true);
                    $title = strtolower($l->post_title);
                    return $type !== 'quiz' && strpos($title, 'quiz') === false;
                }));

                // Sort by assigned position, fallback to ID
                usort($less, function($a, $b) {
                    $posA = get_post_meta($a->ID, '_liva_position', true);
                    $posB = get_post_meta($b->ID, '_liva_position', true);
                    $posA = ($posA !== '') ? (int) $posA : $a->ID;
                    $posB = ($posB !== '') ? (int) $posB : $b->ID;
                    return $posA <=> $posB;
                });

                $is_first = ($m_idx === 0);
                $duration_text = $is_first ? '15min' : '1hr 12min';
                $lesson_count  = count($less);
        ?>
                <div class="liva-curriculum-module-card <?php echo $is_first ? 'open' : ''; ?>">
                    <div class="liva-curriculum-module-head">
                        <span class="liva-curriculum-module-title">
                            Module <?php echo $m_idx + 1; ?><?php echo $is_first ? ' - ' : ': '; ?><?php echo self::h($m->post_title); ?>
                        </span>
                        <div class="liva-curriculum-module-meta">
                            <span>0 / <?php echo $lesson_count; ?> | <?php echo $duration_text; ?></span>

                            <!-- Module Options Dropdown -->
                            <div class="liva-lesson-menu-wrap liva-module-menu-wrap">
                                <button type="button" class="liva-dots-trigger" aria-label="Module options">⋮</button>
                                <div class="liva-dots-dropdown">
                                    <!-- Edit Module Trigger -->
                                    <button type="button" class="liva-dots-item" data-liva-modal-open="liva-edit-module-<?php echo (int) $m->ID; ?>">
                                        <?php echo function_exists('liva_icon') && liva_icon('edit') ? liva_icon('edit') : '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#29779b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>'; ?>
                                        <span>Edit module</span>
                                    </button>

                                    <!-- Delete Module Form & Button -->
                                    <form method="post" class="liva-inline-form" id="liva-delete-module-form-<?php echo (int) $m->ID; ?>">
                                        <?php wp_nonce_field('liva_delete_module', '_liva_nonce'); ?>
                                        <input type="hidden" name="liva_action" value="delete_module">
                                        <input type="hidden" name="module_id" value="<?php echo (int) $m->ID; ?>">
                                        <input type="hidden" name="course_id" value="<?php echo (int) $c->ID; ?>">
                                        <button type="button" 
                                                class="liva-dots-item liva-dots-item-danger liva-trigger-delete-module-modal" 
                                                data-target="liva-delete-module-form-<?php echo (int) $m->ID; ?>"
                                                data-title="<?php echo esc_attr($m->post_title); ?>"
                                                data-count="<?php echo (int) $lesson_count; ?>">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#c71920" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                            <span>Delete module</span>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <svg class="liva-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </div>

                    <div class="liva-curriculum-module-body">
                        <?php if (!empty($less)): ?>
                            <div class="liva-curriculum-lesson-list">
                                <?php foreach ($less as $l_idx => $l): 
                                    $type         = get_post_meta($l->ID, '_liva_type', true) ?: 'video';
                                    $duration     = get_post_meta($l->ID, '_liva_duration', true) ?: '5min';
                                    $hidden_state = (int) get_post_meta($l->ID, '_liva_hidden', true);
                                ?>
                                    <div class="liva-curriculum-lesson-row">
                                        <div class="liva-lesson-row-left">
                                            <span class="liva-lesson-type-ico">
                                                <?php if ($type === 'pdf' || preg_match('/\.pdf$/i', $l->post_title)): ?>
                                                    <?php echo function_exists('liva_icon') ? liva_icon('order_history') : ''; ?>
                                                <?php else: ?>
                                                    <?php echo function_exists('liva_icon') ? liva_icon('play') : ''; ?>
                                                <?php endif; ?>
                                            </span>

                                            <span class="liva-lesson-title-text"><?php echo self::h($l->post_title); ?></span>
                                            <?php if ($hidden_state): ?>
                                                <span class="liva-pill liva-pill-muted" style="font-size:10.5px; padding: 2px 8px; margin-left: 6px;">Hidden</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="liva-lesson-row-right">
                                            <?php if ($duration): ?>
                                                <div class="liva-lesson-duration-pill">
                                                    <?php if ($type === 'pdf' || preg_match('/\.pdf$/i', $l->post_title)): ?>
                                                        <?php echo function_exists('liva_icon') && liva_icon('order_history') ? liva_icon('order_history') : ''; ?>
                                                    <?php else: ?>
                                                        <?php echo function_exists('liva_icon') && liva_icon('video_2') ? liva_icon('video_2') : ''; ?>
                                                    <?php endif; ?>
                                                    <span><?php echo esc_html($duration); ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <!-- 3 Dots Dropdown Menu -->
                                            <div class="liva-lesson-menu-wrap">
                                                <button type="button" class="liva-dots-trigger" aria-label="Lesson options">⋮</button>
                                                <div class="liva-dots-dropdown">
                                                    <!-- 1. Reorder -->
                                                    <button type="button" class="liva-dots-item" data-liva-modal-open="liva-reorder-module-<?php echo $m->ID; ?>">
                                                        <?php echo function_exists('liva_icon') && liva_icon('arrangement') ? liva_icon('arrangement') : '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#29779b" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>'; ?>
                                                        <span>Reorder</span>
                                                    </button>

                                                    <!-- 2. Edit Lesson -->
                                                    <button type="button" class="liva-dots-item" data-liva-modal-open="liva-edit-lesson-<?php echo $l->ID; ?>">
                                                        <?php echo function_exists('liva_icon') && liva_icon('edit') ? liva_icon('edit') : '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#29779b" stroke-width="2.2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>'; ?>
                                                        <span>Edit lesson</span>
                                                    </button>

                                                    <!-- 3. Hide / Show to students -->
                                                    <form method="post" class="liva-inline-form">
                                                        <?php wp_nonce_field('liva_toggle_lesson_visibility', '_liva_nonce'); ?>
                                                        <input type="hidden" name="liva_action" value="toggle_lesson_visibility">
                                                        <input type="hidden" name="lesson_id" value="<?php echo (int) $l->ID; ?>">
                                                        <input type="hidden" name="course_id" value="<?php echo (int) $c->ID; ?>">
                                                        <button type="submit" class="liva-dots-item">
                                                            <?php if ($hidden_state): ?>
                                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#29779b" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                                <span>Show to students</span>
                                                            <?php else: ?>
                                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#29779b" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                                                <span>Hide from students</span>
                                                            <?php endif; ?>
                                                        </button>
                                                    </form>

                                                    <!-- 4. Delete Lesson with Popup Confirmation -->
                                                    <form method="post" class="liva-inline-form" id="liva-delete-lesson-form-<?php echo (int) $l->ID; ?>">
                                                        <?php wp_nonce_field('liva_delete_lesson', '_liva_nonce'); ?>
                                                        <input type="hidden" name="liva_action" value="delete_lesson">
                                                        <input type="hidden" name="lesson_id" value="<?php echo (int) $l->ID; ?>">
                                                        <input type="hidden" name="course_id" value="<?php echo (int) $c->ID; ?>">
                                                        <button type="button" class="liva-dots-item liva-dots-item-danger liva-trigger-delete-lesson-modal" data-target="liva-delete-lesson-form-<?php echo (int) $l->ID; ?>">
                                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#c71920" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                                            <span>Delete lesson</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    $lesson_modals .= self::edit_lesson_modal($c->ID, $m, $l, $m_idx + 1);
                                endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="liva-module-add-lesson-row">
                            <button type="button" class="liva-full-add-lesson-btn" data-liva-modal-open="liva-add-lesson-<?php echo $m->ID; ?>">
                                <?php echo function_exists('liva_icon') ? liva_icon('plus') : '+'; ?>
                                <span>Add lesson</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php 
                $lesson_modals  .= self::add_lesson_modal($c->ID, $m, $m_idx + 1);
                $reorder_modals .= self::reorder_lessons_modal($c->ID, $m, $less);
                $edit_module_modals .= self::edit_module_modal($c->ID, $m, $modules, $m_idx + 1);
            endforeach;
            echo '</div>';
            ?>

            <div class="liva-curriculum-add-module-wrap">
                <button type="button" class="liva-full-add-module-btn" data-liva-modal-open="liva-add-module">
                    <?php echo function_exists('liva_icon') ? liva_icon('plus') : '+'; ?>
                    <span>Add Module</span>
                </button>
            </div>
        <?php
        endif;

        echo $lesson_modals;
        echo $reorder_modals;
        echo $edit_module_modals;
        echo self::add_module_modal($c->ID, $modules);
        echo self::delete_lesson_modal();
        echo self::delete_module_modal();
        ?>
        <script>
        (function() {
            document.addEventListener('click', function(e) {
                var openBtn = e.target.closest('[data-liva-modal-open]');
                if (openBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    document.querySelectorAll('.liva-lesson-menu-wrap.open').forEach(function(el) {
                        el.classList.remove('open');
                    });
                    var targetId = openBtn.getAttribute('data-liva-modal-open');
                    var targetModal = document.getElementById(targetId);
                    if (targetModal) {
                        targetModal.removeAttribute('hidden');
                        targetModal.style.setProperty('display', 'grid', 'important');
                        document.body.classList.add('liva-modal-open');
                    }
                    return;
                }

                var closeBtn = e.target.closest('[data-liva-modal-close], .liva-btn-cancel-modal');
                if (closeBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    var modal = closeBtn.closest('.liva-modal-backdrop');
                    if (modal) {
                        modal.setAttribute('hidden', '');
                        modal.style.setProperty('display', 'none', 'important');
                        document.body.classList.remove('liva-modal-open');
                    }
                    return;
                }

                if (e.target.classList.contains('liva-modal-backdrop')) {
                    e.target.setAttribute('hidden', '');
                    e.target.style.setProperty('display', 'none', 'important');
                    document.body.classList.remove('liva-modal-open');
                    return;
                }

                var trigger = e.target.closest('.liva-dots-trigger');
                if (trigger) {
                    e.preventDefault();
                    e.stopPropagation();
                    var wrap = trigger.closest('.liva-lesson-menu-wrap');
                    var isOpen = wrap.classList.contains('open');
                    document.querySelectorAll('.liva-lesson-menu-wrap.open').forEach(function(el) {
                        el.classList.remove('open');
                    });
                    if (!isOpen) {
                        wrap.classList.add('open');
                    }
                    return;
                }

                if (!e.target.closest('.liva-dots-dropdown')) {
                    document.querySelectorAll('.liva-lesson-menu-wrap.open').forEach(function(el) {
                        el.classList.remove('open');
                    });
                }

                var head = e.target.closest('.liva-curriculum-module-head');
                if (head && !e.target.closest('.liva-dots-trigger') && !e.target.closest('.liva-dots-dropdown')) {
                    var card = head.closest('.liva-curriculum-module-card');
                    if (card) {
                        card.classList.toggle('open');
                    }
                }
            });
        })();
        </script>
        <?php
    }
    /**
     * Modal for reordering lessons inside a module.
     */
    private static function reorder_lessons_modal(int $course_id, WP_Post $m, array $lessons): string {
        $modal_id = 'liva-reorder-module-' . $m->ID;
        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="<?php echo esc_attr($modal_id); ?>" hidden>
            <form class="liva-modal liva-modal-standard" method="post">
                <div class="liva-modal-head" style="margin-bottom: 16px;">
                    <div>
                        <h2>Reorder lessons</h2>
                        <p class="liva-modal-subtitle"><?php echo self::h($m->post_title); ?></p>
                    </div>
                    <button type="button" class="liva-modal-close" data-liva-modal-close aria-label="Close">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <p style="font-size: 13.5px; color: #556270; margin-bottom: 16px;">Click the Up and Down arrows to adjust lesson sequence:</p>

                <div class="liva-reorder-list" id="liva-reorder-list-<?php echo $m->ID; ?>">
                    <?php if (empty($lessons)): ?>
                        <p style="color: #687384; font-size: 13px;">No lessons to reorder in this module.</p>
                    <?php else: ?>
                        <?php foreach ($lessons as $idx => $l): ?>
                            <div class="liva-reorder-row">
                                <span class="liva-reorder-num"><?php echo $idx + 1; ?>.</span>
                                <span class="liva-reorder-title"><?php echo self::h($l->post_title); ?></span>
                                <div class="liva-reorder-actions">
                                    <button type="button" class="liva-reorder-btn liva-reorder-up" title="Move up">▲</button>
                                    <button type="button" class="liva-reorder-btn liva-reorder-down" title="Move down">▼</button>
                                </div>
                                <input type="hidden" name="lesson_order[]" value="<?php echo (int) $l->ID; ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php wp_nonce_field('liva_reorder_lessons', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="reorder_lessons">
                <input type="hidden" name="course_id" value="<?php echo (int) $course_id; ?>">
                <input type="hidden" name="module_id" value="<?php echo (int) $m->ID; ?>">

                <div class="liva-modal-actions">
                    <button type="button" class="liva-btn liva-btn-cancel-modal" data-liva-modal-close>Cancel</button>
                    <button type="submit" class="liva-btn liva-btn-primary" <?php echo empty($lessons) ? 'disabled' : ''; ?>>Save order</button>
                </div>
            </form>
        </div>
        <script>
        (function() {
            var container = document.getElementById('liva-reorder-list-<?php echo $m->ID; ?>');
            if (!container) return;

            function refreshNumbers() {
                var rows = container.querySelectorAll('.liva-reorder-row');
                rows.forEach(function(row, idx) {
                    var numEl = row.querySelector('.liva-reorder-num');
                    if (numEl) numEl.textContent = (idx + 1) + '.';
                });
            }

            container.addEventListener('click', function(e) {
                var upBtn = e.target.closest('.liva-reorder-up');
                var downBtn = e.target.closest('.liva-reorder-down');

                if (upBtn) {
                    e.preventDefault();
                    var row = upBtn.closest('.liva-reorder-row');
                    var prev = row.previousElementSibling;
                    if (prev) {
                        container.insertBefore(row, prev);
                        refreshNumbers();
                    }
                } else if (downBtn) {
                    e.preventDefault();
                    var row = downBtn.closest('.liva-reorder-row');
                    var next = row.nextElementSibling;
                    if (next) {
                        container.insertBefore(next, row);
                        refreshNumbers();
                    }
                }
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Modal for confirming lesson deletion (matches Picture 1 design).
     */
    private static function delete_lesson_modal(): string {
        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="liva-delete-lesson-modal" hidden>
            <div class="liva-modal" style="max-width: 420px; text-align: center; padding: 36px 30px 32px; border-radius: 16px;">
                <div style="width: 54px; height: 54px; background: #fde8e8; color: #c71920; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 20px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </div>
                <h3 style="margin: 0 0 10px; font-size: 22px; font-weight: 800; color: #171b26;">Delete lesson?</h3>
                <p style="margin: 0 0 28px; color: #687384; font-size: 14.5px; line-height: 1.55;">This lesson will be permanently deleted. You won’t be able to undo this action.</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" class="liva-btn liva-btn-cancel-modal" data-liva-modal-close style="min-width: 110px; padding: 10px 24px;">Cancel</button>
                    <button type="button" class="liva-btn liva-btn-danger" id="liva-confirm-delete-lesson-btn" style="min-width: 110px; background: #c71920; border-color: #c71920; color: #ffffff; padding: 10px 24px; border-radius: 8px; font-weight: 600;">Delete</button>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('liva-delete-lesson-modal');
            var confirmBtn = document.getElementById('liva-confirm-delete-lesson-btn');
            var targetForm = null;

            document.querySelectorAll('.liva-trigger-delete-lesson-modal').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    targetForm = document.getElementById(this.getAttribute('data-target'));
                    document.querySelectorAll('.liva-lesson-menu-wrap.open').forEach(function(el) {
                        el.classList.remove('open');
                    });
                    if (targetForm && modal) {
                        modal.removeAttribute('hidden');
                        modal.style.setProperty('display', 'grid', 'important');
                        document.body.classList.add('liva-modal-open');
                    }
                });
            });

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    if (targetForm) {
                        targetForm.submit();
                    }
                });
            }

            if (modal) {
                modal.querySelectorAll('[data-liva-modal-close]').forEach(function(el) {
                    el.addEventListener('click', function() {
                        modal.setAttribute('hidden', '');
                        modal.style.setProperty('display', 'none', 'important');
                        document.body.classList.remove('liva-modal-open');
                    });
                });
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.setAttribute('hidden', '');
                        modal.style.setProperty('display', 'none', 'important');
                        document.body.classList.remove('liva-modal-open');
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }    

    /**
     * Modal for confirming module deletion.
     */
    private static function delete_module_modal(): string {
        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="liva-delete-module-modal" hidden>
            <div class="liva-modal" style="max-width: 420px; text-align: center; padding: 36px 30px 32px; border-radius: 16px;">
                <div style="width: 54px; height: 54px; background: #fde8e8; color: #c71920; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 20px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </div>
                <h3 style="margin: 0 0 10px; font-size: 22px; font-weight: 800; color: #171b26;">Delete module?</h3>
                <p id="liva-delete-module-desc" style="margin: 0 0 28px; color: #687384; font-size: 14.5px; line-height: 1.55;">
                    This module and all of its lessons will be permanently deleted. You won’t be able to undo this action.
                </p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" class="liva-btn liva-btn-cancel-modal" data-liva-modal-close style="min-width: 110px; padding: 10px 24px;">Cancel</button>
                    <button type="button" class="liva-btn liva-btn-danger" id="liva-confirm-delete-module-btn" style="min-width: 110px; background: #c71920; border-color: #c71920; color: #ffffff; padding: 10px 24px; border-radius: 8px; font-weight: 600;">Delete</button>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('liva-delete-module-modal');
            var confirmBtn = document.getElementById('liva-confirm-delete-module-btn');
            var descEl = document.getElementById('liva-delete-module-desc');
            var targetForm = null;

            document.querySelectorAll('.liva-trigger-delete-module-modal').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    targetForm = document.getElementById(this.getAttribute('data-target'));
                    var title = this.getAttribute('data-title');
                    var count = this.getAttribute('data-count');

                    if (descEl) {
                        descEl.innerHTML = '<strong>' + (title || 'This module') + '</strong> and all of its <strong>' + (count || '0') + ' lesson(s)</strong> will be permanently deleted. You won’t be able to undo this action.';
                    }

                    document.querySelectorAll('.liva-lesson-menu-wrap.open').forEach(function(el) {
                        el.classList.remove('open');
                    });

                    if (targetForm && modal) {
                        modal.removeAttribute('hidden');
                        modal.style.setProperty('display', 'grid', 'important');
                        document.body.classList.add('liva-modal-open');
                    }
                });
            });

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    if (targetForm) {
                        targetForm.submit();
                    }
                });
            }

            if (modal) {
                modal.querySelectorAll('[data-liva-modal-close]').forEach(function(el) {
                    el.addEventListener('click', function() {
                        modal.setAttribute('hidden', '');
                        modal.style.setProperty('display', 'none', 'important');
                        document.body.classList.remove('liva-modal-open');
                    });
                });
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.setAttribute('hidden', '');
                        modal.style.setProperty('display', 'none', 'important');
                        document.body.classList.remove('liva-modal-open');
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    private static function add_module_modal(int $course, array $existing_modules = []): string {
        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="liva-add-module" hidden>
            <form class="liva-modal liva-modal-standard" method="post">
                <div class="liva-modal-head">
                    <h2>Add module</h2>
                    <button type="button" class="liva-modal-close" data-liva-modal-close aria-label="Close">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div class="liva-form-group">
                    <label>Module title</label>
                    <input class="liva-input" name="title" placeholder="e.g. Family and members" required>
                </div>

                <div class="liva-form-group">
                    <label>Description (optional)</label>
                    <textarea class="liva-textarea" name="description" rows="3" placeholder="What students will learn in this module"></textarea>
                </div>

                <div class="liva-form-group">
                    <label>Position</label>
                    <div class="liva-select-wrapper">
                        <select class="liva-select liva-position-select" name="position">
                            <?php if (empty($existing_modules)): ?>
                                <option value="1">Position 1</option>
                            <?php else: ?>
                                <?php foreach ($existing_modules as $idx => $mod): ?>
                                    <option value="<?php echo $idx + 1; ?>" <?php selected($idx + 1, count($existing_modules)); ?>>
                                        After Module <?php echo $idx + 1; ?> - <?php echo esc_html($mod->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <?php wp_nonce_field('liva_add_module', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="add_module">
                <input type="hidden" name="course_id" value="<?php echo (int) $course; ?>">

                <div class="liva-modal-actions">
                    <button type="button" class="liva-btn liva-btn-cancel-modal" data-liva-modal-close>Cancel</button>
                    <button type="submit" class="liva-btn liva-btn-primary">Add module</button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    /**
     * Modal for editing an existing module.
     */
    private static function edit_module_modal(int $course_id, WP_Post $m, array $all_modules, int $current_module_num = 1): string {
        $modal_id = 'liva-edit-module-' . $m->ID;
        $current_pos = (int) (get_post_meta($m->ID, '_liva_position', true) ?: $current_module_num);

        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="<?php echo esc_attr($modal_id); ?>" hidden>
            <form class="liva-modal liva-modal-standard" method="post">
                <div class="liva-modal-head">
                    <h2>Edit module</h2>
                    <button type="button" class="liva-modal-close" data-liva-modal-close aria-label="Close">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <!-- Module Title -->
                <div class="liva-form-group">
                    <label>Module title</label>
                    <input class="liva-input" name="title" value="<?php echo esc_attr($m->post_title); ?>" placeholder="e.g. Introduction" required>
                </div>

                <!-- Description (optional) -->
                <div class="liva-form-group">
                    <label>Description (optional)</label>
                    <textarea class="liva-textarea" name="description" rows="3" placeholder="What students will learn in this module"><?php echo esc_textarea($m->post_content); ?></textarea>
                </div>

                <!-- Position Dropdown matching Edit_Module screenshot -->
                <div class="liva-form-group">
                    <label>Position</label>
                    <div class="liva-select-wrapper">
                        <select class="liva-select liva-position-select" name="position">
                            <?php foreach ($all_modules as $idx => $mod): 
                                $pos_val = $idx + 1;
                            ?>
                                <option value="<?php echo $pos_val; ?>" <?php selected($current_pos, $pos_val); ?>>
                                    After Module <?php echo $pos_val; ?> - <?php echo esc_html($mod->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php wp_nonce_field('liva_update_module', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="update_module">
                <input type="hidden" name="course_id" value="<?php echo (int) $course_id; ?>">
                <input type="hidden" name="module_id" value="<?php echo (int) $m->ID; ?>">

                <div class="liva-modal-actions">
                    <button type="button" class="liva-btn liva-btn-cancel-modal" data-liva-modal-close>Cancel</button>
                    <button type="submit" class="liva-btn liva-btn-primary">Edit module</button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function add_lesson_modal(int $course, WP_Post $m, int $module_num = 1): string {
        $modal_id = 'liva-add-lesson-' . $m->ID;
        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="<?php echo esc_attr($modal_id); ?>" hidden>
            <form class="liva-modal liva-modal-standard liva-lesson-modal-form" method="post" enctype="multipart/form-data">
                <div class="liva-modal-head">
                    <div>
                        <h2>Add lesson</h2>
                        <p class="liva-modal-subtitle">Module <?php echo (int) $module_num; ?> - <?php echo self::h($m->post_title); ?></p>
                    </div>
                    <button type="button" class="liva-modal-close" data-liva-modal-close aria-label="Close">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <input type="hidden" name="lesson_type" class="liva-lesson-type-input" value="video">

                <!-- 2 Cards: VIDEO & PDF -->
                <div class="liva-type-picker-grid">
                    <button type="button" class="liva-type-card active" data-type="video">
                        <div class="liva-type-icon-box">
                            <?php echo function_exists('liva_icon') && liva_icon('play') ? liva_icon('play') : '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="6 4 19 12 6 20 6 4"></polygon></svg>'; ?>
                        </div>
                        <span class="liva-type-label">VIDEO</span>
                    </button>

                    <button type="button" class="liva-type-card" data-type="pdf">
                        <div class="liva-type-icon-box">
                            <?php echo function_exists('liva_icon') && liva_icon('pdf') ? liva_icon('pdf') : '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>'; ?>
                        </div>
                        <span class="liva-type-label">PDF</span>
                    </button>
                </div>

                <div class="liva-form-group">
                    <label class="liva-title-label">Lesson title</label>
                    <input class="liva-input" name="title" placeholder="e.g. Basic greetings" required>
                </div>

                <!-- YouTube Video URL / ID Section -->
                <div class="liva-form-group liva-video-input-section">
                    <label>YouTube video URL or Video ID</label>
                    <input class="liva-input" name="video_url" placeholder="e.g. https://www.youtube.com/watch?v=dQw4w9WgXcQ or dQw4w9WgXcQ">
                    <p class="liva-help" style="margin-top:6px; color:#687384; font-size:12.5px;">
                        Set your YouTube video to <strong>Unlisted</strong> so only students inside this course can watch it.
                    </p>
                </div>

                <!-- PDF File Dropzone -->
                <div class="liva-form-group liva-dropzone-section liva-dropzone-pdf-section" style="display: none;">
                    <label>Pdf file</label>
                    <label class="liva-lesson-dropzone" for="liva-pdf-file-<?php echo $m->ID; ?>">
                        <input type="file" id="liva-pdf-file-<?php echo $m->ID; ?>" name="pdf_file" accept="application/pdf" style="display:none;">
                        <div class="liva-lesson-dropzone-inner">
                            <div class="liva-monitor-icon">
                                <?php echo function_exists('liva_icon') && liva_icon('import') ? liva_icon('import') : '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#5f6b7c" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>'; ?>
                            </div>
                            <span class="liva-dropzone-title">Drag pdf here or browse</span>
                            <span class="liva-dropzone-sub">PDF, max 50MB</span>
                        </div>
                    </label>
                </div>

                <div class="liva-form-group liva-checkbox-group">
                    <label class="liva-custom-checkbox">
                        <input type="checkbox" name="preview" value="1" checked>
                        <span class="liva-checkmark"></span>
                        <span class="liva-checkbox-label">Preview available before enrollment</span>
                    </label>
                </div>

                <?php wp_nonce_field('liva_add_lesson', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="add_lesson">
                <input type="hidden" name="course_id" value="<?php echo (int) $course; ?>">
                <input type="hidden" name="module_id" value="<?php echo (int) $m->ID; ?>">

                <div class="liva-modal-actions">
                    <button type="button" class="liva-btn liva-btn-cancel-modal" data-liva-modal-close>Cancel</button>
                    <button type="submit" class="liva-btn liva-btn-primary">Add lesson</button>
                </div>
            </form>
        </div>

        <script>
        (function() {
            var modal = document.getElementById('<?php echo esc_js($modal_id); ?>');
            if (!modal) return;

            var cards = modal.querySelectorAll('.liva-type-card');
            var typeInput = modal.querySelector('.liva-lesson-type-input');
            var videoSection = modal.querySelector('.liva-video-input-section');
            var pdfDrop = modal.querySelector('.liva-dropzone-pdf-section');
            var checkboxLabel = modal.querySelector('.liva-checkbox-label');

            cards.forEach(function(card) {
                card.onclick = function(e) {
                    e.preventDefault();
                    cards.forEach(function(c) { c.classList.remove('active'); });
                    this.classList.add('active');

                    var type = this.getAttribute('data-type');
                    typeInput.value = type;

                    if (type === 'video') {
                        videoSection.style.display = 'block';
                        pdfDrop.style.display = 'none';
                        if (checkboxLabel) checkboxLabel.textContent = 'Preview available before enrollment';
                    } else {
                        videoSection.style.display = 'none';
                        pdfDrop.style.display = 'block';
                        if (checkboxLabel) checkboxLabel.textContent = 'Download available before enrollment';
                    }
                };
            });

            var pdfInput = modal.querySelector('#liva-pdf-file-<?php echo $m->ID; ?>');
            var pdfTitle = modal.querySelector('.liva-dropzone-pdf-section .liva-dropzone-title');
            if (pdfInput && pdfTitle) {
                pdfInput.onchange = function() {
                    if (this.files && this.files[0]) {
                        pdfTitle.textContent = this.files[0].name;
                        pdfTitle.style.fontWeight = '700';
                        pdfTitle.style.color = '#171b26';
                    }
                };
            }
        })();

            var pdfInput = modal.querySelector('#liva-pdf-file-<?php echo $m->ID; ?>');
            var pdfTitle = modal.querySelector('.liva-dropzone-pdf-section .liva-dropzone-title');
            if (pdfInput && pdfTitle) {
                pdfInput.onchange = function() {
                    if (this.files && this.files[0]) {
                        pdfTitle.textContent = this.files[0].name;
                        pdfTitle.style.fontWeight = '700';
                        pdfTitle.style.color = '#171b26';
                    }
                };
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    private static function edit_lesson_modal(int $course, WP_Post $m, WP_Post $l, int $module_num = 1): string {
        $modal_id = 'liva-edit-lesson-' . $l->ID;
        $type     = get_post_meta($l->ID, '_liva_type', true) ?: 'video';
        if ($type === 'quiz') {
            $type = 'video';
        }
        $preview   = get_post_meta($l->ID, '_liva_preview', true);
        $video_url = get_post_meta($l->ID, '_liva_video_url', true) ?: get_post_meta($l->ID, '_liva_video_id', true);

        $file_id  = get_post_meta($l->ID, '_liva_file_id', true);
        $file_url = get_post_meta($l->ID, '_liva_file_url', true);
        $filename = '';
        if ($file_id) {
            $filename = basename(get_attached_file($file_id) ?: get_the_title($file_id));
        } elseif ($file_url && $type === 'pdf') {
            $filename = basename(parse_url($file_url, PHP_URL_PATH));
        }

        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="<?php echo esc_attr($modal_id); ?>" hidden>
            <form class="liva-modal liva-modal-standard liva-lesson-modal-form" method="post" enctype="multipart/form-data">
                <div class="liva-modal-head">
                    <div>
                        <h2>Edit lesson</h2>
                        <p class="liva-modal-subtitle">Module <?php echo (int) $module_num; ?> - <?php echo self::h($m->post_title); ?></p>
                    </div>
                    <button type="button" class="liva-modal-close" data-liva-modal-close aria-label="Close">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <input type="hidden" name="lesson_type" class="liva-lesson-type-input" value="<?php echo esc_attr($type); ?>">

                <!-- 2 Cards: VIDEO & PDF -->
                <div class="liva-type-picker-grid">
                    <button type="button" class="liva-type-card <?php echo $type === 'video' ? 'active' : ''; ?>" data-type="video">
                        <div class="liva-type-icon-box">
                            <?php echo function_exists('liva_icon') && liva_icon('play') ? liva_icon('play') : '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="6 4 19 12 6 20 6 4"></polygon></svg>'; ?>
                        </div>
                        <span class="liva-type-label">VIDEO</span>
                    </button>

                    <button type="button" class="liva-type-card <?php echo $type === 'pdf' ? 'active' : ''; ?>" data-type="pdf">
                        <div class="liva-type-icon-box">
                            <?php echo function_exists('liva_icon') && liva_icon('pdf') ? liva_icon('pdf') : '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>'; ?>
                        </div>
                        <span class="liva-type-label">PDF</span>
                    </button>
                </div>

                <div class="liva-form-group">
                    <label class="liva-title-label">Lesson title</label>
                    <input class="liva-input" name="title" value="<?php echo esc_attr($l->post_title); ?>" required>
                </div>

                <!-- YouTube Video URL / ID Section -->
                <div class="liva-form-group liva-video-input-section" style="<?php echo $type === 'video' ? 'display: block;' : 'display: none;'; ?>">
                    <label>YouTube video URL or Video ID</label>
                    <input class="liva-input" name="video_url" value="<?php echo esc_attr($video_url); ?>" placeholder="e.g. https://www.youtube.com/watch?v=dQw4w9WgXcQ or dQw4w9WgXcQ">
                    <p class="liva-help" style="margin-top:6px; color:#687384; font-size:12.5px;">
                        Set your YouTube video to <strong>Unlisted</strong> so only students inside this course can watch it.
                    </p>
                </div>

                <!-- PDF File Dropzone -->
                <div class="liva-form-group liva-dropzone-section liva-dropzone-pdf-section" style="<?php echo $type === 'pdf' ? 'display: block;' : 'display: none;'; ?>">
                    <label>Pdf file</label>
                    <label class="liva-lesson-dropzone" for="liva-edit-pdf-file-<?php echo $l->ID; ?>">
                        <input type="file" id="liva-edit-pdf-file-<?php echo $l->ID; ?>" name="pdf_file" accept="application/pdf" style="display:none;">
                        <div class="liva-lesson-dropzone-inner">
                            <div class="liva-monitor-icon">
                                <?php echo function_exists('liva_icon') && liva_icon('import') ? liva_icon('import') : '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#5f6b7c" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>'; ?>
                            </div>
                            <span class="liva-dropzone-title" style="<?php echo $filename ? 'font-weight:700; color:#171b26;' : ''; ?>">
                                <?php echo esc_html($filename ?: 'Drag pdf here or browse'); ?>
                            </span>
                            <span class="liva-dropzone-sub">
                                <?php echo $filename ? 'Click to replace PDF (PDF, max 50MB)' : 'PDF, max 50MB'; ?>
                            </span>
                        </div>
                    </label>
                </div>

                <div class="liva-form-group liva-checkbox-group">
                    <label class="liva-custom-checkbox">
                        <input type="checkbox" name="preview" value="1" <?php checked($preview, 1); ?>>
                        <span class="liva-checkmark"></span>
                        <span class="liva-checkbox-label"><?php echo $type === 'pdf' ? 'Download available before enrollment' : 'Preview available before enrollment'; ?></span>
                    </label>
                </div>

                <?php wp_nonce_field('liva_update_lesson', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="update_lesson">
                <input type="hidden" name="lesson_id" value="<?php echo (int) $l->ID; ?>">
                <input type="hidden" name="course_id" value="<?php echo (int) $course; ?>">
                <input type="hidden" name="module_id" value="<?php echo (int) $m->ID; ?>">

                <div class="liva-modal-actions">
                    <button type="button" class="liva-btn liva-btn-cancel-modal" data-liva-modal-close>Cancel</button>
                    <button type="submit" class="liva-btn liva-btn-primary">Edit lesson</button>
                </div>
            </form>
        </div>

        <script>
        (function() {
            var modal = document.getElementById('<?php echo esc_js($modal_id); ?>');
            if (!modal) return;

            var cards = modal.querySelectorAll('.liva-type-card');
            var typeInput = modal.querySelector('.liva-lesson-type-input');
            var videoSection = modal.querySelector('.liva-video-input-section');
            var pdfDrop = modal.querySelector('.liva-dropzone-pdf-section');
            var checkboxLabel = modal.querySelector('.liva-checkbox-label');

            cards.forEach(function(card) {
                card.onclick = function(e) {
                    e.preventDefault();
                    cards.forEach(function(c) { c.classList.remove('active'); });
                    this.classList.add('active');

                    var type = this.getAttribute('data-type');
                    typeInput.value = type;

                    if (type === 'video') {
                        videoSection.style.display = 'block';
                        pdfDrop.style.display = 'none';
                        if (checkboxLabel) checkboxLabel.textContent = 'Preview available before enrollment';
                    } else {
                        videoSection.style.display = 'none';
                        pdfDrop.style.display = 'block';
                        if (checkboxLabel) checkboxLabel.textContent = 'Download available before enrollment';
                    }
                };
            });

            var pdfInput = modal.querySelector('#liva-edit-pdf-file-<?php echo $l->ID; ?>');
            var pdfTitle = modal.querySelector('.liva-dropzone-pdf-section .liva-dropzone-title');
            var pdfSub   = modal.querySelector('.liva-dropzone-pdf-section .liva-dropzone-sub');
            if (pdfInput && pdfTitle) {
                pdfInput.onchange = function() {
                    if (this.files && this.files[0]) {
                        pdfTitle.textContent = this.files[0].name;
                        pdfTitle.style.fontWeight = '700';
                        pdfTitle.style.color = '#171b26';
                        if (pdfSub) pdfSub.textContent = 'New file selected';
                    }
                };
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    private static function course_students_tab(int $id): void {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.*, u.display_name, u.user_email 
                 FROM {$wpdb->prefix}liva_enrollments e 
                 LEFT JOIN {$wpdb->users} u ON u.ID = e.user_id 
                 WHERE e.course_id = %d 
                 ORDER BY e.created_at DESC",
                $id
            ),
            ARRAY_A
        );
        ?>
        <div class="liva-panel">
            <div class="liva-filter-row">
                <input class="liva-input liva-search" placeholder="Search students">
                <select class="liva-select" style="max-width:190px">
                    <option>All progress</option>
                    <option>Not started</option>
                    <option>In progress</option>
                    <option>Completed</option>
                </select>
            </div>
            <div class="liva-table-wrap">
                <table class="liva-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Enrolled</th>
                            <th>Progress</th>
                            <th>Last activity</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="5">No enrolled students yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <strong><?php echo self::h($r['display_name'] ?: 'Student'); ?></strong><br>
                                    <small><?php echo self::h($r['user_email'] ?: ''); ?></small>
                                </td>
                                <td><?php echo esc_html(mysql2date('M j, Y', $r['created_at'])); ?></td>
                                <td style="min-width:180px">
                                    <div class="liva-progress">
                                        <span style="width:<?php echo (int) $r['progress']; ?>%"></span>
                                    </div>
                                    <small><?php echo (int) $r['progress']; ?>%</small>
                                </td>
                                <td>—</td>
                                <td><button class="liva-mini-btn">⋮</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private static function course_reviews_tab(): void {
        ?>
        <div class="liva-empty">
            <div class="symbol">★</div>
            <h3>No reviews yet</h3>
            <p>The review tab is wired into the course management structure. Production review moderation can be added later.</p>
        </div>
        <?php
    }

    private static function course_settings_tab(WP_Post $c): void {
        $level   = get_post_meta($c->ID, '_liva_level', true) ?: 'Basic';
        $price   = get_post_meta($c->ID, '_liva_price', true) ?: 39;
        $status  = $c->post_status;

        $avatar_url = get_post_meta($c->ID, '_liva_avatar_url', true);
        if (!$avatar_url && has_post_thumbnail($c->ID)) {
            $avatar_url = get_the_post_thumbnail_url($c->ID, 'medium');
        }
        if (!$avatar_url) {
            $avatar_url = get_template_directory_uri() . '/assets/img/course-placeholder.png';
        }

        $cover_url = get_post_meta($c->ID, '_liva_cover_image_url', true);
        if (!$cover_url) {
            $cover_url = get_template_directory_uri() . '/assets/img/course-placeholder.png';
        }
        ?>
        <div class="liva-course-settings-wrap">
            <form id="liva-course-settings-form" method="post" enctype="multipart/form-data" class="liva-course-settings-form">
                <?php wp_nonce_field('liva_update_course_settings', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="update_course_settings">
                <input type="hidden" name="course_id" value="<?php echo (int) $c->ID; ?>">

                <!-- Course Title -->
                <div class="liva-form-group">
                    <label>Course title</label>
                    <input class="liva-input" name="title" value="<?php echo esc_attr($c->post_title); ?>" required>
                </div>

                <!-- Level & Price Row -->
                <div class="liva-form-row">
                    <div class="liva-form-group">
                        <label>Level</label>
                        <div class="liva-select-wrapper">
                            <select class="liva-select" name="level">
                                <option value="Basic" <?php selected($level, 'Basic'); ?>>Basic</option>
                                <option value="Intermediate" <?php selected($level, 'Intermediate'); ?>>Intermediate</option>
                                <option value="Advanced" <?php selected($level, 'Advanced'); ?>>Advanced</option>
                            </select>
                        </div>
                    </div>
                    <div class="liva-form-group">
                        <label>Price (USD)</label>
                        <input class="liva-input" type="number" name="price" value="<?php echo esc_attr((int)$price); ?>" min="0" step="1">
                    </div>
                </div>

                <!-- Visibility -->
                <div class="liva-form-row">
                    <div class="liva-form-group">
                        <label>Visibility</label>
                        <div class="liva-select-wrapper">
                            <select class="liva-select" name="visibility">
                                <option value="publish" <?php selected($status, 'publish'); ?>>Published</option>
                                <option value="draft" <?php selected($status, 'draft'); ?>>Draft</option>
                            </select>
                        </div>
                    </div>
                    <div></div>
                </div>

                <!-- Clickable Avatar & Cover Upload Areas -->
                <div class="liva-settings-images-row">
                    <div class="liva-settings-img-col liva-settings-avatar-col">
                        <label>Avatar image</label>
                        <label class="liva-settings-image-picker liva-settings-avatar-picker" for="liva-settings-avatar-file">
                            <input type="file" id="liva-settings-avatar-file" name="avatar_image" accept="image/png, image/jpeg, image/webp" style="display:none;">
                            <img id="liva-settings-avatar-preview-img" src="<?php echo esc_url($avatar_url); ?>" alt="Avatar preview">
                            <div class="liva-settings-img-overlay">
                                <span>Change image</span>
                            </div>
                        </label>
                    </div>

                    <div class="liva-settings-img-col liva-settings-cover-col">
                        <label>Cover image</label>
                        <label class="liva-settings-image-picker liva-settings-cover-picker" for="liva-settings-cover-file">
                            <input type="file" id="liva-settings-cover-file" name="cover_image" accept="image/png, image/jpeg, image/webp" style="display:none;">
                            <img id="liva-settings-cover-preview-img" src="<?php echo esc_url($cover_url); ?>" alt="Cover preview">
                            <div class="liva-settings-img-overlay">
                                <span>Change image</span>
                            </div>
                        </label>
                    </div>
                </div>
            </form>

            <!-- Danger Zone Card -->
            <div class="liva-settings-danger-card">
                <div class="liva-danger-block">
                    <h4>Archive course</h4>
                    <p>Hides the course from new enrollment. Existing students keep access.</p>
                    <form method="post" class="liva-inline-form">
                        <?php wp_nonce_field('liva_archive_course', '_liva_nonce'); ?>
                        <input type="hidden" name="liva_action" value="archive_course">
                        <input type="hidden" name="course_id" value="<?php echo (int) $c->ID; ?>">
                        <button type="submit" class="liva-btn-outline-danger">Archive</button>
                    </form>
                </div>

                <div class="liva-danger-divider"></div>

                <div class="liva-danger-block">
                    <h4>Delete course</h4>
                    <p>Permanently removes the course, its modules, lessons, and all enrollment records. This cannot be undone.</p>
                    <form method="post" class="liva-inline-form" onsubmit="return confirm('Are you sure you want to permanently delete this course? This cannot be undone.');">
                        <?php wp_nonce_field('liva_delete_course', '_liva_nonce'); ?>
                        <input type="hidden" name="liva_action" value="delete_course">
                        <input type="hidden" name="course_id" value="<?php echo (int) $c->ID; ?>">
                        <button type="submit" class="liva-btn-outline-danger">Delete course</button>
                    </form>
                </div>
            </div>

            <!-- Save Changes Placed Underneath Danger Zone -->
            <div class="liva-settings-submit-bottom">
                <button type="submit" form="liva-course-settings-form" class="liva-btn liva-btn-primary">Save changes</button>
            </div>
        </div>

        <script>
        (function() {
            function bindPicker(inputId, imgId) {
                var input = document.getElementById(inputId);
                var img   = document.getElementById(imgId);
                if (!input || !img) return;

                input.addEventListener('change', function() {
                    var file = this.files && this.files[0];
                    if (file && file.type.startsWith('image/')) {
                        img.src = URL.createObjectURL(file);
                    }
                });
            }
            bindPicker('liva-settings-avatar-file', 'liva-settings-avatar-preview-img');
            bindPicker('liva-settings-cover-file', 'liva-settings-cover-preview-img');
        })();
        </script>
        <?php
    }

    public static function admin_placement(): string {
        $tab  = sanitize_key($_GET['tab'] ?? 'questions');
        $view = sanitize_key($_GET['view'] ?? '');
        $qid  = absint($_GET['id'] ?? 0);

        if ($view === 'question' && $qid) {
            return self::question_view($qid);
        }

        global $wpdb;
        $count = (int) wp_count_posts('liva_question')->publish;
        $subs  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}liva_test_submissions");

        ob_start();
        echo self::status();
        $target_modal = ($count >= 30) ? 'liva-question-limit-modal' : 'liva-add-question';

        echo self::app_title(
            'Placement test',
            '30 questions · 4 stages · 100 points total',
            '<div class="liva-header-btn-group">'
                . '<button type="button" class="liva-btn liva-btn-primary liva-btn-sm liva-btn-icon" data-liva-modal-open="' . esc_attr($target_modal) . '">'
                . (function_exists('liva_icon') && liva_icon('plus') ? liva_icon('plus') : '+') . 'Add question'
                . '</button>'
            . '</div>'
        );
        ?>
        <div class="liva-stat-grid">
            <div class="liva-stat-card">
                <div class="liva-stat-label">Questions</div>
                <div class="liva-stat-value"><?php echo $count; ?></div>
            </div>
            <div class="liva-stat-card">
                <div class="liva-stat-label">Total points</div>
                <div class="liva-stat-value">100</div>
            </div>
            <div class="liva-stat-card">
                <div class="liva-stat-label">Stages</div>
                <div class="liva-stat-value">3</div>
            </div>
            <div class="liva-stat-card">
                <div class="liva-stat-label">Submissions</div>
                <div class="liva-stat-value"><?php echo $subs; ?></div>
            </div>
        </div>

        <div class="liva-tabs">
            <a class="<?php echo $tab === 'questions' ? 'active' : ''; ?>" href="<?php echo esc_url(self::page('liva-placement-tests')); ?>">Question bank</a>
            <a class="<?php echo $tab === 'scoring' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('tab', 'scoring', self::page('liva-placement-tests'))); ?>">Scoring and levels</a>
            <a class="<?php echo $tab === 'submissions' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('tab', 'submissions', self::page('liva-placement-tests'))); ?>">Submissions</a>
        </div>

        <?php
        if ($tab === 'scoring') {
            self::placement_scoring();
        } elseif ($tab === 'submissions') {
            self::placement_submissions();
        } else {
            self::placement_questions();
        }

        echo self::add_question_modal();
        echo self::delete_question_modal();
        echo self::question_limit_modal();
        ?>

        <script>
        (function() {
            document.addEventListener('click', function(e) {
                // 1. Open any modal with [data-liva-modal-open]
                var openBtn = e.target.closest('[data-liva-modal-open]');
                if (openBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    var targetId = openBtn.getAttribute('data-liva-modal-open');
                    var targetModal = document.getElementById(targetId);
                    if (targetModal) {
                        targetModal.removeAttribute('hidden');
                        targetModal.style.setProperty('display', 'grid', 'important');
                        document.body.classList.add('liva-modal-open');
                    }
                    return;
                }

                // 2. Close modals with [data-liva-modal-close] or cancel buttons
                var closeBtn = e.target.closest('[data-liva-modal-close], .liva-btn-cancel-modal');
                if (closeBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    var modal = closeBtn.closest('.liva-modal-backdrop');
                    if (modal) {
                        modal.setAttribute('hidden', '');
                        modal.style.setProperty('display', 'none', 'important');
                        document.body.classList.remove('liva-modal-open');
                    }
                    return;
                }

                // 3. Close when clicking the dark backdrop area outside the modal card
                if (e.target.classList.contains('liva-modal-backdrop')) {
                    e.target.setAttribute('hidden', '');
                    e.target.style.setProperty('display', 'none', 'important');
                    document.body.classList.remove('liva-modal-open');
                    return;
                }
            });

            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.liva-modal-backdrop:not([hidden])').forEach(function(modal) {
                        modal.setAttribute('hidden', '');
                        modal.style.setProperty('display', 'none', 'important');
                        document.body.classList.remove('liva-modal-open');
                    });
                }
            });
        })();
        </script>

        <?php
        return self::shell('liva-placement-tests', ob_get_clean());
    }

    private static function placement_questions(): void {
        $stage_filter = absint($_GET['stage'] ?? 0);
        $args = [
            'post_type'      => 'liva_question',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC'
        ];

        if ($stage_filter > 0) {
            $args['meta_query'] = [
                [
                    'key'   => '_liva_stage',
                    'value' => $stage_filter,
                ]
            ];
        }

        $qs = get_posts($args);

        usort($qs, function($a, $b) {
            $stageA = (int) get_post_meta($a->ID, '_liva_stage', true);
            $stageB = (int) get_post_meta($b->ID, '_liva_stage', true);
            if ($stageA === $stageB) {
                return $a->ID <=> $b->ID;
            }
            return $stageA <=> $stageB;
        });
        ?>
        <div class="liva-panel liva-panel-borderless">
            <div class="liva-stage-filter-bar">
                <strong>Filter by stage:</strong>
                <div class="liva-stage-pills">
                    <a class="liva-stage-pill <?php echo $stage_filter === 0 ? 'active' : ''; ?>" href="<?php echo esc_url(remove_query_arg(['stage', 'pg'])); ?>">All</a>
                    <?php for ($s = 1; $s <= 4; $s++): ?>
                        <a class="liva-stage-pill <?php echo $stage_filter === $s ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['stage' => $s, 'pg' => 1])); ?>">Stage <?php echo $s; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="liva-table-wrap">
                <table class="liva-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Stage</th>
                            <th>Pts</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total       = (int) wp_count_posts('liva_question')->publish;
                        $edit_modals = '';
                        foreach ($qs as $i => $q):
                            $stage = (int) get_post_meta($q->ID, '_liva_stage', true);
                        ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo self::h($q->post_title); ?></td>
                                <td><span class="liva-stage-badge liva-stage-<?php echo esc_attr($stage); ?>">Stage <?php echo $stage; ?></span></td>
                                <td><?php echo self::h(get_post_meta($q->ID, '_liva_points', true)); ?></td>
                                <td>
                                    <div class="liva-table-actions">
                                        <button type="button" class="liva-icon-btn" title="Edit question" data-liva-modal-open="liva-edit-question-<?php echo (int) $q->ID; ?>">
                                            <?php echo function_exists('liva_icon') && liva_icon('edit') ? liva_icon('edit') : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20H21"/><path d="M16.5 3.5C17.3 2.7 18.7 2.7 19.5 3.5C20.3 4.3 20.3 5.7 19.5 6.5L7 19L3 20L4 16L16.5 3.5Z"/></svg>'; ?>
                                        </button>
                                        <form method="post" class="liva-inline-form" id="liva-delete-form-<?php echo (int) $q->ID; ?>">
                                            <?php wp_nonce_field('liva_delete_question', '_liva_nonce'); ?>
                                            <input type="hidden" name="liva_action" value="delete_question">
                                            <input type="hidden" name="question_id" value="<?php echo (int) $q->ID; ?>">
                                            <button type="button" class="liva-icon-btn liva-icon-btn-delete liva-trigger-delete-modal" data-target="liva-delete-form-<?php echo (int) $q->ID; ?>" title="Delete question">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7H20"/><path d="M6 7V20C6 20.6 6.4 21 7 21H17C17.6 21 18 20.6 18 20V7"/><path d="M9 7V4C9 3.4 9.4 3 10 3H14C14.6 3 15 3.4 15 4V7"/><path d="M10 11V17"/><path d="M14 11V17"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php $edit_modals .= self::edit_question_modal($q, $i + 1, $total); ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php echo $edit_modals; ?>
        </div>
        <?php
    }

    private static function placement_scoring(): void {
        $ranges = get_option('liva_level_ranges', [
            'Basic'        => [0, 45],
            'Intermediate' => [50, 79],
            'Advanced'     => [80, 100]
        ]);
        $b = (int) ($ranges['Basic'][1] ?? 45);
        $i = (int) ($ranges['Intermediate'][1] ?? 79);
        ?>
        <div class="liva-panel liva-scoring-panel">
            <div class="liva-scoring-section">
                <div class="liva-scoring-head">
                    <h2>Stage weighting</h2>
                    <p>Points per question by difficulty stage</p>
                </div>
                <div class="liva-table-wrap">
                    <table class="liva-table liva-stage-table">
                        <thead>
                            <tr>
                                <th style="width: 45%;">STAGE</th>
                                <th style="width: 18%;">QUESTIONS</th>
                                <th style="width: 18%;">PTS</th>
                                <th style="width: 19%;">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Stage 1 - Foundation knowledge</td>
                                <td>10</td>
                                <td>2.0</td>
                                <td>20</td>
                            </tr>
                            <tr>
                                <td>Stage 2 - Communication and grammar</td>
                                <td>10</td>
                                <td>3.0</td>
                                <td>30</td>
                            </tr>
                            <tr>
                                <td>Stage 3 - Vocabulary, idioms and culture</td>
                                <td>7</td>
                                <td>5.0</td>
                                <td>35</td>
                            </tr>
                            <tr>
                                <td>Stage 4 - Reading comprehension</td>
                                <td>3</td>
                                <td>5.0</td>
                                <td>15</td>
                            </tr>
                            <tr class="liva-stage-table-total">
                                <td>Total</td>
                                <td>30</td>
                                <td></td>
                                <td>100</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <form class="liva-scoring-section" method="post" style="margin-top: 52px;">
                <div class="liva-scoring-head">
                    <h2>Learner classification</h2>
                    <p>Score ranges that map to each level.</p>
                </div>

                <div class="liva-level-cards-grid">
                    <div class="liva-level-metric-card">
                        <span class="liva-level-card-label">BASIC</span>
                        <div class="liva-level-card-range" id="liva-range-basic">0 - <?php echo $b; ?> points</div>
                        <div class="liva-range-inputs">
                            <input class="liva-range-box" type="text" value="0" readonly tabindex="-1">
                            <span class="liva-range-to">to</span>
                            <input class="liva-range-box liva-range-box-active" type="number" id="liva-basic-max" name="basic_max" value="<?php echo $b; ?>" min="1" max="98" required>
                        </div>
                    </div>

                    <div class="liva-level-metric-card">
                        <span class="liva-level-card-label">INTERMEDIATE</span>
                        <div class="liva-level-card-range" id="liva-range-intermediate"><?php echo $b + 1; ?> - <?php echo $i; ?> points</div>
                        <div class="liva-range-inputs">
                            <input class="liva-range-box" type="text" id="liva-intermediate-min" value="<?php echo $b + 1; ?>" readonly tabindex="-1">
                            <span class="liva-range-to">to</span>
                            <input class="liva-range-box liva-range-box-active" type="number" id="liva-intermediate-max" name="intermediate_max" value="<?php echo $i; ?>" min="<?php echo $b + 2; ?>" max="99" required>
                        </div>
                    </div>

                    <div class="liva-level-metric-card">
                        <span class="liva-level-card-label">ADVANCED</span>
                        <div class="liva-level-card-range" id="liva-range-advanced"><?php echo $i + 1; ?> - 100 points</div>
                        <div class="liva-range-inputs">
                            <input class="liva-range-box" type="text" id="liva-advanced-min" value="<?php echo $i + 1; ?>" readonly tabindex="-1">
                            <span class="liva-range-to">to</span>
                            <input class="liva-range-box" type="text" value="100" readonly tabindex="-1">
                        </div>
                    </div>
                </div>

                <?php wp_nonce_field('liva_save_scoring', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="save_scoring">

                <div class="liva-scoring-bottom-actions">
                    <button type="submit" class="liva-btn liva-btn-primary liva-btn-sm">Save changes</button>
                </div>
            </form>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var basicMax = document.getElementById('liva-basic-max');
            var interMax = document.getElementById('liva-intermediate-max');
            var interMin = document.getElementById('liva-intermediate-min');
            var advMin   = document.getElementById('liva-advanced-min');
            var rangeBasic = document.getElementById('liva-range-basic');
            var rangeInter = document.getElementById('liva-range-intermediate');
            var rangeAdv   = document.getElementById('liva-range-advanced');

            function syncRanges() {
                var bVal = parseInt(basicMax.value, 10) || 0;
                var iVal = parseInt(interMax.value, 10) || (bVal + 1);

                if (iVal <= bVal) {
                    iVal = bVal + 1;
                    interMax.value = iVal;
                }

                interMin.value = bVal + 1;
                advMin.value   = iVal + 1;

                interMax.min = bVal + 2;

                rangeBasic.textContent = '0 - ' + bVal + ' points';
                rangeInter.textContent = (bVal + 1) + ' - ' + iVal + ' points';
                rangeAdv.textContent   = (iVal + 1) + ' - 100 points';
            }

            if (basicMax && interMax) {
                basicMax.addEventListener('input', syncRanges);
                interMax.addEventListener('input', syncRanges);
            }
        });
        </script>
        <?php
    }

    private static function placement_submissions(): void {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT s.*, u.display_name, u.user_email 
             FROM {$wpdb->prefix}liva_test_submissions s 
             LEFT JOIN {$wpdb->users} u ON u.ID = s.user_id 
             ORDER BY s.created_at DESC 
             LIMIT 50",
            ARRAY_A
        );
        ?>
        <div class="liva-panel">
            <div class="liva-table-wrap">
                <table class="liva-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Score</th>
                            <th>Level</th>
                            <th>Taken</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="5">No submissions yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <?php echo self::h($r['display_name'] ?: 'Guest learner'); ?><br>
                                    <small><?php echo self::h($r['user_email'] ?: ''); ?></small>
                                </td>
                                <td><?php echo (int) $r['score']; ?> / 100</td>
                                <td><span class="liva-pill"><?php echo self::h($r['level']); ?></span></td>
                                <td><?php echo esc_html(mysql2date('M j, Y', $r['created_at'])); ?></td>
                                <td>
                                    <a class="liva-mini-btn" href="<?php echo esc_url(add_query_arg('submission', $r['id'], self::page('placement-test-result'))); ?>">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private static function add_question_modal(): string {
        $correct = 0; // Default first option as correct
        $options = ['', '', '', ''];

        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="liva-add-question" hidden>
            <form class="liva-modal" method="post">
                <div class="liva-modal-head">
                    <div>
                        <h2>Add questions</h2>
                    </div>
                    <button type="button" class="liva-modal-close" data-liva-modal-close>×</button>
                </div>
                <div class="liva-form-group">
                    <label>Question text</label>
                    <textarea class="liva-textarea" name="question" rows="4" placeholder="Type question text" required></textarea>
                </div>
                <div class="liva-form-row">
                    <div class="liva-form-group">
                        <label>Stage</label>
                        <select class="liva-select" name="stage" id="liva-add-stage">
                            <option value="1">Stage 1</option>
                            <option value="2">Stage 2</option>
                            <option value="3">Stage 3</option>
                            <option value="4">Stage 4</option>
                        </select>
                    </div>
                    <div class="liva-form-group">
                        <label>Points</label>
                        <input class="liva-input" id="liva-add-points" name="points" type="number" step="0.5" min="0" value="2.0">
                    </div>
                </div>
                <div class="liva-form-group" style="margin-bottom: 8px;">
                    <label>Answer options</label>
                </div>
                <div class="liva-option-list">
                    <?php for ($i = 0; $i < 4; $i++):
                        $letter = chr(65 + $i);
                        $value  = $options[$i] ?? '';
                        $is_checked = ($correct === $i);
                    ?>
                        <div class="liva-option-field-row">
                            <label class="liva-custom-radio-label">
                                <input type="radio" 
                                       name="correct" 
                                       value="<?php echo $i; ?>" 
                                       class="liva-option-radio liva-correct-radio" 
                                       data-letter="<?php echo $letter; ?>" 
                                       <?php checked($correct, $i); ?>>
                                <span class="liva-radio-icon-wrap">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <circle cx="12" cy="12" r="9"></circle>
                                    </svg>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2.5"></circle>
                                        <circle cx="12" cy="12" r="4.5" fill="currentColor"></circle>
                                    </svg>
                                </span>
                            </label>
                            <input class="liva-input liva-option-input" 
                                   name="options[]" 
                                   value="<?php echo esc_attr($value); ?>" 
                                   placeholder="Type answer option">
                        </div>
                    <?php endfor; ?>
                </div>
                <p class="liva-help" style="margin-top: 10px; margin-bottom: 24px;">Select the radio button next to the correct answer.</p>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var stageSelect = document.getElementById('liva-add-stage');
                    var pointsInput = document.getElementById('liva-add-points');
                    if (stageSelect && pointsInput) {
                        var stagePoints = { '1': '2.0', '2': '3.0', '3': '5.0', '4': '5.0' };
                        stageSelect.addEventListener('change', function() {
                            pointsInput.value = stagePoints[this.value] || '2.0';
                        });
                    }
                });
                </script>
                <?php wp_nonce_field('liva_add_question', '_liva_nonce'); ?>
                <input type="hidden" name="liva_action" value="add_question">
                <div class="liva-modal-actions">
                    <button type="button" class="liva-btn liva-btn-outline" data-liva-modal-close>Cancel</button>
                    <button class="liva-btn liva-btn-primary">Save question</button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function edit_question_modal(WP_Post $q, int $position, int $total): string {
        $id      = $q->ID;
        $stage   = (int) get_post_meta($id, '_liva_stage', true);
        $points  = get_post_meta($id, '_liva_points', true);
        $options = (array) get_post_meta($id, '_liva_options', true);
        $correct = (int) get_post_meta($id, '_liva_correct', true);
        $edited  = $q->post_modified ? date_i18n('M j, Y', strtotime($q->post_modified)) : '—';
        $form_id = 'liva-edit-form-' . $id;
        $hint_id = 'liva-correct-hint-' . $id;

        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="liva-edit-question-<?php echo (int) $id; ?>" hidden>
            <div class="liva-modal liva-modal-standard">
                <div class="liva-modal-head">
                    <div>
                        <h2>Edit questions</h2>
                        <p class="liva-modal-subtitle">Question <?php echo (int) $position; ?> of <?php echo (int) $total; ?> - last edited <?php echo self::h($edited); ?></p>
                    </div>
                    <button type="button" class="liva-modal-close" data-liva-modal-close aria-label="Close">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <form method="post" id="<?php echo esc_attr($form_id); ?>" class="liva-edit-question-form">
                    <?php wp_nonce_field('liva_update_question', '_liva_nonce'); ?>
                    <input type="hidden" name="liva_action" value="update_question">
                    <input type="hidden" name="question_id" value="<?php echo (int) $id; ?>">

                    <div class="liva-form-group">
                        <label>Question text</label>
                        <textarea class="liva-textarea" name="question" rows="3" required><?php echo self::h($q->post_title); ?></textarea>
                    </div>

                    <div class="liva-form-row">
                        <div class="liva-form-group">
                            <label>Stage</label>
                            <div class="liva-select-wrapper">
                                <select class="liva-select" name="stage" id="liva-edit-stage-<?php echo (int) $id; ?>">
                                    <?php for ($s = 1; $s <= 4; $s++): ?>
                                        <option value="<?php echo $s; ?>" <?php selected($stage, $s); ?>>Stage <?php echo $s; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="liva-form-group">
                            <label>Points</label>
                            <input class="liva-input" id="liva-edit-points-<?php echo (int) $id; ?>" type="number" step="0.5" min="0" name="points" value="<?php echo esc_attr($points); ?>">
                        </div>
                    </div>

                    <div class="liva-form-group" style="margin-bottom: 8px;">
                        <label>Answer options</label>
                    </div>

                    <div class="liva-option-list">
                        <?php for ($i = 0; $i < 4; $i++):
                            $letter = chr(65 + $i);
                            $value  = $options[$i] ?? '';
                            $is_checked = ($correct === $i);
                        ?>
                            <div class="liva-option-field-row">
                                <label class="liva-custom-radio-label">
                                    <input type="radio" 
                                        name="correct" 
                                        value="<?php echo $i; ?>" 
                                        class="liva-option-radio liva-correct-radio" 
                                        data-letter="<?php echo $letter; ?>" 
                                        <?php checked($correct, $i); ?>>
                                    <span class="liva-radio-icon-wrap">
                                        <!-- Unchecked SVG (Clean outer ring) -->
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <circle cx="12" cy="12" r="9"></circle>
                                        </svg>
                                        <!-- Checked SVG (Outer ring + solid inner dot) -->
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2.5"></circle>
                                            <circle cx="12" cy="12" r="4.5" fill="currentColor"></circle>
                                        </svg>
                                    </span>
                                </label>
                                <input class="liva-input liva-option-input" 
                                    name="options[]" 
                                    value="<?php echo esc_attr($value); ?>" 
                                    placeholder="Type answer option">
                            </div>
                        <?php endfor; ?>
                    </div>

                    <p class="liva-help liva-correct-hint" id="<?php echo esc_attr($hint_id); ?>" style="margin-top: 12px; margin-bottom: 24px; font-size: 13px; color: #687384;">
                        Currently correct: <?php echo $correct >= 0 && isset($options[$correct]) ? chr(65 + $correct) . '. ' . self::h($options[$correct]) : '—'; ?>
                    </p>
                </form>

                <div class="liva-modal-actions liva-modal-actions-split">
                    <button type="button" class="liva-btn liva-btn-delete-outline liva-trigger-delete-modal" data-target="liva-delete-form-<?php echo (int) $id; ?>">
                        <?php echo function_exists('liva_icon') && liva_icon('recycle_bin') ? liva_icon('recycle_bin') : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>'; ?>
                        <span>Delete</span>
                    </button>
                    <div class="liva-modal-actions-right">
                        <button type="button" class="liva-btn liva-btn-cancel-modal" data-liva-modal-close>Cancel</button>
                        <button type="submit" form="<?php echo esc_attr($form_id); ?>" class="liva-btn liva-btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function() {
            var form = document.getElementById('<?php echo esc_js($form_id); ?>');
            if (!form) return;

            var stageSelect = document.getElementById('liva-edit-stage-<?php echo (int) $id; ?>');
            var pointsInput = document.getElementById('liva-edit-points-<?php echo (int) $id; ?>');
            var stagePoints = { '1': '2.0', '2': '3.0', '3': '5.0', '4': '5.0' };

            if (stageSelect && pointsInput) {
                stageSelect.addEventListener('change', function() {
                    pointsInput.value = stagePoints[this.value] || '2.0';
                });
            }

            function updateHint() {
                var checked = form.querySelector('.liva-correct-radio:checked');
                var hint = document.getElementById('<?php echo esc_js($hint_id); ?>');
                if (!checked || !hint) return;
                var row = checked.closest('.liva-option-field-row');
                var text = row ? row.querySelector('.liva-option-input').value : '';
                hint.textContent = 'Currently correct: ' + checked.dataset.letter + '. ' + (text || '—');
            }

            form.addEventListener('input', function(e) {
                if (e.target.classList.contains('liva-option-input')) updateHint();
            });
            form.addEventListener('change', function(e) {
                if (e.target.classList.contains('liva-correct-radio')) updateHint();
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    private static function question_limit_modal(): string {
        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="liva-question-limit-modal" hidden>
            <div class="liva-modal" style="max-width:440px; text-align:center; padding:32px 26px;">
                <div style="width:54px; height:54px; background:#fff4ce; color:#ca8b04; border-radius:50%; display:grid; place-items:center; margin:0 auto 16px;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <h3 style="margin:0 0 8px; font-size:20px; font-weight:700; color:#171b26;">Question limit reached</h3>
                <p style="margin:0 0 24px; color:var(--liva-muted); font-size:14px; line-height:1.5;">
                    The placement test has reached its maximum limit of <strong>30 questions</strong> (100 points total). You cannot add more questions until you remove an existing one.
                </p>
                <div style="display:flex; justify-content:center;">
                    <button type="button" class="liva-btn liva-btn-primary liva-btn-sm" data-liva-modal-close>Understood</button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function delete_question_modal(): string {
        ob_start();
        ?>
        <div class="liva-modal-backdrop" id="liva-delete-question-modal" hidden>
            <div class="liva-modal" style="max-width:420px; text-align:center; padding:32px 26px;">
                <div style="width:52px; height:52px; background:#fde8e8; color:var(--liva-danger); border-radius:50%; display:grid; place-items:center; margin:0 auto 16px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                </div>
                <h3 style="margin:0 0 8px; font-size:20px; font-weight:700;">Delete question?</h3>
                <p style="margin:0 0 24px; color:var(--liva-muted); font-size:14px; line-height:1.5;">This question will be permanently deleted. You won’t be able to undo this action.</p>
                <div style="display:flex; gap:12px; justify-content:center;">
                    <button type="button" class="liva-btn liva-btn-outline liva-btn-sm" data-liva-modal-close>Cancel</button>
                    <button type="button" class="liva-btn liva-btn-danger liva-btn-sm" id="liva-confirm-delete-btn" style="background:var(--liva-danger); color:#fff; border-color:var(--liva-danger);">Delete</button>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('liva-delete-question-modal');
            var confirmBtn = document.getElementById('liva-confirm-delete-btn');
            var targetForm = null;

            document.querySelectorAll('.liva-trigger-delete-modal').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    targetForm = document.getElementById(this.getAttribute('data-target'));
                    var parentModal = this.closest('.liva-modal-backdrop');
                    if (parentModal && parentModal !== modal) {
                        parentModal.setAttribute('hidden', '');
                    }
                    if (targetForm && modal) {
                        modal.removeAttribute('hidden');
                    }
                });
            });

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    if (targetForm) {
                        targetForm.submit();
                    }
                });
            }

            if (modal) {
                modal.querySelectorAll('[data-liva-modal-close]').forEach(function(el) {
                    el.addEventListener('click', function() {
                        modal.setAttribute('hidden', '');
                    });
                });
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.setAttribute('hidden', '');
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private static function question_view(int $id): string {
        $q = get_post($id);
        if (!$q) {
            return self::shell('liva-placement-tests', '<p>Question not found.</p>');
        }

        $options = (array) get_post_meta($id, '_liva_options', true);
        $correct = (int) get_post_meta($id, '_liva_correct', true);

        ob_start();
        ?>
        <a class="liva-btn liva-btn-outline liva-btn-sm liva-back-btn" href="<?php echo esc_url(self::page('liva-placement-tests')); ?>">← Back</a>
        <div class="liva-panel" style="max-width:760px;margin:30px auto">
            <div class="liva-panel-header">
                <div>
                    <span class="liva-pill">Stage <?php echo (int) get_post_meta($id, '_liva_stage', true); ?></span>
                    <h2><?php echo self::h($q->post_title); ?></h2>
                </div>
            </div>
            <?php foreach ($options as $i => $o): ?>
                <div class="liva-choice">
                    ◯ <?php echo self::h($o); ?>
                    <?php echo $i === $correct ? ' <strong>✓ correct</strong>' : ''; ?>
                </div>
            <?php endforeach; ?>

            <div class="liva-stat-grid" style="grid-template-columns:repeat(3,1fr)">
                <div class="liva-stat-card">
                    <div class="liva-stat-label">Points</div>
                    <div class="liva-stat-value"><?php echo self::h(get_post_meta($id, '_liva_points', true)); ?></div>
                </div>
                <div class="liva-stat-card">
                    <div class="liva-stat-label">Correct rate</div>
                    <div class="liva-stat-value">—</div>
                </div>
                <div class="liva-stat-card">
                    <div class="liva-stat-label">Times answered</div>
                    <div class="liva-stat-value">—</div>
                </div>
            </div>
            <div class="liva-modal-actions">
                <button class="liva-btn liva-btn-primary">Edit</button>
                <button class="liva-btn liva-btn-danger">Delete</button>
            </div>
        </div>
        <?php
        return self::shell('liva-placement-tests', ob_get_clean());
    }

    public static function admin_placeholder($atts = []): string {
        $a       = shortcode_atts(['title' => 'Section'], $atts);
        $slug    = get_post_field('post_name', get_queried_object_id());
        $content = self::app_title($a['title'], 'This navigation destination is present in the supplied design but its detailed workflow was not specified.')
            . '<div class="liva-empty"><div class="symbol">◇</div><h3>' . self::h($a['title']) . ' structure reserved</h3><p>The shell, permissions, and navigation are implemented without inventing an unsupported product workflow.</p></div>';
        return self::shell($slug, $content, true);
    }

    public static function student_placeholder($atts = []): string {
        $a       = shortcode_atts(['title' => 'Section'], $atts);
        $slug    = get_post_field('post_name', get_queried_object_id());
        $content = self::app_title($a['title'], 'Student account section.')
            . '<div class="liva-empty"><div class="symbol">◇</div><h3>' . self::h($a['title']) . '</h3><p>This destination is wired into the student navigation and ready for final content requirements.</p></div>';
        return self::shell($slug, $content, false);
    }

    public static function public_placeholder($atts = []): string {
        $a = shortcode_atts(['title' => 'Section'], $atts);
        return '<section class="liva-section"><div class="liva-shell"><div class="liva-centered-heading"><span class="liva-eyebrow">Liva Academy</span><h1>' . self::h($a['title']) . '</h1><p>This public section is linked and styled, but its detailed content was not included in the supplied designs.</p></div><div class="liva-empty"><div class="symbol">◇</div><h3>Structure ready</h3><p>Final functionality can be added without changing the site architecture.</p></div></div></section>';
    }
}