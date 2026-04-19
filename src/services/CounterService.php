<?php

namespace ggio\TrendingEntries\services;

use Craft;
use craft\base\Component;
use yii\redis\Connection;

/**
 * Counter Service
 *
 * This service manages the volatile engagement data (view counts).
 * It is designed to be high-performance by leveraging Redis atomic increments
 * when available, while providing a seamless fallback to standard Craft cache drivers.
 *
 * @author Gonzalo García Arce <info@gongarce.io>
 * @copyright Gonzalo García Arce
 * @license MIT
 *
 * @since     1.0.0
 */
class CounterService extends Component
{
    /**
     * Increments the view counter for a specific entry.
     *
     * If Redis is available, it performs an atomic INCR operation for maximum speed.
     * Otherwise, it falls back to a get/set pattern compatible with File or DB cache.
     *
     * @param  int  $entryId  The ID of the entry being viewed.
     * @param  string  $listKey  The identifier for the trending list (e.g., 'news').
     * @return mixed The new incremented value or the previous count depending on the driver.
     */
    public function increment(int $entryId, string $listKey = 'default'): mixed
    {
        $cache = Craft::$app->getCache();
        $cacheKey = "trending:{$listKey}:{$entryId}";

        // Check for direct Redis connection to use atomic increment
        if (isset($cache->redis) && $cache->redis instanceof Connection) {
            return $cache->redis->incr($cacheKey);
        }

        // Fallback for FileCache, DbCache, etc.
        $current = (int) ($cache->get($cacheKey) ?: 0);
        $newCount = $current + 1;

        // We set a 30-day TTL for non-Redis drivers to avoid permanent clutter
        $cache->set($cacheKey, $newCount, 2592000);

        return $newCount;
    }

    /**
     * Gets the view count for an entry, handling different cache drivers.
     *
     * This method resolves the "serialization" issue by attempting a standard
     * Craft cache read first, and then a raw Redis read if the first one fails.
     *
     * @param  int  $entryId  The ID of the entry.
     * @param  string  $listKey  The identifier for the trending list.
     * @return int The current view count.
     */
    public function getViews(int $entryId, string $listKey = 'default'): int
    {
        $cache = Craft::$app->getCache();
        $cacheKey = "trending:{$listKey}:{$entryId}";

        // 1. Attempt standard read (handles serialized data from File/DB cache)
        $views = $cache->get($cacheKey);

        // 2. If standard read fails and Redis is active, attempt a raw read
        // to retrieve data stored via the INCR command.
        if ($views === false && isset($cache->redis)) {
            $views = $cache->redis->get($cacheKey);
        }

        return (int) ($views ?: 0);
    }
}
