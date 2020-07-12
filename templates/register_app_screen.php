<div class="wrap">
    <h2><?php esc_html_e('Register App Screen', FUSION_WA_SLUG); ?>&nbsp;&nbsp;
        <a class="button button-secondary" href="<?php echo esc_url(FUSION_WA_URL); ?>-send_new"><strong><?php esc_html_e('Send a New Push', FUSION_WA_SLUG); ?></strong></a>
    </h2>
    <div id="icon-themes" class="icon32"></div>
    <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
        <?php
        settings_fields('register_screen_fields_form');
        do_settings_sections('register_screen_fields_form');
        ?>
        <input type="hidden" name="id" value="<?php echo esc_attr($this->item) ?? ''; ?>">
        <input type="hidden" name="action" value="<?php echo ($this->action ?? '') == 'edit-app-screen' ? esc_attr('register_edit_app_screen') : esc_attr('register_new_app_screen') ?>">
        <?php submit_button(); ?>
    </form>
</div>