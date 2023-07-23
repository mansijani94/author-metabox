<?php
/**
 * Plugin Name: Multi Contributors
 * Plugin URI:  http://example.com/multi-contributors
 * Description: Add multiple contributors to posts.
 * Version:     1.0.0
 * Author:      Mansi Jani
 * Author URI:  http://example.com
 * Text Domain: multi-contributors
 * Domain Path: /languages
 *
 * @package Multi_Contributors
 */

// Register and display the contributors meta box
function mc_add_contributors_meta_box() {
    add_meta_box('mc_contributors', 'Contributors', 'mc_render_contributors_meta_box', 'post', 'side', 'high');
}
add_action('add_meta_boxes', 'mc_add_contributors_meta_box');

// Render the contributors meta box
function mc_render_contributors_meta_box($post) {
   $current_author_id = $post->post_author;
   $users = get_users(array('role__in' => array('author', 'editor', 'administrator')));
   $contributors = get_post_meta($post->ID, 'mc_contributors', true);

   if (!is_array($contributors)) {
       $contributors = array();
   }

   ?>
   <div>
       <?php wp_nonce_field('mc_contributors_nonce', 'mc_contributors_nonce_field'); ?>
       <?php foreach ($users as $user) : ?>
           <label>
               <input type="checkbox" name="mc_contributors[]" value="<?php echo esc_attr($user->ID); ?>" <?php checked(in_array($user->ID, $contributors)); ?> <?php disabled($user->ID == $current_author_id); ?>>
               <?php echo esc_html($user->first_name . ' ' . $user->last_name . ' (' . $user->user_login . ')'); ?>
           </label>
           <br>
       <?php endforeach; ?>
   </div>
   <?php
}

// Save the selected contributors on post save/update
function mc_save_contributors($post_id) {
    if (!isset($_POST['mc_contributors_nonce_field']) || !wp_verify_nonce($_POST['mc_contributors_nonce_field'], 'mc_contributors_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['mc_contributors'])) {
        $contributors = array_map('intval', $_POST['mc_contributors']);
        update_post_meta($post_id, 'mc_contributors', $contributors);
    } else {
        delete_post_meta($post_id, 'mc_contributors');
    }
}
add_action('save_post', 'mc_save_contributors');

// Display the contributors on single posts
function mc_display_contributors() {
    $contributors = get_post_meta(get_the_ID(), 'mc_contributors', true);

    if ($contributors) {
        echo '<div class="mc-contributors">';
        echo '<h3>Contributors:</h3>';
        echo '<ul>';

        foreach ($contributors as $contributor_id) {
            $contributor = get_userdata($contributor_id);

            if ($contributor) {
                echo '<li>';
                echo get_avatar($contributor_id);
                echo '<a href="' . get_author_posts_url($contributor_id) . '">' . esc_html($contributor->display_name) . '</a>';
                echo '</li>';
            }
        }

        echo '</ul>';
        echo '</div>';
    }
}
add_action('the_content', 'mc_display_contributors');

// Display the contributors on the author archive page
function mc_display_contributors_on_author_archive($query) {
    if ($query->is_author() && $query->is_main_query()) {
        $author_id = get_queried_object_id();
        $args = array(
            'post_type' => 'post',
            'meta_query' => array(
                array(
                    'key' => 'mc_contributors',
                    'value' => $author_id,
                    'compare' => 'LIKE'
                )
            )
        );
        $query->set('meta_query', $args);
    }
}
add_action('pre_get_posts', 'mc_display_contributors_on_author_archive');


// Enqueue necessary scripts and stylesheets
function mc_enqueue_scripts() {
    wp_enqueue_style('multi-contributors', plugins_url('/css/multi-contributors.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'mc_enqueue_scripts');

// Load the language file for translation
function mc_load_textdomain() {
    load_plugin_textdomain('multi-contributors', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'mc_load_textdomain');
