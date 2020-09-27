<?php

namespace FluentCrm\App\Hooks\Handlers;

class SupportTicketsProviders
{
    public function pushDefaultProviders($providers)
    {
        if (class_exists('\Awesome_Support')) {
            $providers['awesome_support'] = [
                'title' => __('Support Tickets by Awesome Support', 'fluentcrm'),
                'name'  => __('Awesome Support', 'fluentcrm')
            ];
        }
        return $providers;
    }

    public function awesomeSupoortTickets($data, $subscriber)
    {
        if (!$subscriber->user_id || !class_exists('\Awesome_Support')) {
            return $data;
        }

        $app = fluentCrm();
        $page = intval($app->request->get('page', 1));
        $per_page = intval($app->request->get('per_page', 10));

        $args = array(
            'author'                 => $subscriber->user_id,
            'post_type'              => 'ticket',
            'post_status'            => 'any',
            'order'                  => 'DESC',
            'orderby'                => 'date',
            'posts_per_page'         => $per_page,
            'paged'                  => $page,
            'no_found_rows'          => false,
            'cache_results'          => false,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false
        );

        $tickets = new \WP_Query($args);

        $total = $tickets->found_posts;
        $tickets = $tickets->get_posts();


        $formattedTickets = [];
        foreach ($tickets as $ticket) {
            $actionHTML = '<a target="_blank" href="'.get_edit_post_link($ticket).'">View Ticket</a>';
            $formattedTickets[] = [
                'id' => '#'.$ticket->ID,
                'title' => $ticket->post_title,
                'status' => ucfirst(wpas_get_ticket_status( $ticket->ID )),
                'Submitted at' => human_time_diff(strtotime($ticket->post_date_gmt), time()).' ago',
                'action' => $actionHTML
            ];
        }

        return [
            'total' => $total,
            'data'  => $formattedTickets
        ];
    }
}
