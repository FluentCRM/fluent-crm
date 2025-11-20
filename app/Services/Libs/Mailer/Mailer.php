<?php

namespace FluentCrm\App\Services\Libs\Mailer;

use FluentCrm\Framework\Support\Arr;

class Mailer
{
    public static function send($data, $subscriber = null, $emailModel = null)
    {

        $headers = static::buildHeaders($data, $subscriber, $emailModel);

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

    protected static function buildHeaders($data, $subscriber = null, $emailModel = null)
    {
        $headers[] = "Content-Type: text/html; charset=UTF-8";

        $from = Arr::get($data, 'headers.From');
        $replyTo = Arr::get($data, 'headers.Reply-To');

        if ($from) {
            $headers[] = "From: {$from}";
        }

        // Set Reply-To Header
        if ($replyTo) {
            $headers[] = "Reply-To: {$replyTo}";
        }

        if ($subscriber && apply_filters('fluent_crm/enable_unsub_header', true, $data, $subscriber, $emailModel)) {
            $campaign = ($emailModel && $emailModel->campaign) ? $emailModel->campaign : null;
            $isTransactional = $campaign && Arr::get($campaign->settings, 'is_transactional') == 'yes';

            if (!$isTransactional) {
                $args = [
                    'fluentcrm'   => 1,
                    'route'       => 'unsubscribe',
                    'secure_hash' => fluentCrmGetContactManagedHash($subscriber->id)
                ];
                if ($emailModel) {
                    $args['ce_id'] = $emailModel->id;
                }

                $unsubscribeUrl = add_query_arg($args, site_url('index.php'));

                $headers[] = "List-Unsubscribe: <{$unsubscribeUrl}>";
                $headers[] = "List-Unsubscribe-Post: List-Unsubscribe=One-Click";
            }
        }

        return apply_filters('fluent_crm/email_headers', $headers, $data, $subscriber, $emailModel);
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
