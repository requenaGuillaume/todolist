<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20231014212450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO user (username, password, email, roles)
            VALUES ('anonyme', 'invalidPasswordBecauseNotHashed', 'fake-email@fake.fake', '[\"ROLE_ADMIN\"]')"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM user WHERE username = "anonyme"');
    }
}
