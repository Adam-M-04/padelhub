<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261114110612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Wersja meczu do blokady optymistycznej';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_matches ADD version INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_matches DROP version');
    }
}
