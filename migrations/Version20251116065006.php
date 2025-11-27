<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251116065006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add authentication fields to users table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD password VARCHAR(255) DEFAULT \'temp_password\'');
        $this->addSql('ALTER TABLE users ADD roles JSON DEFAULT \'["ROLE_USER"]\'');

        $this->addSql('UPDATE users SET password = DEFAULT, roles = DEFAULT');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP password');
        $this->addSql('ALTER TABLE users DROP roles');

    }
}
