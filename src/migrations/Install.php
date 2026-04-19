<?php

namespace ggio\TrendingEntries\migrations;

use craft\db\Migration;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): bool
    {
        $this->createTable('{{%trending_entries_scores}}', [
            'id' => $this->primaryKey(),
            'entryId' => $this->integer()->notNull(),
            'listKey' => $this->string()->defaultValue('default')->notNull(),
            'score' => $this->float()->defaultValue(0)->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);

        // Índices para que la lectura sea instantánea
        $this->createIndex(null, '{{%trending_entries_scores}}', ['entryId', 'listKey'], true);
        $this->createIndex(null, '{{%trending_entries_scores}}', ['listKey', 'score']);

        // FK para asegurar integridad (opcional, pero recomendado)
        $this->addForeignKey(null, '{{%trending_entries_scores}}', 'entryId', '{{%elements}}', 'id', 'CASCADE');

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): bool
    {
        $this->dropTable('{{%trending_entries_scores}}');

        return true;
    }
}
