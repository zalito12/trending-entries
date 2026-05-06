<?php

namespace ggio\TrendingEntries\jobs;

use Craft;
use craft\queue\BaseJob;
use ggio\TrendingEntries\Plugin;

/**
 * Calculate Ranking Queue Job
 *
 * Pushes the trending score calculation into Craft's queue system,
 * preventing CLI timeouts and allowing retries on failure.
 *
 * @author Gonzalo García Arce <info@gongarce.io>
 * @copyright Gonzalo García Arce
 * @license MIT
 *
 * @since 1.1.0
 */
class CalculateRankingJob extends BaseJob
{
    /**
     * @var string|array List key(s) to process.
     */
    public string|array $listKeys = 'default';

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $listKeys = is_array($this->listKeys) ? $this->listKeys : [$this->listKeys];
        $total = count($listKeys);

        foreach ($listKeys as $index => $listKey) {
            $this->setProgress($queue, $index / $total, "Processing list: {$listKey}");

            $result = Plugin::getInstance()->ranking->calculate($listKey);

            if ($result !== true) {
                Craft::warning("TrendingEntries calculation failed for list '{$listKey}': {$result}", __METHOD__);
                throw new \Exception($result);
            }
        }

        $this->setProgress($queue, 1, 'All lists updated.');
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        $keys = is_array($this->listKeys) ? implode(', ', $this->listKeys) : $this->listKeys;

        return Craft::t('trending-entries', 'Calculating trending ranking for: {lists}', [
            'lists' => $keys,
        ]);
    }
}
