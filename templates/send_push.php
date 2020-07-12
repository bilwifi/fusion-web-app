<div class="wrap">
    <h2><?php esc_html_e('Push Notifications', FUSION_WA_SLUG); ?>&nbsp;&nbsp;
        <a class="button button-secondary" href="<?php echo esc_url(FUSION_WA_URL); ?>-register_new_app_screen"><strong><?php esc_html_e('Register Screen', FUSION_WA_SLUG); ?></strong></a>
    </h2>
    <div id="icon-themes" class="icon32"></div>
    <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" id="send_push_form">
        <?php
        settings_fields('send_push_fields_form');
        do_settings_sections('send_push_fields_form');
        ?>
        <input type="hidden" name="action" value="send_new_push">
        <input type="hidden" name="draft" value="0" id="is_draft">
        <?php submit_button(__('Send Now', FUSION_WA_SLUG), 'primary', 'submit_form', false); ?>
        <button class="button button-secondary" type="button" onclick="save_as_draft('send_push_form')"><?php _e('Save as draft', FUSION_WA_SLUG) ?></button>
    </form>
</div>