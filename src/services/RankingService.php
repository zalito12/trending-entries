<?php

namespace ggio\TrendingEntries\services;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use craft\helpers\App;
use craft\helpers\Db;
use ggio\TrendingEntries\Plugin;

/**
 * RankingService Service
 *
 * This service handles the business logic for calculating trending scores.
 * It uses a decay algorithm based on views and time passed since publication.
 *
 * The score is calculated using the following formula:
 * Score = (Views - 1)^power / (AgeInHours + 2)^gravity
 *
 * @author Gonzalo García Arce <info@gongarce.io>
 * @copyright Gonzalo García Arce
 * @license MIT
 *
 * @since     1.0.0
 */
class RankingService extends Component
{
    /**
     * @var int Number of days to look back for entries
     */
    private int $_timeWindowDays;

    /**
     * @var float The decay gravity (higher = faster decay)
     */
    private float $_gravity;

    /**
     * @var float The weight power for views (dampens huge traffic spikes)
     */
    private float $_power;

    /**
     * @var array Section handles of entries to compute score for
     */
    private array $_sections;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        // Load configuration from environment variables
        $this->_timeWindowDays = (int) (App::env('TRENDING_WINDOW_DAYS') ?: 7);
        $this->_gravity = (float) (App::env('TRENDING_GRAVITY') ?: 1.2);
        $this->_power = (float) (App::env('TRENDING_POWER') ?: 0.8);

        $sectionsString = App::env('TRENDING_SECTIONS') ?: '';
        $this->_sections = $sectionsString ? array_map('trim', explode(',', $sectionsString)) : [];
    }

    /**
     * Executes the ranking calculation for one or more list keys.
     *
     * @param  string|array  $listKeys  Single key or array of keys to process.
     */
    public function calculate(string|array $listKeys = 'default'): string|true
    {
        if (empty($this->_sections)) {
            $error = 'No sections defined for Trending Entries.';
            Craft::warning($error, __METHOD__);

            return $error;
        }

        // Ensure we are working with an array
        $listKeys = is_array($listKeys) ? $listKeys : [$listKeys];

        // 1. Fetch recent entries once (Reusing the query for all lists)
        $entries = Entry::find()
            ->section($this->_sections)
            ->postDate('> '.Db::prepareDateForDb(new \DateTime("-{$this->_timeWindowDays} days")))
            ->all();

        if (empty($entries)) {
            $error = "No entries found for time window {$this->_timeWindowDays} days";
            Craft::warning($error, __METHOD__);

            return $error;
        }

        foreach ($listKeys as $listKey) {
            // Cleanup this specific list
            $this->_cleanup($listKey);

            foreach ($entries as $entry) {
                $views = Plugin::getInstance()->counter->getViews($entry->id, $listKey);

                if ($views > 1) {
                    $postTimestamp = $entry->postDate->getTimestamp();
                    $hours = (time() - $postTimestamp) / 3600;

                    // Exponential Decay Formula
                    $score = pow(($views - 1), $this->_power) / pow(($hours + 2), $this->_gravity);

                    Db::upsert('{{%trending_entries_scores}}', [
                        'entryId' => $entry->id,
                        'listKey' => $listKey,
                        'score' => $score,
                        'dateUpdated' => Db::prepareDateForDb(new \DateTime),
                    ], [
                        'score' => $score,
                        'dateUpdated' => Db::prepareDateForDb(new \DateTime),
                    ]);
                }
            }
        }

        return true;
    }

    /**
     * Removes scores from the database that belong to entries older than the time window.
     */
    private function _cleanup(string $listKey): void
    {
        // Identify entry IDs that are still within the valid time window
        $validEntryIds = Entry::find()
            ->section($this->_sections)
            ->postDate('> '.Db::prepareDateForDb(new \DateTime("-{$this->_timeWindowDays} days")))
            ->select(['elements.id'])
            ->column();

        // Delete any score for this listKey that is NOT in the valid entry IDs list
        // This keeps our custom table lean and performant
        $condition = ['listKey' => $listKey];
        if (! empty($validEntryIds)) {
            $condition = ['and', $condition, ['not in', 'entryId', $validEntryIds]];
        }

        Db::delete('{{%trending_entries_scores}}', $condition);
    }
}
