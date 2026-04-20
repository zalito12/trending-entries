<?php

namespace ggio\TrendingEntries\variables;

use craft\elements\db\EntryQuery;
use craft\elements\Entry;

class TrendingEntriesVariable
{
    /**
     * Returns an EntryQuery ordered by the calculated trending score.
     *
     * @param  string  $listKey  The list identifier defined during increment/sync.
     */
    public function get(string $listKey = 'default'): EntryQuery
    {
        // Start a standard Entry query
        $query = Entry::find();

        // Join with our custom scores table
        $query->innerJoin(
            '{{%trending_entries_scores}} scores',
            ['and', '[[scores.entryId]] = [[elements.id]]', ['scores.listKey' => $listKey]]
        );

        $query->orderBy(['scores.score' => SORT_DESC]);

        return $query;
    }
}
