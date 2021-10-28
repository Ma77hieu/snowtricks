<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211028142326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment ADD trick_id INT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD author_id INT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CB281BE2E FOREIGN KEY (trick_id) REFERENCES trick (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_9474526CB281BE2E ON comment (trick_id)');
        $this->addSql('CREATE INDEX IDX_9474526CF675F31B ON comment (author_id)');
        $this->addSql('ALTER TABLE media ADD media_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE media ADD trick_id INT NOT NULL');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CA49B0ADA FOREIGN KEY (media_type_id) REFERENCES media_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CB281BE2E FOREIGN KEY (trick_id) REFERENCES trick (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6A2CA10CA49B0ADA ON media (media_type_id)');
        $this->addSql('CREATE INDEX IDX_6A2CA10CB281BE2E ON media (trick_id)');
        $this->addSql('ALTER TABLE trick ADD trick_group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trick ADD name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE trick ADD CONSTRAINT FK_D8F0A91E9B875DF8 FOREIGN KEY (trick_group_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D8F0A91E9B875DF8 ON trick (trick_group_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526CB281BE2E');
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526CF675F31B');
        $this->addSql('DROP INDEX IDX_9474526CB281BE2E');
        $this->addSql('DROP INDEX IDX_9474526CF675F31B');
        $this->addSql('ALTER TABLE comment DROP trick_id');
        $this->addSql('ALTER TABLE comment DROP author_id');
        $this->addSql('ALTER TABLE media DROP CONSTRAINT FK_6A2CA10CA49B0ADA');
        $this->addSql('ALTER TABLE media DROP CONSTRAINT FK_6A2CA10CB281BE2E');
        $this->addSql('DROP INDEX IDX_6A2CA10CA49B0ADA');
        $this->addSql('DROP INDEX IDX_6A2CA10CB281BE2E');
        $this->addSql('ALTER TABLE media DROP media_type_id');
        $this->addSql('ALTER TABLE media DROP trick_id');
        $this->addSql('ALTER TABLE trick DROP CONSTRAINT FK_D8F0A91E9B875DF8');
        $this->addSql('DROP INDEX IDX_D8F0A91E9B875DF8');
        $this->addSql('ALTER TABLE trick DROP trick_group_id');
        $this->addSql('ALTER TABLE trick DROP name');
    }
}
