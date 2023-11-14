<?php

namespace FluentCrm\App\Services\Libs\Mailer;

use FluentCrm\Framework\Support\Arr;

class Mailer
{
    public static function send($data, $subscriber = null)
    {

        $headers = static::buildHeaders($data, $subscriber);

        if (apply_filters('fluent_crm/is_simulated_mail', false, $data, $headers)) {
            return true;
        }

        $to = $data['to']['email'];

        if (!$to) {
            return false;
        }

        if (self::willIncludeName()) {
            if ($name = Arr::get($data, 'to.name')) {
                $to = $name . ' <' . $to . '>';
            }
        }

        return wp_mail(
            $to,
            $data['subject'],
            $data['body'],
            $headers
        );
    }

    protected static function buildHeaders($data, $subscriber = null)
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

        if (apply_filters('fluent_crm/enable_unsub_header', true, $data)) {
            $sendingEmail = Arr::get($data, 'to.email');

            if ($subscriber) {

                $unsubscribeUrl = add_query_arg([
                    'fluentcrm'   => 1,
                    'route'       => 'unsubscribe',
                    'secure_hash' => fluentCrmGetContactSecureHash($subscriber->id)
                ], site_url('/'));

                $headers[] = 'List-Unsubscribe: <' . $unsubscribeUrl . '>';
            } else if ($sendingEmail) {
                $unsubscribeUrl = add_query_arg([
                    'fluentcrm' => 1,
                    'route'     => 'unsubscribe',
                    'hash'      => md5($sendingEmail)
                ], site_url('/'));
                $headers[] = 'List-Unsubscribe: <' . $unsubscribeUrl . '>';
            }

        }

        return apply_filters('fluent_crm/email_headers', $headers, $data);
    }

    private static function willIncludeName()
    {
        static $status = null;
        if ($status !== null) {
            return $status;
        }
        $status = apply_filters('fluent_crm/enable_mailer_to_name', true);
        return $status;
    }
}
