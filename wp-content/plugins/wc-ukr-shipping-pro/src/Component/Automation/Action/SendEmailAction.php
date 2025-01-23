<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Action;

use kirillbdev\WCUkrShipping\Component\Automation\Context;

if ( ! defined('ABSPATH')) {
    exit;
}

class SendEmailAction implements ActionInterface
{
    private string $subject;
    private string $message;
    private string $sendTo;

    public function __construct(string $subject, string $message, string $sendTo)
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->sendTo = $sendTo;
    }

    public function execute(Context $context): void
    {
        $order = wcus_wrap_order($context->getOrder());
        $compiled = str_replace(
            [
                '{{ttn_number}}',
                '{{ttn_status_code}}',
                '{{order_id}}',
                '{{billing_firstname}}',
                '{{billing_lastname}}',
                '{{city}}',
                '{{address}}',
            ],
            [
                $context->getTtn()['ttn_id'],
                $context->getTtn()['status_code'],
                $order->getOrigin()->get_id(),
                $order->getOrigin()->get_billing_first_name(),
                $order->getOrigin()->get_billing_last_name(),
                $order->getCity(),
                $order->getAddress1(),
            ],
            $this->message
        );

        if ($this->sendTo === 'customer') {
            wc_mail($order->getOrigin()->get_billing_email(), $this->subject, $compiled);
        } elseif ($this->sendTo === 'admin') {
            $adminEmail = get_option('admin_email');
            if ($adminEmail) {
                wc_mail($adminEmail, $this->subject, $compiled);
            }
        }
    }
}
