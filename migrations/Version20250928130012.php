<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250928130012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema for the entire database';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE config (keyname VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, PRIMARY KEY(keyname)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE teams (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, slug VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, UNIQUE INDEX unique_slug (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE events (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, team_id INT UNSIGNED DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, slug VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, website VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, twitter VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, twitch VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, theme VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, secret VARCHAR(20) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, max_schedules INT UNSIGNED NOT NULL, INDEX IDX_5387574A296CD8AE (team_id), INDEX IDX_5387574AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE users_teams (user_id INT UNSIGNED NOT NULL, team_id INT UNSIGNED NOT NULL, role VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, INDEX IDX_71B58611A76ED395 (user_id), INDEX IDX_71B58611296CD8AE (team_id), PRIMARY KEY(user_id, team_id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE schedule_columns (id INT UNSIGNED AUTO_INCREMENT NOT NULL, schedule_id INT UNSIGNED NOT NULL, position INT UNSIGNED DEFAULT 0 NOT NULL, hidden TINYINT(1) DEFAULT 0 NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, INDEX IDX_21A4EC06A40BC2D5 (schedule_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE sessions (id VARBINARY(128) NOT NULL, data BLOB NOT NULL, lifetime INT NOT NULL, mtime INT UNSIGNED NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE schedule_items (id INT UNSIGNED AUTO_INCREMENT NOT NULL, schedule_id INT UNSIGNED NOT NULL, position SMALLINT UNSIGNED NOT NULL, length TIME NOT NULL, extra LONGTEXT CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, INDEX IDX_1D472911A40BC2D5 (schedule_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT NOT NULL, login VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, password VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, display_name VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, gravatar_hash VARCHAR(32) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, language VARCHAR(10) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, role VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, twitch_oauth VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, created_at DATETIME NOT NULL, max_events INT UNSIGNED NOT NULL, UNIQUE INDEX unique_login (login), UNIQUE INDEX unique_twitch (twitch_oauth), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE schedules (id INT UNSIGNED AUTO_INCREMENT NOT NULL, event_id INT UNSIGNED NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, slug VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, timezone VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, start DATETIME NOT NULL, website VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, twitter VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, twitch VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, theme VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, secret VARCHAR(20) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, hidden_secret VARCHAR(20) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, setup_time TIME DEFAULT NULL, max_items INT UNSIGNED NOT NULL, updated_at DATETIME NOT NULL, extra LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, INDEX IDX_313BDC8E71F7E88B (event_id), UNIQUE INDEX unique_slug (event_id, slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = ''
        SQL);

        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE users_teams ADD CONSTRAINT FK_71B58611A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE;
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE users_teams ADD CONSTRAINT FK_71B58611296CD8AE FOREIGN KEY (team_id) REFERENCES users (id) ON DELETE CASCADE;
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE events ADD CONSTRAINT FK_5387574AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE;
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE events ADD CONSTRAINT FK_5387574A296CD8AE FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE CASCADE;
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE schedules ADD CONSTRAINT FK_313BDC8E71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE;
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE schedule_items ADD CONSTRAINT FK_1D472911A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES schedules (id) ON DELETE CASCADE;
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE schedule_columns ADD CONSTRAINT FK_21A4EC06A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES schedules (id) ON DELETE CASCADE;
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            DROP TABLE config
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            DROP TABLE teams
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            DROP TABLE events
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            DROP TABLE users_teams
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            DROP TABLE schedule_columns
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            DROP TABLE sessions
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            DROP TABLE schedule_items
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            DROP TABLE users
        SQL);
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1010Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1010Platform'."
        );

        $this->addSql(<<<'SQL'
            DROP TABLE schedules
        SQL);
    }
}
