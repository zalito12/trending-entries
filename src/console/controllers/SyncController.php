<?php

namespace ggio\TrendingEntries\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use ggio\TrendingEntries\jobs\CalculateRankingJob;
use ggio\TrendingEntries\Plugin;
use yii\console\ExitCode;

/**
 * Sync Controller
 *
 * This controller handles the background synchronization and processing
 * of trending scores via the Command Line Interface (CLI).
 *
 * It can be executed directly (ideal for ad-hoc runs) or used to push
 * the work into Craft's queue (recommended for production environments).
 *
 * @author Gonzalo García Arce <info@gongarce.io>
 * @copyright Gonzalo García Arce
 * @license MIT
 *
 * @since     1.0.0
 */
class SyncController extends Controller
{
    /**
     * @var bool Whether to push the calculation job to the queue instead of running it directly.
     */
    public bool $queue = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'calculate') {
            $options[] = 'queue';
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), [
            'q' => 'queue',
        ]);
    }

    /**
     * Processes and calculates the trending ranking for provided list keys.
     *
     * This command iterates through the specified list identifiers, calculates
     * the new scores based on the ranking algorithm, and updates the database.
     *
     * Usage:
     * php craft trending-entries/sync/calculate [listKey1] [listKey2] ...
     *
     * Example (direct execution):
     * php craft trending-entries/sync/calculate news recipes blog
     *
     * Example (push to queue):
     * php craft trending-entries/sync/calculate news recipes blog --queue
     *
     * @param  array  $listKeys  Array of list identifiers to process (defaults to ['default']).
     * @return int The exit code (0 for success).
     */
    public function actionCalculate(array $listKeys = ['default']): int
    {
        if ($this->queue) {
            $job = new CalculateRankingJob([
                'listKeys' => $listKeys,
            ]);

            Craft::$app->getQueue()->push($job);

            $this->stdout('Job pushed to queue for lists: '.implode(', ', $listKeys)."\n", Console::FG_GREEN);

            return ExitCode::OK;
        }

        $this->stdout('Processing lists: '.implode(', ', $listKeys)."...\n");

        $result = Plugin::getInstance()->ranking->calculate($listKeys);

        if ($result !== true) {
            $this->stdout("Warning: {$result}\n", Console::FG_YELLOW);
        }

        $this->stdout("Success: All lists updated.\n", Console::FG_GREEN);

        return ExitCode::OK;
    }
}
