<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260214190150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE widgets DROP CONSTRAINT fk_9d58e4c1b9d04d2b');
        $this->addSql('ALTER TABLE widgets ALTER type TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE widgets ALTER updated_at DROP NOT NULL');
        $this->addSql('ALTER TABLE widgets ADD CONSTRAINT FK_9D58E4C1B9D04D2B FOREIGN KEY (dashboard_id) REFERENCES dashboards (id) NOT DEFERRABLE');
        $this->addSql('ALTER INDEX unique_dashboard_position RENAME TO unique_position');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE widgets DROP CONSTRAINT FK_9D58E4C1B9D04D2B');
        $this->addSql('ALTER TABLE widgets ALTER type TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE widgets ALTER updated_at SET NOT NULL');
        $this->addSql('ALTER TABLE widgets ADD CONSTRAINT fk_9d58e4c1b9d04d2b FOREIGN KEY (dashboard_id) REFERENCES dashboards (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX unique_position RENAME TO unique_dashboard_position');
    }
}
