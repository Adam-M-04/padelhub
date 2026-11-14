<?php

namespace App\MessageHandler;

use App\Message\RoundCreated;
use App\Repository\RoundRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RoundCreatedHandler
{
    public function __construct(
        private RoundRepository $roundRepository,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(RoundCreated $message): void
    {
        $round = $this->roundRepository->find($message->roundId);

        if ($round === null) {
            return;
        }

        foreach ($round->getMatches() as $match) {
            foreach ([[$match->getTeam1(), $match->getTeam2()], [$match->getTeam2(), $match->getTeam1()]] as [$team, $opponents]) {
                foreach ($team as $index => $player) {
                    $email = (new TemplatedEmail())
                        ->to($player->getEmail())
                        ->subject(sprintf('Runda %d – %s', $round->getNumber(), $round->getTournament()->getName()))
                        ->textTemplate('emails/round_created.txt.twig')
                        ->context([
                            'player' => $player,
                            'partner' => $team[1 - $index],
                            'opponents' => $opponents,
                            'court' => $match->getCourt(),
                            'round' => $round,
                            'tournament' => $round->getTournament(),
                        ]);

                    $this->mailer->send($email);
                }
            }
        }
    }
}
