<?php

namespace ggio\TrendingEntries\console\controllers;

use craft\console\Controller;
use craft\helpers\Console;
use ggio\TrendingEntries\Plugin;
use yii\console\ExitCode;

/**
 * Sync Controller
 *
 * This controller handles the background synchronization and processing
 * of trending scores via the Command Line Interface (CLI).
 *
 * It is intended to be executed periodically via a Cron Job or a
 * Scheduled Task to update the database rankings based on cached engagement data.
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
     * Processes and calculates the trending ranking for provided list keys.
     * * This command iterates through the specified list identifiers, calculates
     * the new scores based on the ranking algorithm, and updates the database.
     *
     * Usage:
     * php craft trending-entries/sync/calculate [listKey1] [listKey2] ...
     * * Example:
     * php craft trending-entries/sync/calculate news recipes blog
     *
     * @param  array  $listKeys  Array of list identifiers to process (defaults to ['default']).
     * @return int The exit code (0 for success).
     */
    public function actionCalculate(array $listKeys = ['default']): int
    {
        $this->stdout('Processing lists: '.implode(', ', $listKeys)."...\n");

        $result = Plugin::getInstance()->ranking->calculate($listKeys);

        if ($result !== true) {
            $this->stdout("Warning: {$result}\n", Console::FG_YELLOW);
        }

        $this->stdout("Success: All lists updated.\n", Console::FG_GREEN);

        return ExitCode::OK;
    }
}
