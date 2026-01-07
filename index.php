<?php
/*
Plugin Name: LeadRouter
Plugin URI: https://vivzon.in/plugins/lead-router/index.html
Description: Route leads from CF7, Elementor, and custom forms to Vivzon CRM, plus a floating WhatsApp Chat widget and Product Enquiries.
Version: 1.3
Author: Sr. Vivek Raj
Author URI: https://vivzon.in
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// --- 1. Core Function: Send to Vivzon API ---

function vivzon_crm_send_to_api($data) {
    $token = get_option('vivzon_crm_token');
    if (empty($token)) {
        error_log('LeadRouter Error: CRM Token is not set.');
        return;
    }

    $url = "https://business.vivzon.in/api/v1/save-lead/{$token}";

    $response = wp_remote_post($url, [
        'method'  => 'POST',
        'body'    => $data,
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        error_log("LeadRouter API Error: " . $response->get_error_message());
    }
}

// --- 2. Admin Menu & Settings ---

add_action('admin_menu', 'vivzon_crm_menu');
add_action('admin_init', 'vivzon_crm_settings');

function vivzon_crm_menu() {
    add_options_page('LeadRouter Settings', 'LeadRouter', 'manage_options', 'leadrouter-settings', 'vivzon_crm_settings_page');
}

function vivzon_crm_settings() {
    // CRM API Settings
    register_setting('vivzon_crm_group', 'vivzon_crm_token');
    
    // WhatsApp Widget Settings
    register_setting('vivzon_crm_group', 'vivzon_wa_enabled');
    register_setting('vivzon_crm_group', 'vivzon_wa_number');
    register_setting('vivzon_crm_group', 'vivzon_wa_message');
    register_setting('vivzon_crm_group', 'vivzon_wa_position');

    // Product Enquiry Settings
    register_setting('vivzon_crm_group', 'vivzon_enquiry_enabled');
    register_setting('vivzon_crm_group', 'vivzon_enquiry_btn_text');
}

function vivzon_crm_settings_page() {
    ?>
    <div class="wrap">
        <h1>LeadRouter Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('vivzon_crm_group'); ?>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px; border: 1px solid #ccc;">
                <h2>🔌 CRM Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="vivzon_crm_token">CRM API Token</label></th>
                        <td>
                            <input type="text" id="vivzon_crm_token" name="vivzon_crm_token" value="<?php echo esc_attr(get_option('vivzon_crm_token')); ?>" class="regular-text" />
                            <p class="description">Get your token from your Vivzon Browser CRM dashboard.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px; border: 1px solid #ccc;">
                <h2>🛍️ Product Enquiry Popup</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Enquiry Button</th>
                        <td>
                            <input type="checkbox" name="vivzon_enquiry_enabled" value="1" <?php checked(1, get_option('vivzon_enquiry_enabled'), true); ?> />
                            <label>Show "Enquire Now" button on WooCommerce product pages</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Button Text</th>
                        <td>
                            <input type="text" name="vivzon_enquiry_btn_text" value="<?php echo esc_attr(get_option('vivzon_enquiry_btn_text', 'Product Enquiry')); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px; border: 1px solid #ccc;">
                <h2>💬 WhatsApp Chat Widget</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Widget</th>
                        <td>
                            <input type="checkbox" name="vivzon_wa_enabled" value="1" <?php checked(1, get_option('vivzon_wa_enabled'), true); ?> />
                            <label>Show floating chat button on the website</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">WhatsApp Number</th>
                        <td>
                            <input type="text" name="vivzon_wa_number" value="<?php echo esc_attr(get_option('vivzon_wa_number')); ?>" placeholder="e.g. 919876543210" class="regular-text" />
                            <p class="description">Include country code (e.g. 91 for India) without '+' or spaces.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Message</th>
                        <td>
                            <input type="text" name="vivzon_wa_message" value="<?php echo esc_attr(get_option('vivzon_wa_message')); ?>" class="regular-text" placeholder="Hello, I have an inquiry!" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Position</th>
                        <td>
                            <select name="vivzon_wa_position">
                                <option value="right" <?php selected(get_option('vivzon_wa_position'), 'right'); ?>>Bottom Right</option>
                                <option value="left" <?php selected(get_option('vivzon_wa_position'), 'left'); ?>>Bottom Left</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// --- 3. Frontend WhatsApp Icon Logic ---

add_action('wp_footer', 'vivzon_crm_render_whatsapp_icon');
function vivzon_crm_render_whatsapp_icon() {
    if (get_option('vivzon_wa_enabled') != '1') return;

    $number = get_option('vivzon_wa_number');
    if (empty($number)) return;

    $message = urlencode(get_option('vivzon_wa_message', 'Hello!'));
    $pos = get_option('vivzon_wa_position', 'right');
    $side_css = ($pos == 'left') ? 'left: 25px;' : 'right: 25px;';
    $wa_url = "https://wa.me/{$number}?text={$message}";

    ?>
    <style>
        .vz-wa-float { position: fixed; bottom: 25px; <?php echo $side_css; ?> width: 60px; height: 60px; background-color: #25d366; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 2px 5px 15px rgba(0,0,0,0.3); z-index: 999999; transition: all 0.3s ease; animation: vz-pulse 2s infinite; text-decoration: none !important; }
        .vz-wa-float:hover { transform: scale(1.1); background-color: #128c7e; }
        .vz-wa-float svg { width: 34px; height: 34px; fill: #fff; display: block; }
        @keyframes vz-pulse { 0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); } 70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); } 100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); } }
        @media (max-width: 768px) { .vz-wa-float { width: 50px; height: 50px; bottom: 20px; <?php echo ($pos == 'left') ? 'left: 20px;' : 'right: 20px;'; ?> } .vz-wa-float svg { width: 28px; height: 28px; } }
    </style>
    <a href="<?php echo $wa_url; ?>" class="vz-wa-float" target="_blank" rel="noopener noreferrer">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.4 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-5.5-2.8-23.2-8.5-44.2-27.1-16.4-14.6-27.4-32.7-30.6-38.2-3.2-5.6-.3-8.6 2.4-11.3 2.5-2.6 5.5-6.5 8.3-9.7 2.8-3.3 3.7-5.6 5.5-9.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.4-29.8-17-40.7-4.5-10.7-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 13.2 5.7 23.5 9.2 31.5 11.8 13.3 4.2 25.4 3.6 35 2.2 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>
    </a>
    <?php
}

// --- 4. Product Enquiry Popup Logic ---

add_action('woocommerce_after_add_to_cart_button', 'vivzon_crm_add_enquiry_button');
function vivzon_crm_add_enquiry_button() {
    if (get_option('vivzon_enquiry_enabled') != '1') return;
    $btn_text = get_option('vivzon_enquiry_btn_text', 'Product Enquiry');
    echo '<button type="button" id="vz-enquiry-trigger" class="button alt" style="margin-left:10px; background-color:#007cba;">'.esc_html($btn_text).'</button>';
}

add_action('wp_footer', 'vivzon_crm_enquiry_modal_html');
function vivzon_crm_enquiry_modal_html() {
    if (get_option('vivzon_enquiry_enabled') != '1' || !is_product()) return;
    global $product;
    ?>
    <div id="vz-enquiry-modal" class="vz-modal">
        <div class="vz-modal-content">
            <span class="vz-close">&times;</span>
            <h3>Enquire About: <span id="vz-prod-name"><?php echo $product->get_name(); ?></span></h3>
            <form id="vz-enquiry-form">
                <input type="hidden" name="subject" value="Enquiry for: <?php echo esc_attr($product->get_name()); ?>">
                <div class="vz-field"><input type="text" name="name" placeholder="Your Name" required></div>
                <div class="vz-field"><input type="email" name="email" placeholder="Your Email" required></div>
                <div class="vz-field"><input type="text" name="mob" placeholder="Phone Number" required></div>
                <div class="vz-field"><textarea name="message" placeholder="Message" rows="3"></textarea></div>
                <button type="submit" id="vz-submit-btn">Send Enquiry</button>
                <div id="vz-status"></div>
            </form>
        </div>
    </div>

    <style>
        .vz-modal { display: none; position: fixed; z-index: 9999999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); }
        .vz-modal-content { background: #fff; margin: 10% auto; padding: 25px; width: 90%; max-width: 450px; border-radius: 8px; position: relative; }
        .vz-close { position: absolute; right: 15px; top: 10px; font-size: 28px; cursor: pointer; }
        .vz-field { margin-bottom: 15px; }
        .vz-field input, .vz-field textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        #vz-submit-btn { width: 100%; padding: 12px; background: #007cba; color: #fff; border: none; cursor: pointer; border-radius: 4px; }
        #vz-status { margin-top: 10px; text-align: center; font-size: 14px; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById("vz-enquiry-modal");
            var btn = document.getElementById("vz-enquiry-trigger");
            var span = document.getElementsByClassName("vz-close")[0];

            if(btn) {
                btn.onclick = function() { modal.style.display = "block"; }
            }
            span.onclick = function() { modal.style.display = "none"; }
            window.onclick = function(event) { if (event.target == modal) modal.style.display = "none"; }

            document.getElementById('vz-enquiry-form').onsubmit = function(e) {
                e.preventDefault();
                var btn = document.getElementById('vz-submit-btn');
                var status = document.getElementById('vz-status');
                btn.disabled = true;
                btn.innerText = 'Sending...';

                var formData = new FormData(this);
                formData.append('action', 'vz_handle_enquiry');
                formData.append('website', window.location.origin);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                }).then(res => res.text()).then(data => {
                    status.innerHTML = '✅ Enquiry sent successfully!';
                    btn.innerText = 'Sent';
                    setTimeout(() => { modal.style.display = "none"; }, 2000);
                }).catch(err => {
                    status.innerHTML = '❌ Error sending enquiry.';
                    btn.disabled = false;
                });
            };
        });
    </script>
    <?php
}

// AJAX Handler for Custom Enquiry
add_action('wp_ajax_vz_handle_enquiry', 'vz_handle_enquiry');
add_action('wp_ajax_nopriv_vz_handle_enquiry', 'vz_handle_enquiry');
function vz_handle_enquiry() {
    $payload = [
        'name'    => sanitize_text_field($_POST['name']),
        'email'   => sanitize_email($_POST['email']),
        'mob'     => sanitize_text_field($_POST['mob']),
        'subject' => sanitize_text_field($_POST['subject']),
        'message' => sanitize_textarea_field($_POST['message']),
        'website' => esc_url($_POST['website'])
    ];
    vivzon_crm_send_to_api($payload);
    wp_send_json_success();
    wp_die();
}

// --- 5. Contact Form 7 Integration ---

add_action('wpcf7_mail_sent', 'vivzon_crm_c7_submission');
function vivzon_crm_c7_submission($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return;
    
    $data = $submission->get_posted_data();

    $payload = [
		'name'    => trim(($data['your-name'] ?? $data['name'] ?? '') . ' ' . ($data['your-lastname'] ?? $data['lastname'] ?? $data['last_name'] ?? ''))
						?: trim(($data['your-firstname'] ?? $data['firstname'] ?? $data['first_name'] ?? '') . ' ' . ($data['your-lastname'] ?? $data['lastname'] ?? $data['last_name'] ?? '')),
		'email'   => $data['your-email'] ?? $data['email'] ?? $data['user_email'] ?? '',
		'mob'     => $data['your-phone'] ?? $data['phone'] ?? $data['your-mob'] ?? $data['your-mobile'] ?? $data['mobile'] ?? $data['contact'] ?? '',
        'company' => $data['your-company'] ?? $data['company'] ?? $data['company_name'] ?? $data['organization'] ?? '',
		'subject' => $data['your-subject'] ?? $data['subject'] ?? '',
		'message' => $data['your-message'] ?? $data['message'] ?? $data['comments'] ?? $data['enquiry'] ?? '',
		'website' => home_url()
	];
    
    if(empty(trim($payload['name']))) { $payload['name'] = $data['your-name'] ?? $data['name'] ?? ''; }
    if (empty($payload['email']) && empty($payload['mob'])) return;

    vivzon_crm_send_to_api($payload);
}

// --- 6. Elementor Pro Forms Integration ---

add_action('elementor_pro/forms/new_record', 'vivzon_crm_elementor_submission', 10, 2);
function vivzon_crm_elementor_submission($record, $handler) {
    $fields = $record->get('fields');

    $get_field_value = function($keys) use ($fields) {
        foreach ((array)$keys as $key) {
            if (isset($fields[$key]['value']) && !empty($fields[$key]['value'])) {
                return $fields[$key]['value'];
            }
        }
        return '';
    };

    $first_name = $get_field_value(['firstname', 'first_name', 'your-firstname']);
    $last_name = $get_field_value(['lastname', 'last_name', 'your-lastname']);
    $full_name_combined = trim("{$first_name} {$last_name}");
    
    $payload = [
		'name'    => $get_field_value('name') ?: $full_name_combined,
		'email'   => strtolower(trim($get_field_value(['email', 'your-email', 'user_email']))),
		'mob'     => preg_replace('/\D+/', '', $get_field_value(['phone', 'your-phone', 'mobile', 'your-mobile', 'your-mob', 'contact'])),
        'company' => trim($get_field_value(['company', 'your-company', 'company_name', 'organization'])),
		'subject' => trim($get_field_value(['subject', 'your-subject', 'topic'])),
		'message' => strip_tags(trim($get_field_value(['message', 'your-message', 'comments', 'enquiry']))),
		'website' => home_url()
	];
    
    if (empty($payload['email']) && empty($payload['mob'])) return;
    
	vivzon_crm_send_to_api($payload);
}
