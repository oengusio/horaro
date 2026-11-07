<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107084727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Map bootswatch 3 themes to bootswatch 4';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            UPDATE events SET theme = 'sketchy' WHERE theme = 'amelia';
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE events SET theme = 'cosmo' WHERE theme = 'paper';
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE events SET theme = 'journal' WHERE theme = 'readable';
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE schedules SET theme = 'sketchy' WHERE theme = 'amelia';
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE schedules SET theme = 'cosmo' WHERE theme = 'paper';
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE schedules SET theme = 'journal' WHERE theme = 'readable';
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            UPDATE events SET theme = 'amelia' WHERE theme = 'sketchy';
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE events SET theme = 'paper' WHERE theme = 'cosmo';
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE events SET theme = 'readable' WHERE theme = 'journal';
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE schedules SET theme = 'amelia' WHERE theme = 'sketchy';
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE schedules SET theme = 'paper' WHERE theme = 'cosmo';
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE schedules SET theme = 'readable' WHERE theme = 'journal';
        SQL);
    }
}
