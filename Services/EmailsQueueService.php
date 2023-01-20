<?php
namespace JulienIts\EmailsQueueBundle\Services;

use JulienIts\EmailsQueueBundle\Entity\EmailQueue;
use JulienIts\EmailsQueueBundle\Entity\EmailSent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;


class EmailsQueueService
{
    //const WHITE_LIST_ENABLE = false;
    protected EntityManagerInterface $em;
    protected MailerInterface $mailer;

    public function __construct(EntityManagerInterface $em, MailerInterface $mailer)
    {
        $this->em = $em;
        $this->mailer = $mailer;
    }

    public function processQueue($limit=15)
    {
        $queueRepo = $this->em->getRepository(EmailQueue::class);
        $emailsQueue = $queueRepo->findBy(array(), array('priority'=>'desc', 'id'=>'desc'), $limit);
        foreach($emailsQueue as $emailQueue){
            $this->_sendEmailQueue($emailQueue);
            $this->_setEmailQueueToSent($emailQueue);
        }
    }

    private function _sendEmailQueue(EmailQueue $emailQueue)
    {
        $message = new Email();
        $to = $emailQueue->getEmailTo();

        if($emailQueue->getReplyTo() != null && $emailQueue->getReplyTo() != ''){
            $message->addReplyTo($emailQueue->getReplyTo());
        }

        $message->subject($emailQueue->getSubject())
            ->from(new Address($emailQueue->getEmailFrom(), $emailQueue->getEmailFromName()))
            ->to($to)
            ->html($emailQueue->getBody());

        if($emailQueue->getBodyText() != null){
            $message->text($emailQueue->getBodyText(),'text/plain');
        }

        // Add CC from the emailQueue entity
        if($emailQueue->getEmailsCc() != null){
            $arrEmails = explode(';', $emailQueue->getEmailsCc());
            foreach($arrEmails as $email){
                $email = trim($email);
                if($email == $to){
                    continue;
                }
                $message->addCc($email);
            }
        }

        // Add BCC from the emailQueue entity
        if($emailQueue->getEmailsBcc() != null){
            $arrEmails = explode(';', $emailQueue->getEmailsBcc());
            foreach($arrEmails as $email){
                $email = trim($email);
                if($email == $to){
                    continue;
                }
                $message->addBcc($email);
            }
        }

        $this->mailer->send($message);
    }

    private function _setEmailQueueToSent(EmailQueue $emailQueue)
    {
        $emailSent = new EmailSent();

        $emailSent->setPriority($emailQueue->getPriority());
        $emailSent->setEmailFrom($emailQueue->getEmailFrom());
        $emailSent->setEmailFromName($emailQueue->getEmailFromName());
        $emailSent->setEmailTo($emailQueue->getEmailTo());
        $emailSent->setSubject($emailQueue->getSubject());
        $emailSent->setBody($emailQueue->getBody());
        $emailSent->setBodyText($emailQueue->getBodyText());
        $emailSent->setCreatedOn($emailQueue->getCreatedOn());
        $emailSent->setContext($emailQueue->getContext());
        $emailSent->setEmailsBcc($emailQueue->getEmailsBcc());
        $emailSent->setEmailsCc($emailQueue->getEmailsCc());
        $emailSent->setReplyTo($emailQueue->getReplyTo());

        $this->em->persist($emailSent);
        $this->em->remove($emailQueue);
        $this->em->flush();
    }
}
