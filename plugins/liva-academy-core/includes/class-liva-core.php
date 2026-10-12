<?php
if (!defined('ABSPATH')) {
    exit;
}

class Liva_Core {

    public static function boot(): void {
        add_action('init', [__CLASS__, 'register_content_types']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets']);
        add_action('init', ['Liva_Actions', 'handle']);
        add_action('init', ['Liva_Shortcodes', 'register']);
        add_filter('template_include', [__CLASS__, 'protect_app_pages']);
        self::maybe_upgrade();
    }

    public static function assets(): void {
        wp_enqueue_style('liva-core', LIVA_CORE_URL . 'assets/css/core.css', [], LIVA_CORE_VERSION);
        wp_enqueue_script('liva-core', LIVA_CORE_URL . 'assets/js/core.js', [], LIVA_CORE_VERSION, true);
    }

    public static function register_content_types(): void {
        register_post_type('liva_course', [
            'labels'       => ['name' => 'Courses', 'singular_name' => 'Course'],
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => ['slug' => 'courses'],
            'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'comments'],
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-welcome-learn-more',
        ]);

        foreach ([
            'liva_module'    => ['Modules', 'Module'],
            'liva_lesson'    => ['Lessons', 'Lesson'],
            'liva_listening' => ['Listening videos', 'Listening video'],
            'liva_question'  => ['Placement questions', 'Placement question'],
        ] as $type => $labels) {
            register_post_type($type, [
                'labels'       => ['name' => $labels[0], 'singular_name' => $labels[1]],
                'public'       => false,
                'show_ui'      => true,
                'show_in_rest' => true,
                'supports'     => ['title', 'editor', 'thumbnail'],
            ]);
        }
    }

    public static function activate(): void {
        self::register_content_types();
        self::create_roles();
        update_option('users_can_register', 1);
        update_option('default_role', 'liva_student');
        self::create_tables();
        self::create_pages();
        self::seed_demo_content();
        update_option('liva_core_version', LIVA_CORE_VERSION);
        flush_rewrite_rules();
    }

    private static function maybe_upgrade(): void {
        $installed = (string) get_option('liva_core_version', '');
        if ($installed === LIVA_CORE_VERSION) {
            return;
        }

        self::create_roles();
        self::create_tables();
        self::create_pages();
        update_option('liva_core_version', LIVA_CORE_VERSION);
    }

    private static function create_roles(): void {
        add_role('liva_student', 'Student', ['read' => true]);
        add_role('liva_admin', 'Liva Admin', [
            'read'                 => true,
            'upload_files'         => true,
            'liva_manage_students' => true,
            'liva_manage_courses'  => true,
            'liva_manage_tests'    => true,
        ]);
        add_role('liva_super_admin', 'Liva Super Admin', [
            'read'                 => true,
            'upload_files'         => true,
            'liva_manage_students' => true,
            'liva_manage_courses'  => true,
            'liva_manage_tests'    => true,
            'liva_manage_admins'   => true,
            'liva_view_finance'    => true,
            'liva_manage_site'     => true,
        ]);

        $admin = get_role('administrator');
        if ($admin) {
            foreach ([
                'liva_manage_students',
                'liva_manage_courses',
                'liva_manage_tests',
                'liva_manage_admins',
                'liva_view_finance',
                'liva_manage_site'
            ] as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    private static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $enroll  = $wpdb->prefix . 'liva_enrollments';
        $subs    = $wpdb->prefix . 'liva_test_submissions';

        dbDelta("CREATE TABLE $enroll (
          id bigint unsigned NOT NULL AUTO_INCREMENT,
          user_id bigint unsigned NOT NULL,
          course_id bigint unsigned NOT NULL,
          status varchar(30) NOT NULL DEFAULT 'active',
          access_type varchar(30) NOT NULL DEFAULT 'lifetime',
          payment_method varchar(30) NOT NULL DEFAULT 'manual',
          progress tinyint unsigned NOT NULL DEFAULT 0,
          created_at datetime NOT NULL,
          expires_at datetime NULL,
          PRIMARY KEY (id), UNIQUE KEY user_course (user_id,course_id), KEY course_id (course_id)
        ) $charset;");

        dbDelta("CREATE TABLE $subs (
          id bigint unsigned NOT NULL AUTO_INCREMENT,
          user_id bigint unsigned NULL,
          score tinyint unsigned NOT NULL DEFAULT 0,
          level varchar(30) NOT NULL,
          correct_answers tinyint unsigned NOT NULL DEFAULT 0,
          time_taken smallint unsigned NOT NULL DEFAULT 0,
          answers longtext NULL,
          stage_scores longtext NULL,
          created_at datetime NOT NULL,
          PRIMARY KEY (id), KEY user_id (user_id)
        ) $charset;");
    }

    private static function create_pages(): void {
        $pages = [
            'login'                    => ['Log in', '[liva_login]'],
            'register'                 => ['Sign up', '[liva_register]'],
            'student-dashboard'        => ['Dashboard', '[liva_student_dashboard]'],
            'my-learning'              => ['My learning', '[liva_my_learning]'],
            'my-courses'               => ['My courses', '[liva_my_courses]'],
            'listening-practice'       => ['Listening practice', '[liva_listening]'],
            'placement-test'           => ['Placement test', '[liva_placement_intro]'],
            'placement-test-questions' => ['Placement test questions', '[liva_placement_runner]'],
            'placement-test-result'    => ['Placement test result', '[liva_placement_result]'],
            'admin-dashboard'          => ['Admin Dashboard', '[liva_admin_dashboard]'],
            'liva-users'               => ['Users', '[liva_admin_users]'],
            'liva-courses'             => ['Courses', '[liva_admin_courses]'],
            'liva-placement-tests'     => ['Placement tests', '[liva_admin_placement]'],
            'liva-listening-videos'    => ['Listening videos', '[liva_admin_placeholder title="Listening videos"]'],
            'liva-orders'              => ['Orders', '[liva_admin_placeholder title="Orders"]'],
            'liva-analytics'           => ['Analytics', '[liva_admin_placeholder title="Analytics"]'],
            'liva-settings'            => ['Settings', '[liva_admin_placeholder title="Settings"]'],
            'online-classroom'         => ['Online classroom', '[liva_public_placeholder title="Online classroom"]'],
            'pricing'                  => ['Pricing', '[liva_public_placeholder title="Pricing"]'],
            'company'                  => ['Company', '[liva_public_placeholder title="Company"]'],
            'order-history'            => ['Order history', '[liva_student_placeholder title="Order history"]'],
            'profile'                  => ['Profile', '[liva_student_placeholder title="Profile"]'],
            'notifications'            => ['Notifications', '[liva_student_placeholder title="Notifications"]'],
            'student-settings'         => ['Settings', '[liva_student_placeholder title="Settings"]'],
        ];

        foreach ($pages as $slug => $data) {
            if (!get_page_by_path($slug)) {
                wp_insert_post([
                    'post_title'   => $data[0],
                    'post_name'    => $slug,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => $data[1]
                ]);
            }
        }
    }

    private static function seed_demo_content(): void {
        if (get_option('liva_demo_seeded')) {
            return;
        }

        $course_ids = [];
        $courses = [
            ['Vietnamese For Complete Beginners', 'Basic', 600000],
            ['Everyday Vietnamese Conversations', 'Intermediate', 600000],
            ['Vietnamese Pronunciation Essentials', 'Basic', 600000],
            ['Vietnamese Grammar Foundations', 'Basic', 600000],
            ['Vietnamese Listening Skills', 'Intermediate', 600000],
            ['Vietnamese for Work', 'Advanced', 600000],
            ['Travel Vietnamese', 'Basic', 600000],
            ['Advanced Vietnamese Communication', 'Advanced', 600000],
        ];

        foreach ($courses as $c) {
            $id = wp_insert_post([
                'post_type'    => 'liva_course',
                'post_status'  => 'publish',
                'post_title'   => $c[0],
                'post_content' => 'Placeholder course description. Replace this from WordPress when real course content is ready.'
            ]);

            update_post_meta($id, '_liva_level', $c[1]);
            update_post_meta($id, '_liva_price', $c[2]);
            $course_ids[] = $id;

            for ($m = 1; $m <= 3; $m++) {
                $mid = wp_insert_post([
                    'post_type'   => 'liva_module',
                    'post_status' => 'publish',
                    'post_title'  => 'Module ' . $m
                ]);

                update_post_meta($mid, '_liva_course_id', $id);
                update_post_meta($mid, '_liva_position', $m);

                for ($l = 1; $l <= 3; $l++) {
                    $lid = wp_insert_post([
                        'post_type'   => 'liva_lesson',
                        'post_status' => 'publish',
                        'post_title'  => 'Lesson ' . $m . '.' . $l
                    ]);

                    update_post_meta($lid, '_liva_module_id', $mid);
                    update_post_meta($lid, '_liva_type', ($l === 1 ? 'video' : ($l === 2 ? 'pdf' : 'quiz')));
                    update_post_meta($lid, '_liva_duration', ($l * 4) . ' min');
                }
            }
        }

        for ($i = 1; $i <= 8; $i++) {
            $id = wp_insert_post([
                'post_type'    => 'liva_listening',
                'post_status'  => 'publish',
                'post_title'   => ($i === 1 ? 'At the coffee shop - ordering drinks' : 'Listening practice ' . $i),
                'post_content' => 'Placeholder transcript and lesson description.'
            ]);

            update_post_meta($id, '_liva_level', $i < 3 ? 'Basic' : ($i < 5 ? 'Intermediate' : 'Advanced'));
            update_post_meta($id, '_liva_duration', (4 + $i) . ':' . str_pad((string)($i * 7 % 60), 2, '0', STR_PAD_LEFT));
        }

        for ($i = 1; $i <= 30; $i++) {
            $stage  = $i <= 10 ? 1 : ($i <= 20 ? 2 : ($i <= 27 ? 3 : 4));
            $points = $stage === 1 ? 2 : ($stage === 2 ? 3 : 5);

            $id = wp_insert_post([
                'post_type'   => 'liva_question',
                'post_status' => 'publish',
                'post_title'  => 'Placeholder question ' . $i
            ]);

            update_post_meta($id, '_liva_stage', $stage);
            update_post_meta($id, '_liva_points', $points);
            update_post_meta($id, '_liva_options', ['Option A', 'Option B', 'Option C', 'Option D']);
            update_post_meta($id, '_liva_correct', 0);
        }

        update_option('liva_level_ranges', [
            'Basic'        => [0, 49],
            'Intermediate' => [50, 79],
            'Advanced'     => [80, 100]
        ]);
        update_option('liva_placement_estimated_minutes', 15);
        update_option('liva_demo_seeded', 1);
    }

    public static function page_url(string $slug): string {
        $page = get_page_by_path($slug);
        return $page ? get_permalink($page) : home_url('/' . $slug . '/');
    }

    public static function admin_allowed(): bool {
        return current_user_can('liva_manage_courses') || current_user_can('manage_options');
    }

    public static function protect_app_pages(string $template): string {
        if (!is_page()) {
            return $template;
        }

        $slug = get_post_field('post_name', get_queried_object_id());
        $student = [
            'student-dashboard',
            'my-courses',
            'listening-practice',
            'order-history',
            'profile',
            'notifications',
            'student-settings'
        ];
        $admin = [
            'admin-dashboard',
            'liva-users',
            'liva-courses',
            'liva-listening-videos',
            'liva-placement-tests',
            'liva-orders',
            'liva-analytics',
            'liva-settings'
        ];

        if (in_array($slug, $student, true) && !is_user_logged_in()) {
            wp_safe_redirect(self::page_url('login'));
            exit;
        }

        if (in_array($slug, $admin, true) && !self::admin_allowed()) {
            wp_safe_redirect(is_user_logged_in() ? self::page_url('student-dashboard') : self::page_url('login'));
            exit;
        }

        return $template;
    }
}