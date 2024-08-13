<?php
/*
Plugin Name: Webform2Telegram
Description: Simple webform constructor with results sending to Telegram.
Version: 1.0
Author: DarthArth
*/

if (!defined('ABSPATH')) {
    exit;
}

function webform2telegram_load() {
    add_action('admin_menu', 'webform2telegram_add_admin_menu');
    add_action('admin_init', 'webform2telegram_settings_init');
}

add_action('plugins_loaded', 'webform2telegram_load');


function webform2telegram_add_admin_menu() {
    add_menu_page('Webform2Telegram', 'Webform2Telegram', 'manage_options', 'webform2telegram', 'webform2telegram_options_page');
}


function webform2telegram_settings_init() {
    register_setting('webform2telegram_settings', 'webform2telegram_options');

    add_settings_section(
        'webform2telegram_section',
        __('Form settings', 'webform2telegram'),
        'webform2telegram_settings_section_callback',
        'webform2telegram_settings'
    );

    add_settings_field(
        'webform2telegram_field_chat_id',
        __('Chat ID', 'webform2telegram'),
        'webform2telegram_field_chat_id_render',
        'webform2telegram_settings',
        'webform2telegram_section'
    );

    add_settings_field(
        'webform2telegram_field_telegram_token',
        __('Telegram API Token', 'webform2telegram'),
        'webform2telegram_field_telegram_token_render',
        'webform2telegram_settings',
        'webform2telegram_section'
    );

    add_settings_field(
        'webform2telegram_field_form_builder',
        __('Webform designer', 'webform2telegram'),
        'webform2telegram_field_form_builder_render',
        'webform2telegram_settings',
        'webform2telegram_section'
    );

    add_settings_field(
        'webform2telegram_field_custom_css',
        __('Custom CSS', 'webform2telegram'),
        'webform2telegram_field_custom_css_render',
        'webform2telegram_settings',
        'webform2telegram_section'
    );

    add_settings_field(
        'webform2telegram_shortcode',
        __('Your shortcode', 'webform2telegram'),
        'webform2telegram_shortcode_render',
        'webform2telegram_settings',
        'webform2telegram_section'
    );
}

function webform2telegram_field_custom_css_render() {
    $options = get_option('webform2telegram_options');
    $custom_css = isset($options['custom_css']) ? $options['custom_css'] : '';
    ?>
    <textarea name='webform2telegram_options[custom_css]' rows="10" cols="50" placeholder="Enter your custom CSS here..."><?php echo esc_textarea($custom_css); ?></textarea>
    <p class="description">Enter CSS code to customize the appearance of your form.</p>
    <?php
}

function webform2telegram_field_chat_id_render() {
    $options = get_option('webform2telegram_options');
    $chat_id = isset($options['chat_id']) ? $options['chat_id'] : '';
    $telegram_token = isset($options['telegram_token']) ? $options['telegram_token'] : '';

    $get_updates_url = '';
    if (!empty($telegram_token)) {
        $get_updates_url = "https://api.telegram.org/bot" . esc_attr($telegram_token) . "/getUpdates";
    }
    ?>
    <input type='text' name='webform2telegram_options[chat_id]' value='<?php echo esc_attr($chat_id); ?>' placeholder='Enter your Chat ID'>
    <?php if ($get_updates_url): ?>
        <p class="description">To get a Chat ID for your chat, send a message to the chat where your bot is added, then follow this <a href="<?php echo esc_url($get_updates_url); ?>" target="_blank">link</a> for getting Chat ID. (Save the settings with the token first so that the link with your token is generated). Your chat_id has been something like this - "chat":{"id":291358173,</p>
    <?php else: ?>
        <p class="description">To get Chat ID, first enter Telegram API Token and save settings.</p>
    <?php endif; ?>
    <?php
}

function webform2telegram_field_telegram_token_render() {
    $options = get_option('webform2telegram_options');
    $telegram_token = isset($options['telegram_token']) ? $options['telegram_token'] : '';
    ?>
    <input type='text' name='webform2telegram_options[telegram_token]' value='<?php echo esc_attr($telegram_token); ?>' placeholder='Enter your Telegram API Token'>
    <p class="description">You can get a Telegram API Token by calling <a href="https://t.me/BotFather" target="_blank">BotFather</a> in Telegram. Create a new bot and it will give you a token.</p>
    <?php
}

function webform2telegram_shortcode_render() {
    ?>
    <p>To insert the form on the page, use the following shortcode:</p>
    <code>[webform2telegram_form]</code>
    <?php
}

function webform2telegram_field_form_builder_render() {
    $options = get_option('webform2telegram_options');
    if ($options === false) {
        $options = [];
    }

    $form_fields = isset($options['form_fields']) ? $options['form_fields'] : [];

    ?>
    <div id="form-builder">
        <?php foreach ($form_fields as $index => $field) : ?>
            <div class="form-field">
                <input type="text" name="webform2telegram_options[form_fields][<?php echo $index; ?>][label]" value="<?php echo esc_attr($field['label']); ?>" placeholder="Label">
                <input type="text" name="webform2telegram_options[form_fields][<?php echo $index; ?>][placeholder]" value="<?php echo esc_attr($field['placeholder']); ?>" placeholder="Placeholder">
                <select name="webform2telegram_options[form_fields][<?php echo $index; ?>][type]">
                    <option value="text" <?php selected($field['type'], 'text'); ?>>Text</option>
                    <option value="textarea" <?php selected($field['type'], 'textarea'); ?>>Textarea</option>
                </select>
                <button type="button" class="remove-field button">Remove</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add-field" class="button">Add field</button>
    <style>
        #form-builder input {
            width: 50%;
            margin: 0 0 20px 0;
            display: flex;
        }

        #form-builder select {
            margin: 0 0 20px 0;
            display: flex;
        }

        #form-builder .form-field {
            padding: 0 0 20px 0;
            margin: 0 0 20px 0;
            border-bottom: 2px solid gray;
        }

    </style>
    <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                $('#add-field').on('click', function() {
                    var fieldIndex = $('#form-builder .form-field').length;
                    var newField = '<div class="form-field">' +
                        '<input type="text" name="webform2telegram_options[form_fields][' + fieldIndex + '][label]" placeholder="Label">' +
                        '<input type="text" name="webform2telegram_options[form_fields][' + fieldIndex + '][placeholder]" placeholder="Placeholder">' +
                        '<select name="webform2telegram_options[form_fields][' + fieldIndex + '][type]">' +
                        '<option value="text">Text</option>' +
                        '<option value="textarea">Textarea</option>' +
                        '</select>' +
                        '<button type="button" class="remove-field button">Remove</button>' +
                        '</div>';
                    $('#form-builder').append(newField);
                });

                $('#form-builder').on('click', '.remove-field', function() {
                    $(this).parent('.form-field').remove();
                });
            });
        })(jQuery);
    </script>
    <?php
}

function webform2telegram_settings_section_callback() {
    echo __('Configure the form to send results to Telegram.', 'webform2telegram');
}

function webform2telegram_field_telegram_username_render() {
    $options = get_option('webform2telegram_options');

    if ($options === false) {
        $options = [];
    }

    $telegram_username = isset($options['telegram_username']) ? $options['telegram_username'] : '';
    ?>
    <input type='text' name='webform2telegram_options[telegram_username]' value='<?php echo esc_attr($telegram_username); ?>'>
    <?php
}


function webform2telegram_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h2>Webform2Telegram</h2>
        <?php
        settings_fields('webform2telegram_settings');
        do_settings_sections('webform2telegram_settings');
        submit_button();
        ?>
    </form>
    <?php
}

function webform2telegram_register_shortcode() {
    add_shortcode('webform2telegram_form', 'webform2telegram_form_shortcode');
}

add_action('init', 'webform2telegram_register_shortcode');


function webform2telegram_form_shortcode() {
    global $wp;

    $options = get_option('webform2telegram_options');
    if ($options === false || empty($options['form_fields'])) {
        return '<p>The form is not configured yet.</p>';
    }

    $form_fields = $options['form_fields'];
    $custom_css = isset($options['custom_css']) ? $options['custom_css'] : '';

    // Якщо глобальна змінна $wp не визначена, ініціалізуємо її
    if (is_null($wp)) {
        $wp = new WP();
    }

    ob_start();
    ?>
    <form id="webform2telegram-form" method="post" action="<?php echo esc_url(home_url(add_query_arg(array(), $wp->request))); ?>">
        <?php foreach ($form_fields as $field) : ?>
            <div class="form-group">
                <?php if ($field['type'] == 'text') : ?>
                    <label><?php echo esc_html($field['label']); ?></label>
                    <input type="text" name="<?php echo sanitize_title($field['label']); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>">
                <?php elseif ($field['type'] == 'textarea') : ?>
                    <label><?php echo esc_html($field['label']); ?></label>
                    <textarea name="<?php echo sanitize_title($field['label']); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>"></textarea>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <input type="hidden" name="webform2telegram_form_submitted" value="1">
        <input type="submit" value="Send">
    </form>
    <?php if ($custom_css): ?>
        <style type="text/css">
            <?php echo $custom_css; ?>
        </style>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}




function webform2telegram_handle_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['webform2telegram_form_submitted'])) {
        $options = get_option('webform2telegram_options');
        $chat_id = $options['chat_id'];
        $telegram_token = $options['telegram_token'];
        $message = '';

        foreach ($_POST as $key => $value) {
            if ($key !== 'webform2telegram_form_submitted') {
                $message .= ucfirst($key) . ': ' . sanitize_text_field($value) . "\n";
            }
        }

        $data = [
            'chat_id' => $chat_id,
            'text' => $message
        ];

        $url = "https://api.telegram.org/bot$telegram_token/sendMessage";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code != 200) {
            error_log('Error sending message to Telegram. HTTP Code: ' . $http_code . '. cURL error: ' . $curl_error);
            error_log('Response: ' . $response);
            echo '<p>Error sending the form!</p>';
        } else {
            echo '<p>The form has been sent successfully!</p>';
        }

        wp_redirect(esc_url(home_url(add_query_arg(array(), $wp->request))));
        exit;
    }
}


add_action('wp', 'webform2telegram_handle_form_submission');
