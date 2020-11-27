<?php

namespace FluentCrm\Includes\Mailer;

use FluentCrm\Includes\Helpers\Arr;

class Mailer
{
    public static function send($data)
    {
        $headers = static::buildHeaders($data);

        if (apply_filters('fluentcrm_is_simulated_mail', false, $data, $headers)) {
            return true;
        }

        return wp_mail(
            $data['to']['email'],
            $data['subject'],
            $data['body'],
            $headers
        );
    }

    protected static function buildHeaders($data)
    {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        $from = Arr::get($data, 'headers.From');
        $replyTo = Arr::get($data, 'headers.Reply-To');

        if ($from) {
            $headers[] = "From: {$from}";
        }

        // Set Reply-To Header
        if ($replyTo) {
            $headers[] = "Reply-To: $replyTo";
        }

        if (apply_filters('fluencrm_enable_unsub_header', true, $data)) {
            $sendingEmail = Arr::get($data, 'to.email');
            if ($sendingEmail) {
                $unsubscribeUrl = add_query_arg([
                    'fluentcrm' => 1,
                    'route'     => 'unsubscribe',
                    'hash'      => md5($sendingEmail)
                ], site_url('/'));
                $headers[] ='List-Unsubscribe: <'.$unsubscribeUrl.'>';
            }
        }

        return apply_filters('fluentcrm_email_headers', $headers, $data);
    }
}
