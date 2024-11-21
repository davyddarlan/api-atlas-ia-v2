<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220718193625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE marcador_imagem_multimidia (marcador_imagem_id INT NOT NULL, multimidia_id INT NOT NULL, INDEX IDX_7245C9A06532C7A5 (marcador_imagem_id), INDEX IDX_7245C9A0E7F374CC (multimidia_id), PRIMARY KEY(marcador_imagem_id, multimidia_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE marcador_imagem_multimidia ADD CONSTRAINT FK_7245C9A06532C7A5 FOREIGN KEY (marcador_imagem_id) REFERENCES marcador_imagem (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marcador_imagem_multimidia ADD CONSTRAINT FK_7245C9A0E7F374CC FOREIGN KEY (multimidia_id) REFERENCES multimidia (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE marcador_imagem_multimidia');
    }
}
