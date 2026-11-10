<?php
/**
 * Plugin Name: BB Reviews
 * Description: Bewertungen von Google, OneDoc, Sanasearch und anderen Plattformen von Hand anlegen und per Shortcode [bb_reviews] als Karussell oder Raster zeigen. Lädt nichts von fremden Servern.
 * Version: 0.2.0
 * Update URI: https://github.com/bueroblanko/bb-reviews
 * Plugin URI: https://github.com/bueroblanko/bb-reviews
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Büro Blanko
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) { exit; }

final class BB_Reviews {
    const VERSION = '0.2.0';
    const TYPE = 'bb_review';
    const NONCE = 'bb_reviews_meta';

    public static function boot() {
        add_action('init', [__CLASS__, 'register']);
        add_action('add_meta_boxes', [__CLASS__, 'boxes']);
        add_action('save_post_' . self::TYPE, [__CLASS__, 'save'], 10, 2);
        add_filter('enter_title_here', [__CLASS__, 'title_placeholder'], 10, 2);
        add_filter('manage_' . self::TYPE . '_posts_columns', [__CLASS__, 'columns']);
        add_action('manage_' . self::TYPE . '_posts_custom_column', [__CLASS__, 'column'], 10, 2);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets']);
        add_shortcode('bb_reviews', [__CLASS__, 'shortcode']);
    }

    /** Plattformen. Erweiterbar über den Filter bb_reviews_sources. */
    public static function sources() {
        return apply_filters('bb_reviews_sources', [
            'google' => 'Google',
            'onedoc' => 'OneDoc',
            'sanasearch' => 'Sanasearch',
            'jameda' => 'jameda',
            'doctolib' => 'Doctolib',
            'trustpilot' => 'Trustpilot',
            'provenexpert' => 'ProvenExpert',
            'facebook' => 'Facebook',
            'other' => 'Andere Quelle',
        ]);
    }

    public static function register() {
        register_post_type(self::TYPE, [
            'labels' => [
                'name' => 'Bewertungen',
                'singular_name' => 'Bewertung',
                'menu_name' => 'Bewertungen',
                'add_new' => 'Bewertung hinzufügen',
                'add_new_item' => 'Bewertung hinzufügen',
                'edit_item' => 'Bewertung bearbeiten',
                'new_item' => 'Neue Bewertung',
                'search_items' => 'Bewertungen durchsuchen',
                'not_found' => 'Keine Bewertungen gefunden.',
                'not_found_in_trash' => 'Keine Bewertungen im Papierkorb.',
                'all_items' => 'Alle Bewertungen',
            ],
            // Keine Einzelseiten und kein Archiv: Bewertungen erscheinen nur über den Shortcode.
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => false,
            'menu_icon' => 'dashicons-star-filled',
            'menu_position' => 26,
            'supports' => ['title', 'page-attributes'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'has_archive' => false,
            'rewrite' => false,
            'exclude_from_search' => true,
        ]);
    }

    public static function title_placeholder($text, $post) {
        return ($post && $post->post_type === self::TYPE) ? 'Name, wie er bei der Bewertung steht' : $text;
    }

    public static function boxes() {
        add_meta_box('bb-reviews-data', 'Bewertung', [__CLASS__, 'box'], self::TYPE, 'normal', 'high');
        add_meta_box('bb-reviews-help', 'So erscheint es auf der Website', [__CLASS__, 'help'], self::TYPE, 'side', 'low');
    }

    public static function meta($id) {
        $rating = get_post_meta($id, '_bbr_rating', true);
        return [
            'headline' => (string) get_post_meta($id, '_bbr_headline', true),
            'text' => (string) get_post_meta($id, '_bbr_text', true),
            // Leer = neuer Eintrag (Vorschlag 5), 0 = bewusst keine Sterne.
            'rating' => $rating === '' ? 5 : max(0, min(5, (int) $rating)),
            'source' => (string) (get_post_meta($id, '_bbr_source', true) ?: 'google'),
            'source_label' => (string) get_post_meta($id, '_bbr_source_label', true),
            'date' => (string) get_post_meta($id, '_bbr_date', true),
            'url' => (string) get_post_meta($id, '_bbr_url', true),
        ];
    }

    public static function box($post) {
        $m = self::meta($post->ID);
        wp_nonce_field(self::NONCE, self::NONCE . '_field');
        echo '<style>.bbr-f{margin:0 0 16px}.bbr-f label{display:block;font-weight:600;margin-bottom:4px}.bbr-f small{display:block;color:#646970;margin-top:4px}.bbr-row{display:flex;gap:20px;flex-wrap:wrap}.bbr-row .bbr-f{flex:1 1 180px}</style>';
        echo '<div class="bbr-f"><label for="bbr-headline">Überschrift (optional)</label><input type="text" id="bbr-headline" name="bbr[headline]" class="widefat" value="' . esc_attr($m['headline']) . '"><small>Nur ausfüllen, wenn die Bewertung auf der Plattform eine eigene Überschrift hat.</small></div>';
        echo '<div class="bbr-f"><label for="bbr-text">Text der Bewertung</label>';
        echo '<textarea id="bbr-text" name="bbr[text]" rows="7" class="widefat">' . esc_textarea($m['text']) . '</textarea>';
        echo '<small>Den Text unverändert aus der Plattform hineinkopieren. Absätze bleiben erhalten.</small></div>';
        echo '<div class="bbr-row">';
        echo '<div class="bbr-f"><label for="bbr-rating">Sterne</label><select id="bbr-rating" name="bbr[rating]">';
        for ($i = 5; $i >= 1; $i--) { echo '<option value="' . $i . '"' . selected($m['rating'], $i, false) . '>' . $i . ' von 5</option>'; }
        echo '<option value="0"' . selected($m['rating'], 0, false) . '>Keine Sterne zeigen</option></select></div>';
        echo '<div class="bbr-f"><label for="bbr-source">Plattform</label><select id="bbr-source" name="bbr[source]">';
        foreach (self::sources() as $key => $label) { echo '<option value="' . esc_attr($key) . '"' . selected($m['source'], $key, false) . '>' . esc_html($label) . '</option>'; }
        echo '</select></div>';
        echo '<div class="bbr-f"><label for="bbr-date">Datum der Bewertung</label><input type="date" id="bbr-date" name="bbr[date]" value="' . esc_attr($m['date']) . '"><small>Leer lassen, wenn kein Datum erscheinen soll.</small></div>';
        echo '</div>';
        echo '<div class="bbr-f"><label for="bbr-source-label">Name der Plattform, falls „Andere Quelle“</label><input type="text" id="bbr-source-label" name="bbr[source_label]" class="regular-text" value="' . esc_attr($m['source_label']) . '"></div>';
        echo '<div class="bbr-f"><label for="bbr-url">Link zur Bewertung oder zum Profil (optional)</label><input type="url" id="bbr-url" name="bbr[url]" class="widefat" placeholder="https://" value="' . esc_attr($m['url']) . '"><small>Mit Link wird der Plattformname unter der Bewertung anklickbar und öffnet in einem neuen Tab.</small></div>';
    }

    public static function help() {
        echo '<p>Bewertungen erscheinen überall, wo der Shortcode steht:</p><p><code>[bb_reviews]</code></p>';
        echo '<p>Reihenfolge: neueste zuerst nach „Datum der Bewertung“. Eine feste Reihenfolge geht über das Feld „Reihenfolge“ und <code>sortierung="manuell"</code>.</p>';
        echo '<p>Nur veröffentlichte Bewertungen werden gezeigt.</p>';
    }

    public static function save($id, $post) {
        if (!isset($_POST[self::NONCE . '_field']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE . '_field'])), self::NONCE)) { return; }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        if (!current_user_can('edit_post', $id)) { return; }
        $in = isset($_POST['bbr']) && is_array($_POST['bbr']) ? wp_unslash($_POST['bbr']) : [];
        self::store($id, $in);
    }

    /** Speichert geprüfte Werte. Auch für Importe nutzbar. */
    public static function store($id, array $in) {
        $sources = self::sources();
        $source = isset($in['source']) && isset($sources[$in['source']]) ? $in['source'] : 'other';
        $date = isset($in['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $in['date']) ? (string) $in['date'] : '';
        update_post_meta($id, '_bbr_headline', isset($in['headline']) ? sanitize_text_field($in['headline']) : '');
        update_post_meta($id, '_bbr_text', isset($in['text']) ? sanitize_textarea_field($in['text']) : '');
        update_post_meta($id, '_bbr_rating', max(0, min(5, (int) ($in['rating'] ?? 5))));
        update_post_meta($id, '_bbr_source', $source);
        update_post_meta($id, '_bbr_source_label', isset($in['source_label']) ? sanitize_text_field($in['source_label']) : '');
        update_post_meta($id, '_bbr_date', $date);
        // Sortierschlüssel: ohne Datum zählt das Anlagedatum des Eintrags.
        $created = substr((string) get_post_field('post_date', $id), 0, 10);
        update_post_meta($id, '_bbr_sort', $date !== '' ? $date : ($created !== '' ? $created : gmdate('Y-m-d')));
        update_post_meta($id, '_bbr_url', isset($in['url']) ? esc_url_raw($in['url']) : '');
    }

    public static function columns($cols) {
        $out = [];
        foreach ($cols as $key => $label) {
            if ($key === 'date') { continue; }
            $out[$key] = $key === 'title' ? 'Name' : $label;
            if ($key === 'title') { $out['bbr_rating'] = 'Sterne'; $out['bbr_source'] = 'Plattform'; $out['bbr_date'] = 'Datum der Bewertung'; $out['bbr_text'] = 'Text'; }
        }
        return $out;
    }

    public static function column($col, $id) {
        $m = self::meta($id);
        if ($col === 'bbr_rating') { echo $m['rating'] ? esc_html(str_repeat('★', $m['rating']) . str_repeat('☆', 5 - $m['rating'])) : '–'; }
        if ($col === 'bbr_source') { echo esc_html(self::source_name($m)); }
        if ($col === 'bbr_date') { echo $m['date'] ? esc_html(date_i18n(get_option('date_format'), strtotime($m['date']))) : '–'; }
        if ($col === 'bbr_text') { echo esc_html(wp_trim_words($m['text'], 16, ' …')); }
    }

    private static function source_name(array $m) {
        $sources = self::sources();
        if ($m['source'] === 'other') { return $m['source_label']; }
        return $sources[$m['source']] ?? '';
    }

    public static function assets() {
        $url = plugin_dir_url(__FILE__) . 'assets/';
        wp_register_style('bb-reviews', $url . 'bb-reviews.css', [], self::VERSION);
        wp_register_script('bb-reviews', $url . 'bb-reviews.js', [], self::VERSION, true);
    }

    private static function stars($rating) {
        $out = '<span class="bbr-stars" role="img" aria-label="' . esc_attr($rating . ' von 5 Sternen') . '">';
        for ($i = 1; $i <= 5; $i++) {
            $out .= '<svg class="bbr-star' . ($i <= $rating ? ' is-on' : '') . '" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M12 2.5l2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.5l-5.9 3.2 1.2-6.6L2.5 9.5l6.6-.9z"/></svg>';
        }
        return $out . '</span>';
    }

    private static function yes($value) { return in_array(strtolower((string) $value), ['ja', 'yes', '1', 'true', 'on'], true); }

    /**
     * [bb_reviews layout="karussell|raster" sichtbar="3" anzahl="12" quelle="google,onedoc" sortierung="datum|manuell|zufall"
     *             autoplay="5" pfeile="ja" sterne="ja" datum="ja" zeilen="6" titel=""]
     */
    public static function shortcode($atts) {
        $a = shortcode_atts([
            'layout' => 'karussell', 'sichtbar' => '3', 'anzahl' => '12', 'quelle' => '', 'sortierung' => 'datum',
            'autoplay' => '5', 'pfeile' => 'ja', 'sterne' => 'ja', 'datum' => 'ja', 'zeilen' => '6', 'titel' => '',
        ], is_array($atts) ? $atts : [], 'bb_reviews');

        $query = ['post_type' => self::TYPE, 'post_status' => 'publish', 'posts_per_page' => max(1, min(100, (int) $a['anzahl'])), 'no_found_rows' => true];
        if ($a['sortierung'] === 'manuell') { $query['orderby'] = ['menu_order' => 'ASC', 'date' => 'DESC']; }
        elseif ($a['sortierung'] === 'zufall') { $query['orderby'] = 'rand'; }
        else { $query['meta_key'] = '_bbr_sort'; $query['orderby'] = ['meta_value' => 'DESC', 'date' => 'DESC']; }
        $wanted = array_filter(array_map('sanitize_key', explode(',', (string) $a['quelle'])));
        if ($wanted) { $query['meta_query'] = [['key' => '_bbr_source', 'value' => $wanted, 'compare' => 'IN']]; }

        $posts = get_posts($query);
        if (!$posts) { return current_user_can('edit_posts') ? '<p class="bbr-empty">BB Reviews: Noch keine veröffentlichten Bewertungen.</p>' : ''; }

        wp_enqueue_style('bb-reviews');
        wp_enqueue_script('bb-reviews');

        $carousel = $a['layout'] !== 'raster';
        $cols = max(1, min(4, (int) $a['sichtbar']));
        $lines = max(0, min(20, (int) $a['zeilen']));
        $classes = 'bbr bbr--' . ($carousel ? 'carousel' : 'grid');
        $html = '<div class="' . esc_attr($classes) . '" style="--bbr-cols:' . $cols . ';--bbr-lines:' . ($lines ?: 'none') . '"' . ($carousel ? ' data-autoplay="' . max(0, min(60, (int) $a['autoplay'])) . '"' : '') . '>';
        if ($a['titel'] !== '') { $html .= '<p class="bbr-title">' . esc_html($a['titel']) . '</p>'; }
        if ($carousel && self::yes($a['pfeile'])) {
            $html .= '<div class="bbr-nav"><button type="button" class="bbr-prev" aria-label="Vorherige Bewertungen">‹</button><button type="button" class="bbr-next" aria-label="Nächste Bewertungen">›</button></div>';
        }
        $html .= '<ul class="bbr-track"' . ($carousel ? ' tabindex="0" aria-label="Bewertungen"' : '') . '>';
        foreach ($posts as $post) {
            $m = self::meta($post->ID);
            $source = self::source_name($m);
            $html .= '<li class="bbr-card bbr-source-' . esc_attr($m['source']) . '"><div class="bbr-card-inner">';
            if (self::yes($a['sterne']) && $m['rating'] > 0) { $html .= self::stars($m['rating']); }
            if ($m['headline'] !== '') { $html .= '<p class="bbr-headline">' . esc_html($m['headline']) . '</p>'; }
            if ($m['text'] !== '') {
                $html .= '<div class="bbr-text' . ($lines ? ' is-clamped' : '') . '">' . wpautop(esc_html($m['text'])) . '</div>';
                if ($lines) { $html .= '<button type="button" class="bbr-more" hidden aria-expanded="false">Mehr lesen</button>'; }
            }
            $html .= '<p class="bbr-name">' . esc_html(get_the_title($post)) . '</p>';
            $meta = [];
            if ($source !== '') {
                $label = 'Bewertung auf ' . $source;
                $meta[] = $m['url'] !== '' ? '<a class="bbr-source" href="' . esc_url($m['url']) . '" target="_blank" rel="noopener nofollow">' . esc_html($label) . '</a>' : '<span class="bbr-source">' . esc_html($label) . '</span>';
            }
            if (self::yes($a['datum']) && $m['date'] !== '') { $meta[] = '<time class="bbr-date" datetime="' . esc_attr($m['date']) . '">' . esc_html(date_i18n(get_option('date_format'), strtotime($m['date']))) . '</time>'; }
            if ($meta) { $html .= '<p class="bbr-meta">' . implode('<span class="bbr-sep" aria-hidden="true"> · </span>', $meta) . '</p>'; }
            $html .= '</div></li>';
        }
        return $html . '</ul></div>';
    }
}

BB_Reviews::boot();

// Updates über öffentliche GitHub-Releases, ohne Zugangsdaten auf der Website.
require_once __DIR__ . '/includes/updater.php';
