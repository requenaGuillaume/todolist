<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20231014212450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the anonymous user (existant tasks will belong to this user)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO user (username, password, email, roles)
            VALUES ('anonyme', 'invalidPasswordBecauseNotHashed', 'anonymous@unexistant.dummy', '[\"ROLE_ADMIN\"]')"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM user WHERE username = "anonyme"');
    }
}
