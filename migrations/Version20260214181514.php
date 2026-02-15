<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260214181514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename column to widget_column to avoid SQL reserved word';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE widgets RENAME COLUMN "column" TO widget_column');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE widgets RENAME COLUMN widget_column TO "column"');
    }
}