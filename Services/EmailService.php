<?php

namespace JulienIts\EmailsQueueBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use JulienIts\EmailsQueueBundle\Entity\EmailContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EmailService
{
    protected EntityManagerInterface $em;
    protected RouterInterface $router;
    protected \Twig\Environment $twig;
    protected EmailsQueueService $emailsQueueService;
    protected ParameterBagInterface $param;

    public function __construct(
        EntityManagerInterface $em,
        RouterInterface $router,
        \Twig\Environment $twig,
        EmailsQueueService $emailsQueueService,
        ParameterBagInterface $param
    )
    {
        $this->em = $em;
		$this->router = $router;
		$this->twig = $twig;
        $this->emailsQueueService = $emailsQueueService;
        $this->param = $param;
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

        $context = $this->em->getRepository(EmailContext::class)->findOneByName($config['contextName']);
        if(!$context){
            $context = (new EmailContext())->setName($config['contextName']);
            $this->em->persist($context);
        }

        $emailQueue->setBody($emailHtml);
        $emailQueue->setContext($context);
        $emailQueue->setEmailFrom($config['emailFrom']);
        $emailQueue->setEmailFromName($config['emailFromName']);
        if(isset($config['replyTo'])){
            $emailQueue->setReplyTo($config['replyTo']);
        }

        if($this->param->get('emails_queue.mode') == 'prod'){
            $emailQueue->setEmailTo($config['emailTo']);
            if(isset($config['emailsCc'])){
                $emailQueue->setEmailsCc($config['emailsCc']);
            }
            if(isset($config['emailsBcc'])){
                $emailQueue->setEmailsBcc($config['emailsBcc']);
            }
        }else{
            if(!empty($this->param->get('emails_queue.debug_to'))){
                $emailQueue->setEmailTo($this->param->get('emails_queue.debug_to'));
            }else{
                $emailQueue->setEmailTo('info@'.gethostname());
            }
            if(!empty($this->param->get('emails_queue.debug_cc'))){
                $emailQueue->setEmailsCc($this->param->get('emails_queue.debug_cc'));
            }
            $body = $emailQueue->getBody() ;
            $body .= PHP_EOL . PHP_EOL . PHP_EOL
                . '[DEBUG] Ce message a été envoyé a '. $config['emailTo'];
            $emailQueue->setBody($body);
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
