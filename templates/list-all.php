<div class="wrap">
    <h2><?php esc_html_e('Push Notifications Sent', FUSION_WA_SLUG); ?>&nbsp;&nbsp;
        <a class="button button-primary" href="<?php echo esc_url(FUSION_WA_URL); ?>-send_new"><strong><?php esc_html_e('Send a New Push', FUSION_WA_SLUG); ?></strong></a>
        <a class="button button-secondary" href="<?php echo esc_url(FUSION_WA_URL); ?>-register_new_app_screen"><strong><?php esc_html_e('Register New Screen', FUSION_WA_SLUG); ?></strong></a>
    </h2>
    <p><strong><?php esc_html_e('Important', FUSION_WA_SLUG); ?>: </strong><br><?php esc_html_e('Here you can find all notifications that has been sent. This does not mean that all the recipients have indeed received the push notification since that depends on various external factors. For example, have the users given permission to your App? Sadly, we cannot verify that, but we can be sure that these notifications have been sent out.', FUSION_WA_SLUG); ?></p>
    <?php $this->list->views(); ?>
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <form method="post">
                        <?php $this->list->display(); ?>
                    </form>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
</div>