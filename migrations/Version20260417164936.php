<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417164936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE line_sale (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, amount INTEGER NOT NULL, unit_price INTEGER NOT NULL, total INTEGER NOT NULL, date_at DATETIME NOT NULL, product_name VARCHAR(255) NOT NULL, product_sku VARCHAR(255) NOT NULL, product_id INTEGER NOT NULL, sale_id INTEGER DEFAULT NULL, CONSTRAINT FK_6E0633DC4584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6E0633DC4A7E4868 FOREIGN KEY (sale_id) REFERENCES sale (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6E0633DC4584665A ON line_sale (product_id)');
        $this->addSql('CREATE INDEX IDX_6E0633DC4A7E4868 ON line_sale (sale_id)');
        $this->addSql('CREATE TABLE product_price (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, price INTEGER NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME DEFAULT NULL, product_id INTEGER NOT NULL, CONSTRAINT FK_6B9459854584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6B9459854584665A ON product_price (product_id)');
        $this->addSql('CREATE TABLE sale (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, total INTEGER NOT NULL, state VARCHAR(255) NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE line_sale');
        $this->addSql('DROP TABLE product_price');
        $this->addSql('DROP TABLE sale');
    }
}
