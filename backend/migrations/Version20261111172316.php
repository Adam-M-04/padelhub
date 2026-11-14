<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261111172316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabele tournaments i tournament_courts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tournaments (id BINARY(16) NOT NULL, name VARCHAR(150) NOT NULL, format VARCHAR(20) NOT NULL, starts_at DATETIME NOT NULL, points_per_match INT NOT NULL, max_players INT NOT NULL, status VARCHAR(20) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tournament_courts (tournament_id BINARY(16) NOT NULL, court_id INT NOT NULL, INDEX IDX_C9A1B27F33D1A3E7 (tournament_id), INDEX IDX_C9A1B27FE3184009 (court_id), PRIMARY KEY (tournament_id, court_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE tournament_courts ADD CONSTRAINT FK_C9A1B27F33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_courts ADD CONSTRAINT FK_C9A1B27FE3184009 FOREIGN KEY (court_id) REFERENCES courts (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament_courts DROP FOREIGN KEY FK_C9A1B27F33D1A3E7');
        $this->addSql('ALTER TABLE tournament_courts DROP FOREIGN KEY FK_C9A1B27FE3184009');
        $this->addSql('DROP TABLE tournaments');
        $this->addSql('DROP TABLE tournament_courts');
    }
}
