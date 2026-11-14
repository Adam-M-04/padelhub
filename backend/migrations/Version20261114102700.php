<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261114102700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabela registrations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE registrations (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, registered_at DATETIME NOT NULL, tournament_id BINARY(16) NOT NULL, player_id BINARY(16) NOT NULL, UNIQUE INDEX uniq_registrations_tournament_player (tournament_id, player_id), INDEX IDX_53DE51E733D1A3E7 (tournament_id), INDEX IDX_53DE51E799E6F5DF (player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE registrations ADD CONSTRAINT FK_53DE51E733D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE registrations ADD CONSTRAINT FK_53DE51E799E6F5DF FOREIGN KEY (player_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE registrations DROP FOREIGN KEY FK_53DE51E733D1A3E7');
        $this->addSql('ALTER TABLE registrations DROP FOREIGN KEY FK_53DE51E799E6F5DF');
        $this->addSql('DROP TABLE registrations');
    }
}
