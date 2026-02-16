<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20260215203456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add roles and is_active columns to users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD roles JSON NOT NULL DEFAULT \'["ROLE_USER"]\'');
        $this->addSql('ALTER TABLE users ADD is_active BOOLEAN NOT NULL DEFAULT true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP roles');
        $this->addSql('ALTER TABLE users DROP is_active');
    }
}