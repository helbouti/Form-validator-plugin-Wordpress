<?php
/*
Plugin Name: Simple Form Submission Plugin
Plugin URI: https://example.com/
Description: A basic WordPress plugin to handle form submissions
Version: 1.0
Author: Your Name
Author URI: https://example.com/
*/

class SimpleFormSubmissionPlugin {
    public function __construct() {
        // Register shortcode to display the form
        add_shortcode('simple_submission_form', [$this, 'render_form']);
        
        // Handle form submission via AJAX
        add_action('wp_ajax_simple_form_submit', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_simple_form_submit', [$this, 'handle_form_submission']);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() {
        // Enqueue jQuery (included with WordPress)
        wp_enqueue_script('jquery');
        
        // Enqueue custom script
        wp_enqueue_script(
            'simple-form-script', 
            plugin_dir_url(__FILE__) . 'form-script.js', 
            ['jquery'], 
            '1.0', 
            true
        );
        
        // Localize script with ajax url
        wp_localize_script('simple-form-script', 'simpleFormAjax', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
        
        // Optional: Enqueue a stylesheet
        wp_enqueue_style(
            'simple-form-style', 
            plugin_dir_url(__FILE__) . 'form-style.css'
        );
    }

    public function render_form() {
        ob_start();
        ?>
        <form id="simple-submission-form">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="message">Message:</label>
                <textarea id="message" name="message" required></textarea>
            </div>
            
            <?php wp_nonce_field('simple_form_submission', 'simple_form_nonce'); ?>
            
            <button type="submit">Submit</button>
            
            <div id="form-response"></div>
        </form>
        <?php
        return ob_get_clean();
    }

    public function handle_form_submission() {
        // Check nonce for security
        if (!wp_verify_nonce($_POST['simple_form_nonce'], 'simple_form_submission')) {
            wp_send_json_error('Security check failed');
        }

        // Sanitize and validate inputs
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $message = sanitize_textarea_field($_POST['message']);

        // Validate email
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
        }

        // Optional: Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'form_submissions';
        
        // Create table if not exists (you'd typically do this in plugin activation)
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            email varchar(100) NOT NULL,
            message text NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert submission
        $insert_result = $wpdb->insert(
            $table_name, 
            [
                'name' => $name,
                'email' => $email,
                'message' => $message
            ],
            ['%s', '%s', '%s']
        );

        // Optional: Send email notification
        $to = get_option('admin_email');
        $subject = 'New Form Submission';
        $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
        wp_mail($to, $subject, $body);

        // Return success response
        wp_send_json_success('Form submitted successfully');
    }
}

// Initialize the plugin
function initialize_simple_form_plugin() {
    new SimpleFormSubmissionPlugin();
}
add_action('plugins_loaded', 'initialize_simple_form_plugin');