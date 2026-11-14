<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261114104324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabele rounds i game_matches';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE game_matches (id BINARY(16) NOT NULL, team1_score INT DEFAULT NULL, team2_score INT DEFAULT NULL, round_id INT NOT NULL, court_id INT NOT NULL, team1_player1_id BINARY(16) NOT NULL, team1_player2_id BINARY(16) NOT NULL, team2_player1_id BINARY(16) NOT NULL, team2_player2_id BINARY(16) NOT NULL, INDEX IDX_93E46A95A6005CA0 (round_id), INDEX IDX_93E46A95E3184009 (court_id), INDEX IDX_93E46A958981A400 (team1_player1_id), INDEX IDX_93E46A959B340BEE (team1_player2_id), INDEX IDX_93E46A95FE1F76F0 (team2_player1_id), INDEX IDX_93E46A95ECAAD91E (team2_player2_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rounds (id INT AUTO_INCREMENT NOT NULL, number INT NOT NULL, status VARCHAR(20) NOT NULL, tournament_id BINARY(16) NOT NULL, UNIQUE INDEX uniq_rounds_tournament_number (tournament_id, number), INDEX IDX_3A7FD55433D1A3E7 (tournament_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE game_matches ADD CONSTRAINT FK_93E46A95A6005CA0 FOREIGN KEY (round_id) REFERENCES rounds (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_matches ADD CONSTRAINT FK_93E46A95E3184009 FOREIGN KEY (court_id) REFERENCES courts (id)');
        $this->addSql('ALTER TABLE game_matches ADD CONSTRAINT FK_93E46A958981A400 FOREIGN KEY (team1_player1_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE game_matches ADD CONSTRAINT FK_93E46A959B340BEE FOREIGN KEY (team1_player2_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE game_matches ADD CONSTRAINT FK_93E46A95FE1F76F0 FOREIGN KEY (team2_player1_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE game_matches ADD CONSTRAINT FK_93E46A95ECAAD91E FOREIGN KEY (team2_player2_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE rounds ADD CONSTRAINT FK_3A7FD55433D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_matches DROP FOREIGN KEY FK_93E46A95A6005CA0');
        $this->addSql('ALTER TABLE game_matches DROP FOREIGN KEY FK_93E46A95E3184009');
        $this->addSql('ALTER TABLE game_matches DROP FOREIGN KEY FK_93E46A958981A400');
        $this->addSql('ALTER TABLE game_matches DROP FOREIGN KEY FK_93E46A959B340BEE');
        $this->addSql('ALTER TABLE game_matches DROP FOREIGN KEY FK_93E46A95FE1F76F0');
        $this->addSql('ALTER TABLE game_matches DROP FOREIGN KEY FK_93E46A95ECAAD91E');
        $this->addSql('ALTER TABLE rounds DROP FOREIGN KEY FK_3A7FD55433D1A3E7');
        $this->addSql('DROP TABLE game_matches');
        $this->addSql('DROP TABLE rounds');
    }
}
