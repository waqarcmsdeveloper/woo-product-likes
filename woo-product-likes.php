<?php
/**
 * Plugin Name: WooCommerce Product Like Button
 * Description: Adds a toggled like/dislike button with count to WooCommerce product pages using a shortcode.
 * Version: 2.0
 * Author: reignwebexperts
 */

if (!defined('ABSPATH')) exit;

// Shortcode to display like/dislike buttons
add_shortcode('product_like_button', 'wpl_product_like_button');
function wpl_product_like_button() {
    global $product;
    if (!$product || !is_a($product, 'WC_Product')) {
        $product = wc_get_product(get_the_ID());
    }
    if (!$product) return '';

    $product_id = $product->get_id();
    $likes = (int) get_post_meta($product_id, '_product_likes', true);
    $dislikes = (int) get_post_meta($product_id, '_product_dislikes', true);

    ob_start(); ?>
    <div id="wpl-like-wrapper">
        <button id="wpl-heart-button" data-product-id="<?php echo esc_attr($product_id); ?>">
            <span class="heart-icon">🖤

</span>
        </button>
        <div id="wpl-action-buttons" style="display:none; margin-top:8px;">
            <button class="wpl-action-btn" data-action="like">👍</button>
            <button class="wpl-action-btn" data-action="dislike">👎</button>
        </div>
        <div style="margin-top:8px;">
            <span style="margin-right: 15px;">👍 <strong id="wpl-like-count"><?php echo esc_html($likes); ?></strong></span>
            <span>👎 <strong id="wpl-dislike-count"><?php echo esc_html($dislikes); ?></strong></span>
        </div>
        <div id="wpl-feedback-message" style="display:none; color: green; font-size: 14px; margin-top: 5px;"></div>
    </div>
    <?php
    return ob_get_clean();
}

// AJAX handler
add_action('wp_ajax_wpl_like_toggle', 'wpl_handle_like_toggle');
add_action('wp_ajax_nopriv_wpl_like_toggle', 'wpl_handle_like_toggle');

function wpl_handle_like_toggle() {
    check_ajax_referer('wpl_nonce', 'nonce');

    $product_id = intval($_POST['product_id']);
    $action_type = sanitize_text_field($_POST['like_action']);
    $likes = (int) get_post_meta($product_id, '_product_likes', true);
    $dislikes = (int) get_post_meta($product_id, '_product_dislikes', true);

    $like_cookie = "wpl_liked_$product_id";
    $dislike_cookie = "wpl_disliked_$product_id";

    $response = [
        'new_likes' => $likes,
        'new_dislikes' => $dislikes,
        'status' => '',
        'message' => '',
    ];

    if ($action_type === 'like') {
        if (isset($_COOKIE[$dislike_cookie])) {
            $dislikes = max(0, $dislikes - 1);
            $likes++;
            update_post_meta($product_id, '_product_dislikes', $dislikes);
            update_post_meta($product_id, '_product_likes', $likes);
            setcookie($dislike_cookie, '', time() - 3600, "/", COOKIE_DOMAIN);
            setcookie($like_cookie, 1, time() + (10 * YEAR_IN_SECONDS), "/", COOKIE_DOMAIN, is_ssl(), true);
            $response['message'] = 'Changed to like';
        } elseif (!isset($_COOKIE[$like_cookie])) {
            $likes++;
            update_post_meta($product_id, '_product_likes', $likes);
            setcookie($like_cookie, 1, time() + (10 * YEAR_IN_SECONDS), "/", COOKIE_DOMAIN, is_ssl(), true);
            $response['message'] = 'Liked';
        } else {
            $response['message'] = 'Already liked.';
        }
        $response['status'] = 'like';
    } elseif ($action_type === 'dislike') {
        if (isset($_COOKIE[$like_cookie])) {
            $likes = max(0, $likes - 1);
            $dislikes++;
            update_post_meta($product_id, '_product_likes', $likes);
            update_post_meta($product_id, '_product_dislikes', $dislikes);
            setcookie($like_cookie, '', time() - 3600, "/", COOKIE_DOMAIN);
            setcookie($dislike_cookie, 1, time() + (10 * YEAR_IN_SECONDS), "/", COOKIE_DOMAIN, is_ssl(), true);
            $response['message'] = 'Changed to dislike';
        } elseif (!isset($_COOKIE[$dislike_cookie])) {
            $dislikes++;
            update_post_meta($product_id, '_product_dislikes', $dislikes);
            setcookie($dislike_cookie, 1, time() + (10 * YEAR_IN_SECONDS), "/", COOKIE_DOMAIN, is_ssl(), true);
            $response['message'] = 'Disliked';
        } else {
            $response['message'] = 'Already disliked.';
        }
        $response['status'] = 'dislike';
    }

    $response['new_likes'] = $likes;
    $response['new_dislikes'] = $dislikes;

    wp_send_json_success($response);
}

// Enqueue scripts and inline styles
add_action('wp_enqueue_scripts', 'wpl_enqueue_scripts');
function wpl_enqueue_scripts() {
    wp_enqueue_script('jquery');

    wp_enqueue_script('wpl-like', plugin_dir_url(__FILE__) . 'js/like.js', ['jquery'], null, true);

    wp_localize_script('wpl-like', 'wpl_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wpl_nonce'),
    ]);

    // Inline CSS
    wp_add_inline_style('wpl-like', '
        #wpl-like-wrapper {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        #wpl-heart-button, .wpl-action-btn {
            font-size: 22px;
            background: none;
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        #wpl-heart-button:hover, .wpl-action-btn:hover {
            transform: scale(1.2);
        }
        .heart-icon {
            font-size: 26px;
        }
    ');
}
