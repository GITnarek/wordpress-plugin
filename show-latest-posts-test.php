<?php
/*
Plugin Name: Test Latest Posts Plugin
Description: Test plugin to show the latest posts.
Version: 1.0
Author: Narek Muradyan
*/

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

class Test_Latest_Posts_Plugin {
    private $logger;

    public function __construct() {
        $this->logger = new Logger('wordpress');
        $this->logger->pushHandler(new ErrorLogHandler());

        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'settings_link'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('init', array($this, 'register_shortcodes'));
    }

    public function enqueue_styles(): void {
        wp_enqueue_style('test-latest-posts-styles', plugin_dir_url(__FILE__) . 'assets/style.css');
    }

    public function settings_link($links): array {
        $settings_link = '<a href="options-general.php?page=test-latest-posts-settings">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function add_settings_page(): void {
        add_options_page(
            'Test Latest Posts Settings',
            'Test Latest Posts',
            'manage_options',
            'test-latest-posts-settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php
                settings_fields('test_latest_posts_settings');
                do_settings_sections('test_latest_posts_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings(): void {
        register_setting(
            'test_latest_posts_settings',
            'test_latest_posts_number',
            'sanitize_text_field'
        );

        add_settings_section(
            'test_latest_posts_settings_section',
            'Test Latest Posts Settings',
            array($this, 'settings_section_callback'),
            'test_latest_posts_settings'
        );

        add_settings_field(
            'test_latest_posts_number_field',
            'Number of Posts',
            array($this, 'number_field_callback'),
            'test_latest_posts_settings',
            'test_latest_posts_settings_section'
        );
    }

    public function settings_section_callback(): void {
        echo '<p>Customize settings for the Test Latest Posts Plugin.</p>';
        echo '<p>Copy shortcode to use <strong>[show_latest_posts]</strong></p>';
    }

    public function number_field_callback(): void {
        $value = get_option('test_latest_posts_number', 5);
        echo '<input type="number" id="test_latest_posts_number" name="test_latest_posts_number" value="' . esc_attr($value) . '" />';
    }

    public function register_shortcodes(): void {
        add_shortcode('show_latest_posts', array($this, 'display_latest_posts'));
    }

    public function display_latest_posts(): string {
        $number_of_posts = get_option('test_latest_posts_number', 5);

        $query_args = array(
            'post_type'      => 'post',
            'posts_per_page' => $number_of_posts,
            'order'          => 'DESC',
            'orderby'        => 'date',
        );

        try {
            $latest_posts = new WP_Query($query_args);

            if ($latest_posts->have_posts()) {
                ob_start();
                ?>
                <div class="latest-posts">
                    <?php while ($latest_posts->have_posts()) : $latest_posts->the_post(); ?>
                        <div class="latest-post-box">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="post-image">
                                    <?= get_the_post_thumbnail(get_the_ID(), 'large'); ?>
                                </div>
                            <?php endif; ?>
                            <div class="post-info">
                                <h2><a href="<?= esc_url(get_permalink()); ?>"><?php the_title(); ?></a></h2>
                                <p class="post-excerpt"><?= get_the_excerpt(); ?></p>
                                <a class="learn-more" href="<?= esc_url(get_permalink()); ?>">Learn More</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php
                wp_reset_postdata();

                return ob_get_clean();
            } else {
                return '<p>No posts found.</p>';
            }

        } catch (Exception $e) {
            $this->logger->error('An error occurred: ' . $e->getMessage());
            return '<p>Oops! Something went wrong while retrieving posts.</p>';
        }
    }
}

new Test_Latest_Posts_Plugin();