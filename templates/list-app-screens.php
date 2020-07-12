<div class="wrap">
    <h2><?php esc_html_e('Screens', FUSION_WA_SLUG); ?>&nbsp;&nbsp;
        <a class="button button-primary" href="<?php echo esc_url(FUSION_WA_URL); ?>-register_new_app_screen"><strong><?php esc_html_e('Register New Screen', FUSION_WA_SLUG); ?></strong></a>
        <a class="button button-secondary" href="<?php echo esc_url(FUSION_WA_URL); ?>-send_new"><strong><?php esc_html_e('Send a New Push', FUSION_WA_SLUG); ?></strong></a>
    </h2>
    <p><?php esc_html_e('Here you can see the list of screens registered for your App. You can later use these to redirect your users when they open a new push notification, but keep in mind that the route should be exactly as it looks in your App for this to be able to work properly.', FUSION_WA_SLUG); ?></p>
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