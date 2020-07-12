<div class="wrap">
    <h2><?php esc_html_e('All Recipients', FUSION_WA_SLUG); ?>&nbsp;&nbsp;
        <a class="button button-primary" href="<?php echo esc_url(FUSION_WA_URL); ?>-send_new"><strong><?php esc_html_e('Send a New Push', FUSION_WA_SLUG); ?></strong></a>
        <a class="button button-secondary" href="<?php echo esc_url(FUSION_WA_URL); ?>-register_new_app_screen"><strong><?php esc_html_e('Register New Screen', FUSION_WA_SLUG); ?></strong></a>
    </h2>
    <h3><?php esc_html_e('Recipients', FUSION_WA_SLUG); ?></h3>
    <p><?php esc_html_e('Here you can see a list of all recipients registered from your App. Keep in mind that the delivery of push notifications is only posible for those who have granted the permission on their devices.', FUSION_WA_SLUG); ?></p>
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