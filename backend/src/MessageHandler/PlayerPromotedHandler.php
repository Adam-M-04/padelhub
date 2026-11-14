<?php

namespace App\MessageHandler;

use App\Enum\RegistrationStatus;
use App\Message\PlayerPromoted;
use App\Repository\RegistrationRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PlayerPromotedHandler
{
    public function __construct(
        private RegistrationRepository $registrationRepository,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(PlayerPromoted $message): void
    {
        $registration = $this->registrationRepository->find($message->registrationId);

        if ($registration === null || $registration->getStatus() !== RegistrationStatus::REGISTERED) {
            return;
        }

        $email = (new TemplatedEmail())
            ->to($registration->getPlayer()->getEmail())
            ->subject(sprintf('Masz miejsce w turnieju %s', $registration->getTournament()->getName()))
            ->textTemplate('emails/player_promoted.txt.twig')
            ->context([
                'player' => $registration->getPlayer(),
                'tournament' => $registration->getTournament(),
            ]);

        $this->mailer->send($email);
    }
}
