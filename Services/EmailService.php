<?php

namespace JulienIts\EmailsQueueBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use JulienIts\EmailsQueueBundle\Entity\EmailContext;
use Symfony\Component\Routing\RouterInterface;

class EmailService
{
    protected EntityManagerInterface $em;
    protected RouterInterface $router;
    protected \Twig\Environment $twig;
    protected EmailsQueueService $emailsQueueService;

    public function __construct(
        EntityManagerInterface $em,
        RouterInterface $router,
        \Twig\Environment $twig,
        EmailsQueueService $emailsQueueService
    )
    {
        $this->em = $em;
		$this->router = $router;
		$this->twig = $twig;
        $this->emailsQueueService = $emailsQueueService;
    }

    public function createNewAndProcess($config)
    {
        $this->createNew($config);
        $this->emailsQueueService->processQueue(1);
    }

	public function createNew($config)
	{
        if(key_exists('emailHtml', $config)){
            $emailHtml = $config['emailHtml'];
        }else{
            $tpl = $this->twig->load($config['template']);
            $emailHtml = $tpl->render($config['templateVars']);
        }

		$emailQueue = new \JulienIts\EmailsQueueBundle\Entity\EmailQueue();
		$emailQueue->setBody($emailHtml);
		$emailQueue->setContext($this->em->getRepository(EmailContext::class)->findOneByName($config['contextName']));
		$emailQueue->setEmailFrom($config['emailFrom']);
		$emailQueue->setEmailFromName($config['emailFromName']);
		$emailQueue->setEmailTo($config['emailTo']);
        if(isset($config['emailsCc'])){
            $emailQueue->setEmailsCc($config['emailsCc']);
        }
        if(isset($config['emailsBcc'])){
            $emailQueue->setEmailsBcc($config['emailsBcc']);
        }
        if(isset($config['replyTo'])){
            $emailQueue->setReplyTo($config['replyTo']);
        }

		$emailQueue->setPriority($config['priority']);
		$emailQueue->setSubject($config['subject']);
        $emailQueue->setCreatedOn(new \DateTime());

        // Add body text
		if(isset($config['templateText'])){
			$tplText = $this->twig->load($config['templateText']);
			$emailText = $tplText->render($config['templateVars']);
			$emailQueue->setBodyText($emailText);
		}

		$this->em->persist($emailQueue);
		$this->em->flush();
	}
}
