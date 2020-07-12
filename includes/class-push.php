<?php

defined('ABSPATH') || exit;

class send_push_notification_class
{

    private $params;
    private $push_api_url = 'https://exp.host/--/api/v2/push/send';
    private $table_recipients;
    private $table_all;
    private $message = [];
    private $recipients = [];
    private $tokens = [];
    private $results = [];
    private $packs = [];
    private $sent = 0;


    /**
     * Constructor
     */
    public function __construct()
    {
        // Set table name
        $this->set_table();
    }

    /**
     * Set post values
     */
    private function set_post_values()
    {
        $params = [];
        $params['title']    = sanitize_text_field($_POST['title']);
        $params['subtitle'] = sanitize_text_field($_POST['subtitle']);
        $params['body']     = sanitize_text_field($_POST['body']);
        $params['screen']   = sanitize_text_field($_POST['screen']);
        $params['draft']    = sanitize_text_field($_POST['draft']);
        $this->params = $params;
    }
    
    /**
     * Validate post
     */
    private function validate_post()
    {
        if (empty($this->params['title']) || empty($this->params['subtitle']) || empty($this->params['body'])) {
            // go back with error
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'error';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('Please, make sure all required fields are completed.', FUSION_WA_SLUG);
            $_SESSION['POST'] = $this->params;
            wp_redirect(wp_get_referer());
            die();
        }
    }

    /**
     * Set table name
     */
    private function set_table()
    {
        global $wpdb;
        $this->table_recipients = $wpdb->prefix . FUSION_WA_T_RECIPIENTS;
        $this->table_all = $wpdb->prefix . FUSION_WA_T_ALL;
    }

    /**
     * Send push
     */
    public function send_push()
    {
        // Validate post data
        $this->set_post_values();

        // Save or Send?
        if($this->params['draft']){
            $this->save_push('draft');
            // Redirect with success message
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'success';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('The push notification has been save to drafts.', FUSION_WA_SLUG);
            wp_redirect(FUSION_WA_URL);
            exit;
        }

        // Validate post data
        $this->validate_post();

        // Set message
        $this->setMessage();

        // Get tokens
        $this->getTokens();

        // Try send
        $this->try_send();
    }

    /**
     * Try send
     *
     */
    private function try_send()
    {
        try {
            // Init loops
            $this->initLoops();

            // Save Push Sent
            $this->save_push();

            // Redirect with success message
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'success';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('Done! A new push notification has successfully been sent.', FUSION_WA_SLUG);
            wp_redirect(FUSION_WA_URL);
            die();
        } catch (Exception $e) {
            // Handle error
            error_log('Error while sending a push: ' . $e->getMessage());
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'error';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('An error occurred... please, try again.', FUSION_WA_SLUG);
            wp_redirect(wp_get_referer());
            die();
        }
    }

    /**
     * Init Loops
     *
     */
    private function initLoops()
    {
        // loop through tokens
        $this->loopThroughTokens();

        // loop through recipients
        $this->loopThroughRecipients();

        // loop through packs
        $this->loopThroughPacks();

        // loop through results
        $this->loopThroughResults();
    }

    /**
     * Loop through results
     *
     */
    private function loopThroughResults()
    {
        foreach ($this->results['data'] as $result) {
            // handle error
            if ($result['status'] == 'error') {
                error_log('Errors sending push: ' . print_r($result));
            } else {
                $this->sent++;
            }
        }
    }

    /**
     * Loop through packs
     *
     */
    private function loopThroughPacks()
    {
        foreach ($this->packs as $pack) {
            array_push($this->results, $this->sendByHundred($pack));
        }
    }

    /**
     * Loop through recipients
     *
     */
    private function loopThroughRecipients()
    {
        foreach ($this->recipients as $key => $tokens) {
            $this->message['to'] = $tokens;
            $this->packs[$key] = $this->message;
        }
    }

    /**
     * Loop through tokens
     *
     */
    private function loopThroughTokens()
    {
        foreach ($this->tokens as $key => $token) {
            // set packs by 100
            $this->recipients[$this->getHundred($key)] = $this->recipients[$this->getHundred($key)] ?? [];
            array_push($this->recipients[$this->getHundred($key)], $token->token);
        }
    }

    /**
     * Get tokens from db
     *
     */
    private function getTokens()
    {
        global $wpdb;
        $this->tokens = $wpdb->get_results("SELECT * FROM $this->table_recipients");
    }

    /**
     * Send push by 100
     *
     */
    private function getHundred($value)
    {
        $value = $value == 0 ? 1 : $value;
        return 'package_' . intval(ceil($value / 100)) . 'x100';
    }


    /**
     * Send push by 100
     *
     */
    private function save_push($status = 'sent')
    {
        global $wpdb;

        $data = array(
            'title'      => $status !== 'sent' ? ($this->params['title'] == '' ? __('_draft', FUSION_WA_SLUG) : $this->params['title']) : $this->params['title'],
            'subtitle'   => $this->params['subtitle'],
            'body'       => $this->params['body'],
            'screen'     => $this->params['screen'],
            'recipients' => $this->sent ?? '',
            'status'     => $status
        );
        $wpdb->insert($this->table_all, $data);
    }



    /**
     * Send push by 100
     *
     */
    private function sendByHundred($body)
    {
        $headers = [
            'host: exp.host',
            'accept: application/json',
            'accept-Encoding: gzip, deflate',
            'content-Type: application/json; charset=utf-8'
        ];
        $args = array(
            'body'        => $body,
            'timeout'     => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => $headers,
            'cookies'     => []
        );
        $response = wp_remote_post($this->push_api_url, $args);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            return false;
        }
        $this->results = json_decode(wp_remote_retrieve_body($response), true);
    }


    /**
     * Construct push message
     */
    private function setMessage()
    {
        $this->message = [
            'title' => $this->params['title'],
            'subtitle' => $this->params['subtitle'],
            'body' => $this->params['body'],
            'priority' => 'default',
            'sound' => 'default',
            'badge' => 1,
            'chandelId' => 'Notifications',
            'to' => []
        ];

        if ($this->params['screen'] ?? '' !== '') {
            $this->message['data'] = [
                'screen' => $this->params['screen']
            ];
        }
    }
}
