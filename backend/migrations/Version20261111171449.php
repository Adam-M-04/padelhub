<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261111171449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabela courts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE courts (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, active TINYINT NOT NULL, UNIQUE INDEX uniq_courts_name (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE courts');
    }
}
