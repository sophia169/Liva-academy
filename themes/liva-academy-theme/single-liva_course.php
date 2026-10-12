<?php
get_header();

while (have_posts()) : the_post();
    $course_id   = get_the_ID();
    $level       = get_post_meta($course_id, '_liva_level', true) ?: 'Intermediate';
    $price       = (float)(get_post_meta($course_id, '_liva_price', true) ?: 600000);
    $price_label = $price > 0 ? '$' . number_format($price, 0) : 'Free';

    $cover_url = get_post_meta($course_id, '_liva_cover_image_url', true);
    if (!$cover_url && has_post_thumbnail()) {
        $cover_url = get_the_post_thumbnail_url($course_id, 'full');
    }
    if (!$cover_url) {
        $cover_url = get_template_directory_uri() . '/assets/img/course-placeholder.png';
    }

    $modules = get_posts([
        'post_type'      => 'liva_module',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [['key' => '_liva_course_id', 'value' => $course_id]],
        'meta_key'       => '_liva_position',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC'
    ]);

    $can_edit = current_user_can('liva_manage_courses') || current_user_can('manage_options');
?>

<div class="liva-course-details-wrap">
    <nav class="liva-details-breadcrumbs">
        <a href="<?php echo esc_url(liva_page_url('my-learning', home_url('/my-learning/'))); ?>">Courses</a>
        <span>&gt;</span>
        <span class="current"><?php the_title(); ?></span>
    </nav>

    <div class="liva-details-grid">
        <div class="liva-details-main">
            <div class="liva-details-cover">
                <img src="<?php echo esc_url($cover_url); ?>" alt="<?php the_title_attribute(); ?>">
            </div>

            <h1 class="liva-details-title"><?php the_title(); ?></h1>
            <div class="liva-details-meta-row">
                <span class="liva-stars">★ 4.8 <small>(278 reviews)</small></span>
                <span class="liva-dot-sep">•</span>
                <span>1,850 students</span>
                <span class="liva-dot-sep">•</span>
                <span>24 hours</span>
                <span class="liva-pill liva-pill-level"><?php echo esc_html($level); ?></span>
            </div>

            <div class="liva-details-desc">
                <?php the_content(); ?>
            </div>

            <div class="liva-curriculum-wrap">
                <div class="liva-curriculum-header">
                    <div>
                        <h2>Course content</h2>
                        <p><?php echo count($modules); ?> sections</p>
                    </div>
                    <button type="button" class="liva-expand-all" id="liva-toggle-all-sections">Expand all sections</button>
                </div>

                <div class="liva-accordion-list">
                    <?php if (empty($modules)) : ?>
                        <div class="liva-accordion-item open">
                            <div class="liva-accordion-title">
                                <span><span class="liva-chevron"></span> Module 1</span>
                                <small>0 lectures</small>
                            </div>
                            <div class="liva-accordion-body">
                                <p style="padding: 14px 0; color: #687384; font-size: 13.5px; margin: 0;">No lectures in this module yet.</p>
                            </div>
                        </div>
                    <?php else : ?>
                        <?php foreach ($modules as $m_idx => $mod) :
                            $raw_lessons = get_posts([
                                'post_type'      => 'liva_lesson',
                                'post_status'    => 'publish',
                                'posts_per_page' => -1,
                                'meta_key'       => '_liva_module_id',
                                'meta_value'     => $mod->ID,
                            ]);

                            // Sort lessons by custom position
                            usort($raw_lessons, function($a, $b) {
                                $posA = get_post_meta($a->ID, '_liva_position', true);
                                $posB = get_post_meta($b->ID, '_liva_position', true);
                                $posA = ($posA !== '') ? (int) $posA : $a->ID;
                                $posB = ($posB !== '') ? (int) $posB : $b->ID;
                                return $posA <=> $posB;
                            });

                            // Filter: Admins see all lessons; students only see visible ones
                            $lessons = array_values(array_filter($raw_lessons, function($l) use ($can_edit) {
                                $is_hidden = (int) get_post_meta($l->ID, '_liva_hidden', true) === 1;
                                return $can_edit || !$is_hidden;
                            }));
                        ?>
                            <div class="liva-accordion-item <?php echo $m_idx === 0 ? 'open' : ''; ?>">
                                <div class="liva-accordion-title">
                                    <span><span class="liva-chevron"></span> <?php echo esc_html($mod->post_title); ?></span>
                                    <small><?php echo count($lessons); ?> lectures</small>
                                </div>
                                <div class="liva-accordion-body">
                                    <?php foreach ($lessons as $lesson) : 
                                        $is_hidden       = (int) get_post_meta($lesson->ID, '_liva_hidden', true) === 1;
                                        $type            = get_post_meta($lesson->ID, '_liva_type', true) ?: 'video';
                                        $preview_allowed = get_post_meta($lesson->ID, '_liva_preview', true);
                                        $yt_id           = get_post_meta($lesson->ID, '_liva_video_id', true);
                                        $video_url       = get_post_meta($lesson->ID, '_liva_video_url', true) ?: get_post_meta($lesson->ID, '_liva_file_url', true);
                                        $file_url        = get_post_meta($lesson->ID, '_liva_file_url', true);
                                        $duration        = get_post_meta($lesson->ID, '_liva_duration', true) ?: '10:00';
                                        $is_pdf          = ($type === 'pdf' || preg_match('/\.pdf$/i', $lesson->post_title));

                                        // Fallback if file URL is stored by attachment ID
                                        if ($is_pdf && !$file_url) {
                                            $file_id = get_post_meta($lesson->ID, '_liva_file_id', true);
                                            if ($file_id) {
                                                $file_url = wp_get_attachment_url($file_id);
                                            }
                                        }
                                    ?>
                                        <div class="liva-lecture-row <?php echo ($is_hidden && $can_edit) ? 'liva-lesson-admin-hidden' : ''; ?>">
                                            <div class="liva-lecture-left">
                                                <span class="liva-lecture-icon">
                                                    <?php 
                                                    if ($is_pdf) {
                                                        echo function_exists('liva_icon') && liva_icon('order_history') ? liva_icon('order_history') : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>';
                                                    } else {
                                                        echo function_exists('liva_icon') && liva_icon('video_2') ? liva_icon('video_2') : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18"></rect><polygon points="10 8 16 12 10 16 10 8"></polygon></svg>';
                                                    }
                                                    ?>
                                                </span>
                                                <span><?php echo esc_html($lesson->post_title); ?></span>
                                                <?php if ($is_hidden && $can_edit) : ?>
                                                    <span class="liva-pill liva-pill-muted liva-pill-admin-hidden">Hidden</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="liva-lecture-meta">
                                                <?php if ($is_pdf) : ?>
                                                    <?php if ($preview_allowed && $file_url) : ?>
                                                        <a href="<?php echo esc_url($file_url); ?>" 
                                                            download 
                                                            target="_blank" 
                                                            class="liva-preview-badge liva-download-badge">
                                                            <?php echo function_exists('liva_icon') && liva_icon('download') ? liva_icon('download') : '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>'; ?>
                                                            <span>Download</span>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else : ?>
                                                    <?php if ($preview_allowed && ($yt_id || $video_url)) : ?>
                                                        <button type="button" 
                                                                class="liva-preview-badge liva-preview-trigger" 
                                                                data-video="<?php echo esc_attr($yt_id ?: $video_url); ?>" 
                                                                data-title="<?php echo esc_attr($lesson->post_title); ?>">
                                                            <?php echo function_exists('liva_icon') && liva_icon('preview') ? liva_icon('preview') : '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>'; ?>
                                                            <span>Preview</span>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <span><?php echo esc_html($duration); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <aside class="liva-details-sidebar">
            <div class="liva-pricing-card">
                <div class="liva-pricing-figure"><?php echo esc_html($price_label); ?></div>
                <button type="button" class="liva-btn liva-btn-primary liva-btn-block">Add to cart</button>
                <button type="button" class="liva-btn liva-btn-outline liva-btn-block">Buy now</button>

                <ul class="liva-feature-list">
                    <li>
                        <span class="liva-feature-icon">∞</span>
                        <span>Lifetime access</span>
                    </li>
                    <li>
                        <span class="liva-feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#29779b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><line x1="7" y1="9" x2="17" y2="9"></line><line x1="7" y1="13" x2="17" y2="13"></line></svg>
                        </span>
                        <span>Certificate included</span>
                    </li>
                    <li>
                        <span class="liva-feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#29779b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        </span>
                        <span>Downloadable docs</span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.liva-accordion-title').forEach(function(el) {
        el.addEventListener('click', function() {
            var item = this.parentElement;
            item.classList.toggle('open');
        });
    });

    var expandBtn = document.getElementById('liva-toggle-all-sections');
    if (expandBtn) {
        expandBtn.addEventListener('click', function() {
            var items = document.querySelectorAll('.liva-accordion-item');
            var shouldOpen = !items[0].classList.contains('open');
            items.forEach(function(item) {
                if (shouldOpen) {
                    item.classList.add('open');
                } else {
                    item.classList.remove('open');
                }
            });
            this.textContent = shouldOpen ? 'Collapse all sections' : 'Expand all sections';
        });
    }
});
</script>

<?php
endwhile;
?>

<!-- Video Preview Modal with Shields -->
<div class="liva-modal-backdrop" id="liva-course-preview-modal" hidden>
    <div class="liva-modal liva-modal-video">
        <div class="liva-modal-head">
            <h3 id="liva-preview-modal-title" style="margin:0; font-size:18px; font-weight:700;">Lesson preview</h3>
            <button type="button" class="liva-modal-close" data-liva-modal-close aria-label="Close">×</button>
        </div>
        <div class="liva-video-embed-container" id="liva-video-container">
            <div class="liva-yt-shield-top"></div>
            <div class="liva-yt-shield-bottom"></div>

            <iframe id="liva-preview-iframe" 
                    src="" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
            </iframe>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal     = document.getElementById('liva-course-preview-modal');
    var iframe    = document.getElementById('liva-preview-iframe');
    var titleEl   = document.getElementById('liva-preview-modal-title');
    var container = document.getElementById('liva-video-container');

    if (container) {
        container.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
    }

    function getYouTubeId(url) {
        if (!url) return '';
        var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        var match  = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : url;
    }

    function closeModal() {
        if (!modal) return;
        modal.setAttribute('hidden', '');
        modal.style.display = 'none';
        if (iframe) iframe.src = '';
        document.body.classList.remove('liva-modal-open');
    }

    document.querySelectorAll('.liva-preview-trigger').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var rawVideo = this.getAttribute('data-video');
            var title    = this.getAttribute('data-title');
            var videoId  = getYouTubeId(rawVideo);

            if (!videoId) return;

            if (titleEl) titleEl.textContent = title;
            if (iframe) {
                iframe.src = 'https://www.youtube-nocookie.com/embed/' + videoId + '?autoplay=1&rel=0&modestbranding=1&iv_load_policy=3';
            }

            modal.removeAttribute('hidden');
            modal.style.display = 'grid';
            document.body.classList.add('liva-modal-open');
        });
    });

    modal.querySelectorAll('[data-liva-modal-close]').forEach(function(btn) {
        btn.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.hasAttribute('hidden')) {
            closeModal();
        }
    });
});
</script>

<?php
get_footer();