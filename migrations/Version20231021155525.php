<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20231021155525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set user_id not nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task MODIFY user_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task MODIFY user_id INT DEFAULT NULL');        
    }
}
