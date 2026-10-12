<?php
if (!defined('ABSPATH')) {
    exit;
}

class Liva_Actions {

    public static function handle(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['liva_action'])) {
            return;
        }

        $action = sanitize_key(wp_unslash($_POST['liva_action']));
        $public = ['login', 'register', 'placement_submit'];

        if (!in_array($action, $public, true) && !is_user_logged_in()) {
            return;
        }

        $method = 'do_' . $action;
        if (method_exists(__CLASS__, $method)) {
            self::$method();
        }
    }

    private static function nonce(string $action): void {
        if (empty($_POST['_liva_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_liva_nonce'])), 'liva_' . $action)) {
            wp_die('Security check failed.');
        }
    }

    private static function redirect(string $url, string $status = '', string $message = ''): void {
        if ($status) {
            $url = add_query_arg(['liva_status' => $status, 'liva_message' => rawurlencode($message)], $url);
        }
        wp_safe_redirect($url);
        exit;
    }

    private static function do_login(): void {
        self::nonce('login');

        $creds = [
            'user_login'    => sanitize_text_field(wp_unslash($_POST['email'] ?? '')),
            'user_password' => (string)($_POST['password'] ?? ''),
            'remember'      => !empty($_POST['remember'])
        ];

        $user = wp_signon($creds, is_ssl());
        if (is_wp_error($user)) {
            self::redirect(Liva_Core::page_url('login'), 'error', 'Invalid email/username or password.');
        }

        $target = (user_can($user, 'liva_manage_courses') || user_can($user, 'manage_options'))
            ? Liva_Core::page_url('admin-dashboard')
            : Liva_Core::page_url('student-dashboard');

        self::redirect($target);
    }

    private static function do_register(): void {
        self::nonce('register');

        if (!get_option('users_can_register')) {
            self::redirect(Liva_Core::page_url('register'), 'error', 'Registration is disabled in WordPress settings.');
        }

        $name     = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email    = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (!$email || strlen($password) < 8) {
            self::redirect(Liva_Core::page_url('register'), 'error', 'Use a valid email and a password of at least 8 characters.');
        }

        if (email_exists($email)) {
            self::redirect(Liva_Core::page_url('register'), 'error', 'That email is already registered.');
        }

        $base  = sanitize_user(strstr($email, '@', true) ?: 'student', true);
        $login = $base;
        $i     = 1;

        while (username_exists($login)) {
            $login = $base . $i++;
        }

        $uid = wp_create_user($login, $password, $email);
        if (is_wp_error($uid)) {
            self::redirect(Liva_Core::page_url('register'), 'error', $uid->get_error_message());
        }

        wp_update_user([
            'ID'           => $uid,
            'display_name' => $name ?: $login,
            'first_name'   => $name
        ]);

        (new WP_User($uid))->set_role('liva_student');
        wp_set_current_user($uid);
        wp_set_auth_cookie($uid, true);

        self::redirect(Liva_Core::page_url('student-dashboard'));
    }

    private static function do_add_course(): void {
        self::nonce('add_course');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $desc  = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $level = sanitize_text_field(wp_unslash($_POST['level'] ?? 'Basic'));
        $price = (float)($_POST['price'] ?? 0);

        $course_id = wp_insert_post([
            'post_type'    => 'liva_course',
            'post_status'  => 'draft',
            'post_title'   => $title,
            'post_content' => $desc,
        ]);

        if (is_wp_error($course_id)) {
            self::redirect(Liva_Core::page_url('liva-courses'), 'error', 'Could not create course.');
        }

        update_post_meta($course_id, '_liva_level', $level);
        update_post_meta($course_id, '_liva_price', $price);
        update_post_meta($course_id, '_liva_has_changes', 0);

        self::redirect(add_query_arg(['view' => 'course', 'id' => $course_id, 'tab' => 'curriculum'], Liva_Core::page_url('liva-courses')), 'success', 'Draft course created.');
    }

    private static function do_save_draft_course(): void {
        self::nonce('save_draft_course');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $id  = absint($_POST['course_id'] ?? 0);
        $tab = sanitize_key($_POST['current_tab'] ?? 'curriculum');

        if ($id && get_post_type($id) === 'liva_course') {
            wp_update_post([
                'ID'          => $id,
                'post_status' => 'draft'
            ]);
        }

        self::redirect(add_query_arg(['view' => 'course', 'id' => $id, 'tab' => $tab], Liva_Core::page_url('liva-courses')), 'success', 'Course saved as draft.');
    }

    private static function do_publish_course(): void {
        self::nonce('publish_course');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $id  = absint($_POST['course_id'] ?? 0);
        $tab = sanitize_key($_POST['current_tab'] ?? 'curriculum');

        if ($id && get_post_type($id) === 'liva_course') {
            $modules = get_posts([
                'post_type'      => 'liva_module',
                'posts_per_page' => 1,
                'meta_key'       => '_liva_course_id',
                'meta_value'     => $id,
                'fields'         => 'ids'
            ]);

            $has_lesson = false;
            foreach ($modules as $mid) {
                if (get_posts(['post_type' => 'liva_lesson', 'posts_per_page' => 1, 'meta_key' => '_liva_module_id', 'meta_value' => $mid, 'fields' => 'ids'])) {
                    $has_lesson = true;
                    break;
                }
            }

            if (!$has_lesson) {
                self::redirect(add_query_arg(['view' => 'course', 'id' => $id, 'tab' => $tab], Liva_Core::page_url('liva-courses')), 'error', 'Add at least one module with one lesson before publishing.');
            }

            wp_update_post([
                'ID'          => $id,
                'post_status' => 'publish'
            ]);
            update_post_meta($id, '_liva_has_changes', 1);
        }

        self::redirect(add_query_arg(['view' => 'course', 'id' => $id, 'tab' => $tab], Liva_Core::page_url('liva-courses')), 'success', 'Course published.');
    }

    private static function do_add_module(): void {
        self::nonce('add_module');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $course = absint($_POST['course_id'] ?? 0);
        $id     = wp_insert_post([
            'post_type'    => 'liva_module',
            'post_status'  => 'publish',
            'post_title'   => sanitize_text_field(wp_unslash($_POST['title'] ?? 'New module')),
            'post_content' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''))
        ]);

        update_post_meta($id, '_liva_course_id', $course);
        update_post_meta($id, '_liva_position', absint($_POST['position'] ?? 99));
        update_post_meta($course, '_liva_has_changes', 1);

        self::redirect(add_query_arg(['view' => 'course', 'id' => $course, 'tab' => 'curriculum'], Liva_Core::page_url('liva-courses')), 'success', 'Module added.');
    }

    /**
     * Extracts an 11-character YouTube video ID from various URL formats.
     */
    private static function extract_youtube_id(string $url): string {
        $url = trim($url);
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match)) {
            return $match[1];
        }
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }
        return '';
    }

    private static function do_add_lesson(): void {
        self::nonce('add_lesson');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $course = absint($_POST['course_id'] ?? 0);
        $module = absint($_POST['module_id'] ?? 0);
        $type   = sanitize_key($_POST['lesson_type'] ?? 'video');
        $title  = sanitize_text_field(wp_unslash($_POST['title'] ?? 'New lesson'));

        if (!in_array($type, ['video', 'pdf'], true)) {
            $type = 'video';
        }

        $id = wp_insert_post([
            'post_type'   => 'liva_lesson',
            'post_status' => 'publish',
            'post_title'  => $title
        ]);

        if ($id && !is_wp_error($id)) {
            update_post_meta($id, '_liva_module_id', $module);
            update_post_meta($id, '_liva_course_id', $course);
            update_post_meta($id, '_liva_type', $type);
            update_post_meta($id, '_liva_preview', !empty($_POST['preview']) ? 1 : 0);

            if ($type === 'video') {
                $raw_url = sanitize_text_field(wp_unslash($_POST['video_url'] ?? ''));
                $yt_id   = self::extract_youtube_id($raw_url);

                update_post_meta($id, '_liva_video_url', $raw_url);
                update_post_meta($id, '_liva_video_id', $yt_id);
                update_post_meta($id, '_liva_file_url', $raw_url);
            } else {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                if (!empty($_FILES['pdf_file']['name'])) {
                    $attach_id = media_handle_upload('pdf_file', $id);
                    if (!is_wp_error($attach_id)) {
                        update_post_meta($id, '_liva_file_id', $attach_id);
                        update_post_meta($id, '_liva_file_url', wp_get_attachment_url($attach_id));
                    }
                }
            }

            update_post_meta($course, '_liva_has_changes', 1);
            self::redirect(add_query_arg(['view' => 'course', 'id' => $course, 'tab' => 'curriculum'], Liva_Core::page_url('liva-courses')), 'success', 'Lesson added successfully.');
        }

        self::redirect(add_query_arg(['view' => 'course', 'id' => $course, 'tab' => 'curriculum'], Liva_Core::page_url('liva-courses')), 'error', 'Failed to add lesson.');
    }

    private static function do_update_lesson(): void {
        self::nonce('update_lesson');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $lesson_id = absint($_POST['lesson_id'] ?? 0);
        $course    = absint($_POST['course_id'] ?? 0);
        $module    = absint($_POST['module_id'] ?? 0);
        $type      = sanitize_key($_POST['lesson_type'] ?? 'video');
        $title     = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));

        if (!$lesson_id || get_post_type($lesson_id) !== 'liva_lesson' || empty($title)) {
            self::redirect(wp_get_referer() ?: Liva_Core::page_url('liva-courses'), 'error', 'Invalid lesson data.');
        }

        if (!in_array($type, ['video', 'pdf'], true)) {
            $type = 'video';
        }

        wp_update_post([
            'ID'         => $lesson_id,
            'post_title' => $title,
        ]);

        update_post_meta($lesson_id, '_liva_type', $type);
        if ($module) {
            update_post_meta($lesson_id, '_liva_module_id', $module);
        }
        if ($course) {
            update_post_meta($lesson_id, '_liva_course_id', $course);
        }

        update_post_meta($lesson_id, '_liva_preview', !empty($_POST['preview']) ? 1 : 0);

        if ($type === 'video') {
            $raw_url = sanitize_text_field(wp_unslash($_POST['video_url'] ?? ''));
            $yt_id   = self::extract_youtube_id($raw_url);

            update_post_meta($lesson_id, '_liva_video_url', $raw_url);
            update_post_meta($lesson_id, '_liva_video_id', $yt_id);
            update_post_meta($lesson_id, '_liva_file_url', $raw_url);
        } else {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            if (!empty($_FILES['pdf_file']['name'])) {
                $attach_id = media_handle_upload('pdf_file', $lesson_id);
                if (!is_wp_error($attach_id)) {
                    update_post_meta($lesson_id, '_liva_file_id', $attach_id);
                    update_post_meta($lesson_id, '_liva_file_url', wp_get_attachment_url($attach_id));
                }
            }
        }

        update_post_meta($course, '_liva_has_changes', 1);
        self::redirect(add_query_arg(['view' => 'course', 'id' => $course, 'tab' => 'curriculum'], Liva_Core::page_url('liva-courses')), 'success', 'Lesson updated successfully.');
    }

    private static function do_delete_lesson(): void {
        self::nonce('delete_lesson');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $lesson_id = absint($_POST['lesson_id'] ?? 0);
        $course_id = absint($_POST['course_id'] ?? 0);

        if ($lesson_id && get_post_type($lesson_id) === 'liva_lesson') {
            wp_delete_post($lesson_id, true);
        }

        update_post_meta($course_id, '_liva_has_changes', 1);
        self::redirect(add_query_arg(['view' => 'course', 'id' => $course_id, 'tab' => 'curriculum'], Liva_Core::page_url('liva-courses')), 'success', 'Lesson deleted.');
    }

    private static function do_update_course_settings(): void {
        self::nonce('update_course_settings');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $id = absint($_POST['course_id'] ?? 0);
        if (!$id || get_post_type($id) !== 'liva_course') {
            return;
        }

        $title      = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $level      = sanitize_text_field(wp_unslash($_POST['level'] ?? 'Basic'));
        $price      = (float)($_POST['price'] ?? 0);
        $visibility = sanitize_key($_POST['visibility'] ?? 'draft');
        if (!in_array($visibility, ['publish', 'draft'], true)) {
            $visibility = 'draft';
        }

        wp_update_post([
            'ID'          => $id,
            'post_title'  => $title,
            'post_status' => $visibility
        ]);

        update_post_meta($id, '_liva_level', $level);
        update_post_meta($id, '_liva_price', $price);

        if (!empty($_FILES['avatar_image']['name']) || !empty($_FILES['cover_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            if (!empty($_FILES['avatar_image']['name'])) {
                $avatar_id = media_handle_upload('avatar_image', $id);
                if (!is_wp_error($avatar_id)) {
                    set_post_thumbnail($id, $avatar_id);
                    update_post_meta($id, '_liva_avatar_id', $avatar_id);
                    update_post_meta($id, '_liva_avatar_url', wp_get_attachment_url($avatar_id));
                }
            }

            if (!empty($_FILES['cover_image']['name'])) {
                $cover_id = media_handle_upload('cover_image', $id);
                if (!is_wp_error($cover_id)) {
                    update_post_meta($id, '_liva_cover_image_id', $cover_id);
                    update_post_meta($id, '_liva_cover_image_url', wp_get_attachment_url($cover_id));
                }
            }
        }

        update_post_meta($id, '_liva_has_changes', 1);
        self::redirect(add_query_arg(['view' => 'course', 'id' => $id, 'tab' => 'settings'], Liva_Core::page_url('liva-courses')), 'success', 'Course settings saved.');
    }

    private static function do_archive_course(): void {
        self::nonce('archive_course');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $id = absint($_POST['course_id'] ?? 0);
        wp_update_post(['ID' => $id, 'post_status' => 'draft']);
        update_post_meta($id, '_liva_archived', 1);

        self::redirect(add_query_arg(['view' => 'course', 'id' => $id, 'tab' => 'settings'], Liva_Core::page_url('liva-courses')), 'success', 'Course archived from new enrollment.');
    }

    private static function do_delete_course(): void {
        self::nonce('delete_course');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $id = absint($_POST['course_id'] ?? 0);
        if ($id && get_post_type($id) === 'liva_course') {
            global $wpdb;

            $modules = get_posts([
                'post_type'      => 'liva_module',
                'posts_per_page' => -1,
                'meta_key'       => '_liva_course_id',
                'meta_value'     => $id,
                'fields'         => 'ids'
            ]);

            foreach ($modules as $mid) {
                $lessons = get_posts([
                    'post_type'      => 'liva_lesson',
                    'posts_per_page' => -1,
                    'meta_key'       => '_liva_module_id',
                    'meta_value'     => $mid,
                    'fields'         => 'ids'
                ]);
                foreach ($lessons as $lid) {
                    wp_delete_post($lid, true);
                }
                wp_delete_post($mid, true);
            }

            $wpdb->delete($wpdb->prefix . 'liva_enrollments', ['course_id' => $id], ['%d']);
            wp_delete_post($id, true);
        }

        self::redirect(Liva_Core::page_url('liva-courses'), 'success', 'Course permanently deleted.');
    }

    private static function do_add_question(): void {
        self::nonce('add_question');
        if (!current_user_can('liva_manage_tests') && !current_user_can('manage_options')) {
            return;
        }

        $total_questions = (int) wp_count_posts('liva_question')->publish;
        if ($total_questions >= 30) {
            self::redirect(Liva_Core::page_url('liva-placement-tests'), 'error', 'Question limit reached. A placement test can have at most 30 questions.');
        }

        $title   = sanitize_textarea_field(wp_unslash($_POST['question'] ?? 'Question'));
        $stage   = max(1, min(4, absint($_POST['stage'] ?? 1)));
        $options = [];

        foreach ((array)($_POST['options'] ?? []) as $v) {
            $options[] = sanitize_text_field(wp_unslash($v));
        }
        while (count($options) < 4) {
            $options[] = 'Option ' . chr(65 + count($options));
        }

        $points = isset($_POST['points']) && $_POST['points'] !== ''
            ? (float) $_POST['points']
            : ($stage === 1 ? 2 : ($stage === 2 ? 3 : 5));
        $id     = wp_insert_post([
            'post_type'   => 'liva_question',
            'post_status' => 'publish',
            'post_title'  => $title
        ]);

        update_post_meta($id, '_liva_stage', $stage);
        update_post_meta($id, '_liva_points', $points);
        update_post_meta($id, '_liva_options', $options);
        update_post_meta($id, '_liva_correct', max(0, min(3, absint($_POST['correct'] ?? 0))));

        self::redirect(Liva_Core::page_url('liva-placement-tests'), 'success', 'Question added.');
    }

    private static function do_update_question(): void {
        self::nonce('update_question');
        if (!current_user_can('liva_manage_tests') && !current_user_can('manage_options')) {
            return;
        }

        $id = absint($_POST['question_id'] ?? 0);
        if (!$id || get_post_type($id) !== 'liva_question') {
            return;
        }

        $title = sanitize_textarea_field(wp_unslash($_POST['question'] ?? ''));
        $stage = max(1, min(4, absint($_POST['stage'] ?? 1)));

        $options = [];
        foreach ((array) ($_POST['options'] ?? []) as $v) {
            $options[] = sanitize_text_field(wp_unslash($v));
        }
        while (count($options) < 4) {
            $options[] = 'Option ' . chr(65 + count($options));
        }

        $points = isset($_POST['points']) && $_POST['points'] !== ''
            ? (float) $_POST['points']
            : ($stage === 1 ? 2 : ($stage === 2 ? 3 : 5));

        wp_update_post([
            'ID'         => $id,
            'post_title' => $title,
        ]);

        update_post_meta($id, '_liva_stage', $stage);
        update_post_meta($id, '_liva_points', $points);
        update_post_meta($id, '_liva_options', $options);
        update_post_meta($id, '_liva_correct', max(0, min(3, absint($_POST['correct'] ?? 0))));

        self::redirect(Liva_Core::page_url('liva-placement-tests'), 'success', 'Question updated.');
    }

    private static function do_delete_question(): void {
        self::nonce('delete_question');
        if (!current_user_can('liva_manage_tests') && !current_user_can('manage_options')) {
            return;
        }

        $id = absint($_POST['question_id'] ?? 0);
        if ($id && get_post_type($id) === 'liva_question') {
            wp_delete_post($id, true);
        }

        self::redirect(Liva_Core::page_url('liva-placement-tests'), 'success', 'Question deleted.');
    }

    private static function do_save_scoring(): void {
        self::nonce('save_scoring');
        if (!current_user_can('liva_manage_tests') && !current_user_can('manage_options')) {
            return;
        }

        $b = max(0, min(98, absint($_POST['basic_max'] ?? 49)));
        $i = max($b + 1, min(99, absint($_POST['intermediate_max'] ?? 79)));

        update_option('liva_level_ranges', [
            'Basic'        => [0, $b],
            'Intermediate' => [$b + 1, $i],
            'Advanced'     => [$i + 1, 100]
        ]);

        self::redirect(add_query_arg('tab', 'scoring', Liva_Core::page_url('liva-placement-tests')), 'success', 'Score ranges saved without gaps.');
    }

    private static function do_enroll_student(): void {
        self::nonce('enroll_student');
        if (!current_user_can('liva_manage_students') && !current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'liva_enrollments';
        $uid   = absint($_POST['user_id'] ?? 0);
        $cid   = absint($_POST['course_id'] ?? 0);

        $wpdb->replace(
            $table,
            [
                'user_id'        => $uid,
                'course_id'      => $cid,
                'status'         => 'active',
                'access_type'    => sanitize_key($_POST['access_type'] ?? 'lifetime'),
                'payment_method' => sanitize_key($_POST['payment_method'] ?? 'manual'),
                'progress'       => 0,
                'created_at'     => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%d', '%s']
        );

        self::redirect(add_query_arg(['view' => 'user', 'id' => $uid], Liva_Core::page_url('liva-users')), 'success', 'Student enrolled.');
    }

    private static function do_add_user(): void {
        self::nonce('add_user');
        if (!current_user_can('liva_manage_students') && !current_user_can('manage_options')) {
            return;
        }

        $role = sanitize_key($_POST['role'] ?? 'liva_student');
        if ($role !== 'liva_student' && !current_user_can('liva_manage_admins') && !current_user_can('manage_options')) {
            $role = 'liva_student';
        }
        if (!in_array($role, ['liva_student', 'liva_admin', 'liva_super_admin'], true)) {
            $role = 'liva_student';
        }
        if ($role === 'liva_super_admin' && !current_user_can('liva_manage_admins') && !current_user_can('manage_options')) {
            $role = 'liva_student';
        }

        $email    = sanitize_email($_POST['email'] ?? '');
        $username = sanitize_user($_POST['username'] ?? '', true);
        $password = (string)($_POST['password'] ?? wp_generate_password(16, true));

        if (!$email || !$username || email_exists($email) || username_exists($username)) {
            self::redirect(Liva_Core::page_url('liva-users'), 'error', 'Use a unique username and email.');
        }

        $uid = wp_create_user($username, $password, $email);
        if (is_wp_error($uid)) {
            self::redirect(Liva_Core::page_url('liva-users'), 'error', $uid->get_error_message());
        }

        wp_update_user([
            'ID'           => $uid,
            'first_name'   => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name'    => sanitize_text_field($_POST['last_name'] ?? ''),
            'display_name' => trim(sanitize_text_field(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''))) ?: $username
        ]);

        (new WP_User($uid))->set_role($role);
        self::redirect(Liva_Core::page_url('liva-users'), 'success', 'User created.');
    }

    private static function do_placement_submit(): void {
        self::nonce('placement_submit');
        global $wpdb;

        $questions = get_posts([
            'post_type'      => 'liva_question',
            'post_status'    => 'publish',
            'posts_per_page' => 30,
            'orderby'        => 'ID',
            'order'          => 'ASC'
        ]);

        $answers     = (array)($_POST['answer'] ?? []);
        $score       = 0;
        $correct     = 0;
        $stageScores = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

        foreach ($questions as $q) {
            $qid   = $q->ID;
            $given = isset($answers[$qid]) ? absint($answers[$qid]) : -1;
            $right = (int)get_post_meta($qid, '_liva_correct', true);
            $pts   = (int)get_post_meta($qid, '_liva_points', true);
            $stage = (int)get_post_meta($qid, '_liva_stage', true);

            if ($given === $right) {
                $score += $pts;
                $correct++;
                $stageScores[$stage] += $pts;
            }
        }

        $ranges = get_option('liva_level_ranges', [
            'Basic'        => [0, 49],
            'Intermediate' => [50, 79],
            'Advanced'     => [80, 100]
        ]);

        $level = 'Basic';
        foreach ($ranges as $name => $r) {
            if ($score >= $r[0] && $score <= $r[1]) {
                $level = $name;
                break;
            }
        }

        $table = $wpdb->prefix . 'liva_test_submissions';
        $wpdb->insert(
            $table,
            [
                'user_id'         => get_current_user_id() ?: null,
                'score'           => $score,
                'level'           => $level,
                'correct_answers' => $correct,
                'time_taken'      => 15,
                'answers'         => wp_json_encode($answers),
                'stage_scores'    => wp_json_encode($stageScores),
                'created_at'      => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s']
        );

        self::redirect(add_query_arg('submission', $wpdb->insert_id, Liva_Core::page_url('placement-test-result')));
    }

    /**
     * Toggles whether a lesson is hidden from students.
     */
    private static function do_toggle_lesson_visibility(): void {
        self::nonce('toggle_lesson_visibility');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $lesson_id = absint($_POST['lesson_id'] ?? 0);
        $course_id = absint($_POST['course_id'] ?? 0);

        if ($lesson_id && get_post_type($lesson_id) === 'liva_lesson') {
            $current   = (int) get_post_meta($lesson_id, '_liva_hidden', true);
            $new_state = $current ? 0 : 1;
            update_post_meta($lesson_id, '_liva_hidden', $new_state);

            $msg = $new_state ? 'Lesson hidden from students.' : 'Lesson is now visible to students.';
            self::redirect(
                add_query_arg(['view' => 'course', 'id' => $course_id, 'tab' => 'curriculum'], Liva_Core::page_url('liva-courses')),
                'success',
                $msg
            );
        }

        self::redirect(wp_get_referer() ?: Liva_Core::page_url('liva-courses'), 'error', 'Failed to update lesson visibility.');
    }

    /**
     * Saves the new chronological order of lessons inside a module.
     */
    private static function do_reorder_lessons(): void {
        self::nonce('reorder_lessons');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $course_id = absint($_POST['course_id'] ?? 0);
        $order     = (array) ($_POST['lesson_order'] ?? []);

        if (!empty($order)) {
            foreach ($order as $index => $lesson_id) {
                $lid = absint($lesson_id);
                if ($lid && get_post_type($lid) === 'liva_lesson') {
                    update_post_meta($lid, '_liva_position', $index + 1);
                }
            }

            update_post_meta($course_id, '_liva_has_changes', 1);
            self::redirect(
                add_query_arg(['view' => 'course', 'id' => $course_id, 'tab' => 'curriculum'], Liva_Core::page_url('liva-courses')),
                'success',
                'Lesson order updated successfully.'
            );
        }

        self::redirect(wp_get_referer() ?: Liva_Core::page_url('liva-courses'), 'error', 'No lessons to reorder.');
    }
    private static function do_delete_module(): void {
        self::nonce('delete_module');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $module_id = absint($_POST['module_id'] ?? 0);
        $course_id = absint($_POST['course_id'] ?? 0);

        if ($module_id && get_post_type($module_id) === 'liva_module') {
            // Delete all child lessons inside this module first
            $lessons = get_posts([
                'post_type'      => 'liva_lesson',
                'posts_per_page' => -1,
                'meta_key'       => '_liva_module_id',
                'meta_value'     => $module_id,
                'fields'         => 'ids'
            ]);

            foreach ($lessons as $lid) {
                wp_delete_post($lid, true);
            }

            // Delete the module itself
            wp_delete_post($module_id, true);

            update_post_meta($course_id, '_liva_has_changes', 1);
            self::redirect(
                add_query_arg(['view' => 'course', 'id' => $course_id, 'tab' => 'curriculum'], Liva_Core::page_url('liva-courses')),
                'success',
                'Module and its lessons were permanently deleted.'
            );
        }

        self::redirect(wp_get_referer() ?: Liva_Core::page_url('liva-courses'), 'error', 'Failed to delete module.');
    }
    /**
     * Updates an existing module's title, description, and position.
     */
    private static function do_update_module(): void {
        self::nonce('update_module');
        if (!current_user_can('liva_manage_courses') && !current_user_can('manage_options')) {
            return;
        }

        $module_id = absint($_POST['module_id'] ?? 0);
        $course_id = absint($_POST['course_id'] ?? 0);
        $title     = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $desc      = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $position  = absint($_POST['position'] ?? 1);

        if ($module_id && get_post_type($module_id) === 'liva_module' && !empty($title)) {
            wp_update_post([
                'ID'           => $module_id,
                'post_title'   => $title,
                'post_content' => $desc,
            ]);

            update_post_meta($module_id, '_liva_position', $position);
            update_post_meta($course_id, '_liva_has_changes', 1);

            self::redirect(
                add_query_arg(['view' => 'course', 'id' => $course_id, 'tab' => 'curriculum'], Liva_Core::page_url('liva-courses')),
                'success',
                'Module updated successfully.'
            );
        }

        self::redirect(wp_get_referer() ?: Liva_Core::page_url('liva-courses'), 'error', 'Failed to update module.');
    }
}