<?php
/*
Plugin Name: LeadRouter Pro
Plugin URI: https://vivzon.in/plugins/lead-router/
Description: Premium Lead Routing: CRM Sync, SMS OTP Verification, WhatsApp Widget, and Product Enquiry. Optimized for Woodmart.
Version: 3.4
Author: Sr. Vivek Raj
Author URI: https://vivzon.in
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================
// 1. ADMIN MENU & SETTINGS INITIALIZATION
// ==========================================

add_action('admin_menu', 'vz_leadrouter_menu');
add_action('admin_init', 'vz_leadrouter_settings');

function vz_leadrouter_menu() {
    add_options_page('LeadRouter Settings', 'LeadRouter', 'manage_options', 'leadrouter-settings', 'vz_leadrouter_settings_page');
}

function vz_leadrouter_settings() {
    $settings = [
        'vivzon_crm_token', 'vivzon_admin_email_notify',
        'vivzon_wa_enabled', 'vivzon_wa_number', 'vivzon_wa_message',
        'vivzon_enquiry_enabled', 'vivzon_enquiry_btn_text',
        'vivzon_sms_enabled', 'vivzon_sms_api_url', 'vivzon_sms_api_key', 'vivzon_sms_sender_id',
        'vivzon_otp_enabled' // New specific toggle for OTP
    ];
    foreach ($settings as $setting) { register_setting('vz_lr_group', $setting); }
}

// ==========================================
// 2. ENHANCED ADMIN UI & ALERT FIX
// ==========================================

function vz_leadrouter_settings_page() {
    ?>
    <style>
        :root { --vz-primary: #007cba; --vz-sidebar: #2c3338; --vz-text: #1d2327; }
        .vz-admin-wrap { margin: 20px 20px 0 0; display: flex; background: #fff; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); overflow: hidden; min-height: 650px; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; position: relative; }
        .vz-admin-wrap .notice, .vz-admin-wrap .updated, .vz-admin-wrap .error { position: relative; top: 0; right: 0; left: 0; z-index: 10; margin: 0 0 20px 0 !important; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .vz-sidebar { width: 240px; background: var(--vz-sidebar); color: #fff; padding-top: 20px; flex-shrink: 0; }
        .vz-sidebar h2 { padding: 0 20px 20px; font-size: 18px; border-bottom: 1px solid #3c434a; color: #72aee6; margin-top: 0; }
        .vz-tab-link { display: flex; align-items: center; gap: 10px; padding: 15px 20px; color: #eee; text-decoration: none; border-bottom: 1px solid #3c434a; cursor: pointer; transition: 0.3s; }
        .vz-tab-link:hover { background: #3c434a; }
        .vz-tab-link.active { background: var(--vz-primary); color: #fff; font-weight: 600; box-shadow: inset 4px 0 0 #fff; }
        .vz-content { flex: 1; padding: 40px; position: relative; background: #fff; }
        .vz-tab-content { display: none; }
        .vz-tab-content.active { display: block; animation: vzFadeIn 0.3s ease; }
        @keyframes vzFadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .vz-card-title { font-size: 24px; font-weight: 500; margin: 0 0 30px; color: var(--vz-text); border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .form-table th { width: 200px; font-weight: 600; color: #50575e; }
        .vz-input-full { width: 100%; max-width: 500px; padding: 10px 12px !important; border-radius: 6px !important; border: 1px solid #c3c4c7 !important; background: #fff; }
        .vz-hint { display: block; font-size: 12px; color: #646970; margin-top: 8px; font-style: italic; }
        .vz-btn-wrap { margin-top: 40px; padding-top: 25px; border-top: 1px solid #eee; }
        .vz-help-box { background: #f8f9fa; border: 1px solid #e2e4e7; padding: 25px; border-radius: 6px; }
        .vz-link { color: var(--vz-primary); text-decoration: none; font-weight: 600; }
    </style>

    <div class="wrap">
        <div class="vz-admin-wrap">
            <div class="vz-sidebar">
                <h2>LeadRouter Pro</h2>
                <div class="vz-tab-link active" onclick="vzTab(event, 'tab-crm')">🔌 CRM Config</div>
                <div class="vz-tab-link" onclick="vzTab(event, 'tab-sms')">📱 SMS & OTP</div>
                <div class="vz-tab-link" onclick="vzTab(event, 'tab-frontend')">🎨 Frontend Assets</div>
                <div class="vz-tab-link" onclick="vzTab(event, 'tab-help')">❓ Help & Guide</div>
            </div>

            <div class="vz-content" id="vz-content-area">
                <form method="post" action="options.php">
                    <?php settings_fields('vz_lr_group'); ?>
                    
                    <div id="tab-crm" class="vz-tab-content active">
                        <h3 class="vz-card-title">Vivzon CRM Configuration</h3>
                        <table class="form-table">
                            <tr><th>API Access Token</th><td><input type="text" name="vivzon_crm_token" value="<?php echo esc_attr(get_option('vivzon_crm_token')); ?>" class="vz-input-full" /></td></tr>
                            <tr><th>Notification Email</th><td><input type="email" name="vivzon_admin_email_notify" value="<?php echo esc_attr(get_option('vivzon_admin_email_notify', get_option('admin_email'))); ?>" class="vz-input-full" /></td></tr>
                        </table>
                    </div>

                    <div id="tab-sms" class="vz-tab-content">
                        <h3 class="vz-card-title">SMS Gateway & OTP</h3>
                        <table class="form-table">
                            <tr><th>Enable SMS Master</th><td><input type="checkbox" name="vivzon_sms_enabled" value="1" <?php checked(1, get_option('vivzon_sms_enabled')); ?> /> Enable Gateway</td></tr>
                            <tr><th>Enable Registration OTP</th><td><input type="checkbox" name="vivzon_otp_enabled" value="1" <?php checked(1, get_option('vivzon_otp_enabled')); ?> /> Require OTP for New Users</td></tr>
                            <tr><th>API URL</th><td><input type="text" name="vivzon_sms_api_url" value="<?php echo esc_attr(get_option('vivzon_sms_api_url')); ?>" class="vz-input-full" placeholder="https://api.sms.com/send?key={api_key}&to={number}&msg={message}" /></td></tr>
                            <tr><th>API Key</th><td><input type="text" name="vivzon_sms_api_key" value="<?php echo esc_attr(get_option('vivzon_sms_api_key')); ?>" class="vz-input-full" /></td></tr>
                            <tr><th>Sender ID</th><td><input type="text" name="vivzon_sms_sender_id" value="<?php echo esc_attr(get_option('vivzon_sms_sender_id')); ?>" class="vz-input-full" /></td></tr>
                        </table>
                    </div>

                    <div id="tab-frontend" class="vz-tab-content">
                        <h3 class="vz-card-title">Frontend Features</h3>
                        <table class="form-table">
                            <tr><th>WhatsApp Widget</th><td><input type="checkbox" name="vivzon_wa_enabled" value="1" <?php checked(1, get_option('vivzon_wa_enabled')); ?> /> Enable Floating Icon<br><br><input type="text" name="vivzon_wa_number" value="<?php echo esc_attr(get_option('vivzon_wa_number')); ?>" placeholder="91..." class="vz-input-full" /></td></tr>
                            <tr><th>Enquiry Popup</th><td><input type="checkbox" name="vivzon_enquiry_enabled" value="1" <?php checked(1, get_option('vivzon_enquiry_enabled')); ?> /> Enable Modal on Products<br><br><input type="text" name="vivzon_enquiry_btn_text" value="<?php echo esc_attr(get_option('vivzon_enquiry_btn_text', 'Enquire Now')); ?>" class="vz-input-full" /></td></tr>
                        </table>
                    </div>

                    <div id="tab-help" class="vz-tab-content">
                        <h3 class="vz-card-title">Help & Support</h3>
                        <div class="vz-help-box">
                            <a href="https://vivzon.in/plugins/lead-router/" target="_blank" class="vz-link">🌐 Documentation & Guide: https://vivzon.in/plugins/lead-router/</a>
                        </div>
                    </div>

                    <div class="vz-btn-wrap"><?php submit_button('Save LeadRouter Settings', 'primary large'); ?></div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function vzTab(e,t){var i,c,l;c=document.getElementsByClassName("vz-tab-content");for(i=0;i<c.length;i++)c[i].style.display="none";l=document.getElementsByClassName("vz-tab-link");for(i=0;i<l.length;i++)l[i].className=l[i].className.replace(" active","");document.getElementById(t).style.display="block";e.currentTarget.className+=" active";}
        document.addEventListener('DOMContentLoaded', function() { var notices = document.querySelectorAll('.notice, .updated, .error'); var target = document.getElementById('vz-content-area'); notices.forEach(function(n) { target.prepend(n); }); });
    </script>
    <?php
}

// ==========================================
// 3. CORE LOGIC
// ==========================================

function vz_send_to_crm($data) {
    $token = get_option('vivzon_crm_token');
    if (!$token) return;
    wp_remote_post("https://business.vivzon.in/api/v1/save-lead/{$token}", ['method' => 'POST', 'body' => $data, 'timeout' => 15]);
}

function vz_sms_engine($phone, $msg) {
    if (get_option('vivzon_sms_enabled') != '1') return false;
    $url = str_replace(['{api_key}','{sender}','{number}','{message}'], [get_option('vivzon_sms_api_key'), get_option('vivzon_sms_sender_id'), $phone, urlencode($msg)], get_option('vivzon_sms_api_url'));
    $res = wp_remote_get($url);
    return !is_wp_error($res);
}

// ==========================================
// 4. PRODUCT ENQUIRY (HIDE IF DISABLED)
// ==========================================

if (get_option('vivzon_enquiry_enabled') == '1') {
    add_action('woocommerce_single_product_summary', function() {
        echo '<div style="margin-top:15px;"><button type="button" id="vz-trigger" class="button alt" style="background:#000; color:#fff; width:100%; max-width:200px; height:48px;">'.esc_html(get_option('vivzon_enquiry_btn_text', 'Enquire Now')).'</button></div>';
    }, 35);

    add_action('wp_footer', 'vz_enquiry_modal_html');
}

function vz_enquiry_modal_html() {
    if (!is_product()) return;
    global $product; if(!$product) return;
    ?>
    <div id="vz-modal" style="display:none; position:fixed; z-index:999999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter: blur(3px);">
        <div style="background:#fff; margin:8% auto; padding:35px; width:90%; max-width:400px; border-radius:12px; position:relative; box-shadow:0 20px 50px rgba(0,0,0,0.3);">
            <span id="vz-close" style="position:absolute; right:20px; top:15px; cursor:pointer; font-size:28px; color:#999;">&times;</span>
            <h3 style="margin:0 0 5px; font-size:22px;">Product Enquiry</h3>
            <p style="margin-bottom:20px; color:#666; font-size:14px;"><?php echo $product->get_name(); ?></p>
            <form id="vz-form">
                <input type="hidden" name="product_name" value="<?php echo esc_attr($product->get_name()); ?>">
                <input type="hidden" name="product_url" value="<?php echo esc_url(get_permalink()); ?>">
                <div style="margin-bottom:12px;"><input type="text" name="name" placeholder="Full Name" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;"></div>
                <div style="margin-bottom:12px;"><input type="email" name="email" placeholder="Email Address" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;"></div>
                <div style="margin-bottom:12px;"><input type="text" name="mob" placeholder="Phone Number" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;"></div>
                <div style="margin-bottom:20px;"><textarea name="message" placeholder="How can we help?" rows="3" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;"></textarea></div>
                <button type="submit" id="vz-submit" style="width:100%; background:#007cba; color:#fff; border:none; padding:14px; font-weight:bold; cursor:pointer; border-radius:5px; font-size:16px;">Send Enquiry</button>
                <div id="vz-res" style="margin-top:15px; text-align:center;"></div>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var m = document.getElementById('vz-modal'), b = document.getElementById('vz-trigger'), c = document.getElementById('vz-close');
        if(b) b.onclick = function(){ m.style.display="block"; }
        if(c) c.onclick = function(){ m.style.display="none"; }
        document.getElementById('vz-form').onsubmit = function(e){
            e.preventDefault();
            var btn = document.getElementById('vz-submit'), res = document.getElementById('vz-res'); btn.disabled = true; btn.innerText = 'Sending...';
            var fd = new FormData(this); fd.append('action', 'vz_handle_enquiry');
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd }).then(r => r.json()).then(() => {
                res.innerHTML = '<span style="color:green; font-weight:600;">✅ Enquiry Sent!</span>';
                setTimeout(() => { m.style.display="none"; btn.disabled=false; btn.innerText='Send Enquiry'; res.innerHTML=''; }, 2500);
            });
        };
    });
    </script>
    <?php
}

add_action('wp_ajax_vz_handle_enquiry', 'vz_ajax_enquiry_process');
add_action('wp_ajax_nopriv_vz_handle_enquiry', 'vz_ajax_enquiry_process');
function vz_ajax_enquiry_process() {
    vz_send_to_crm(['name' => $_POST['name'], 'email' => $_POST['email'], 'mob' => $_POST['mob'], 'subject' => "Enquiry: ".$_POST['product_name'], 'message' => "Product: ".$_POST['product_name']."\nLink: ".$_POST['product_url']."\n\n".$_POST['message'], 'website' => home_url()]);
    wp_mail(get_option('vivzon_admin_email_notify', get_option('admin_email')), "Product Enquiry: ".$_POST['product_name'], "New Enquiry Received:\n\nName: {$_POST['name']}\nEmail: {$_POST['email']}\nPhone: {$_POST['mob']}\nProduct: {$_POST['product_name']}\nLink: {$_POST['product_url']}\n\nMessage: {$_POST['message']}");
    wp_send_json_success();
}

// ==========================================
// 5. WOODMART OTP SYSTEM (HIDE IF DISABLED)
// ==========================================

if (get_option('vivzon_otp_enabled') == '1') {
    add_action('woocommerce_register_form', 'vz_woodmart_otp_ui');
    add_filter('woocommerce_registration_errors', 'vz_verify_otp_logic', 10, 3);
    add_action('woocommerce_created_customer', 'vz_save_user_phone');
}

function vz_woodmart_otp_ui() {
    ?>
    <div style="margin-bottom:20px; border-top:1px solid #eee; padding-top:20px;">
        <label><?php _e('Mobile Number', 'woocommerce'); ?> <span class="required">*</span></label>
        <div style="display:flex; border:1px solid #e1e1e1; height:50px; margin-top:8px; border-radius:3px; overflow:hidden;">
            <input type="text" name="billing_phone" id="vz_reg_phone" placeholder="e.g. 919876543210" style="flex:1; border:none; padding:10px 15px; height:100%;">
            <button type="button" id="vz_send_otp_btn" style="background:#000; color:#fff; border:none; padding:0 20px; font-weight:bold; cursor:pointer; font-size:12px; height:100%;">SEND OTP</button>
        </div>
        <div id="vz_otp_sec" style="display:none; margin-top:15px;"><label><?php _e('Enter OTP Code', 'woocommerce'); ?> *</label><input type="text" name="registration_otp" maxlength="6" style="width:100%; height:50px; border:1px solid #e1e1e1; padding:10px; margin-top:8px;"></div>
        <div id="vz_reg_msg" style="margin-top:10px; font-weight:600; font-size:13px;"></div>
    </div>
    <script>
    jQuery(document).ready(function($){
        $('#vz_send_otp_btn').click(function(){
            var p = $('#vz_reg_phone').val(), $btn = $(this); if(p.length < 10) return alert('Valid phone required');
            $btn.prop('disabled',true).text('Sending...');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {action:'vz_send_reg_otp', phone:p}, function(res){
                if(res.success){ $('#vz_otp_sec').slideDown(); $('#vz_reg_msg').html('<span style="color:green;">✅ OTP Sent</span>'); $('#vz_reg_phone').prop('readonly',true); }
                else { $('#vz_reg_msg').html('<span style="color:red;">❌ '+res.data+'</span>'); }
                $btn.prop('disabled',false).text('RESEND');
            });
        });
    });
    </script>
    <?php
}

add_action('wp_ajax_vz_send_reg_otp', 'vz_ajax_otp_handler');
add_action('wp_ajax_nopriv_vz_send_reg_otp', 'vz_ajax_otp_handler');
function vz_ajax_otp_handler() {
    $p = sanitize_text_field($_POST['phone']); $otp = rand(100000, 999999);
    set_transient('vz_reg_otp_'.$p, $otp, 600);
    if(vz_sms_engine($p, "Your OTP is: $otp")) wp_send_json_success(); else wp_send_json_error("SMS Error");
}

function vz_verify_otp_logic($e, $u, $em) {
    $p = $_POST['billing_phone'] ?? ''; if(get_transient('vz_reg_otp_'.$p) != ($_POST['registration_otp'] ?? '')) $e->add('otp_err', 'Invalid OTP.');
    return $e;
}

function vz_save_user_phone($id) {
    $p = sanitize_text_field($_POST['billing_phone']); update_user_meta($id, 'billing_phone', $p);
    $u = get_userdata($id); vz_send_to_crm(['name'=>$u->user_login, 'email'=>$u->user_email, 'mob'=>$p, 'subject'=>'Registration', 'website'=>home_url()]);
}

// ==========================================
// 6. WHATSAPP & EXTERNAL (HIDE IF DISABLED)
// ==========================================

if (get_option('vivzon_wa_enabled') == '1') {
    add_action('wp_footer', function(){
        $num = get_option('vivzon_wa_number'); if(!$num) return;
        $msg = urlencode(get_option('vivzon_wa_message', 'Hello!'));
        echo '<a href="https://wa.me/'.$num.'?text='.$msg.'" style="position:fixed; bottom:25px; right:25px; z-index:99999; background:#25d366; width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 5px 15px rgba(0,0,0,0.2);" target="_blank"><svg style="width:32px; height:32px; fill:#fff;" viewBox="0 0 448 512"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.4 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-5.5-2.8-23.2-8.5-44.2-27.1-16.4-14.6-27.4-32.7-30.6-38.2-3.2-5.6-.3-8.6 2.4-11.3 2.5-2.6 5.5-6.5 8.3-9.7 2.8-3.3 3.7-5.6 5.5-9.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.4-29.8-17-40.7-4.5-10.7-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 13.2 5.7 23.5 9.2 31.5 11.8 13.3 4.2 25.4 3.6 35 2.2 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg></a>';
    });
}

add_action('wpcf7_mail_sent', function($cf){
    $s = WPCF7_Submission::get_instance(); if(!$s) return; $d = $s->get_posted_data();
    vz_send_to_crm(['name'=>$d['your-name']??'','email'=>$d['your-email']??'','mob'=>$d['your-phone']??'','message'=>$d['your-message']??'','website'=>home_url()]);
});
