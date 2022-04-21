<?php

namespace JulienIts\EmailsQueueBundle\Controller;

use JulienIts\EmailsQueueBundle\Services\EmailsQueueService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CronController extends Controller
{
    /**
	 * @Route("/emails-queue/cron/process-mail-queue", name="emails_queue_cron_process_mail_queue")
	 */
    public function processMailQueueAction(EmailsQueueService $service)
	{
        $service->processQueue();
        die('Sent');
    }
}
