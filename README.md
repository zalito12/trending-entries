# Trending Entries Plugin for Craft CMS

A high-performance, flexible trending content system for Craft CMS. This plugin tracks entry views using Redis (with atomic increments) or standard cache fallbacks, and calculates a trending score using a time-decay algorithm.

## Features

- **Hybrid Storage:** Uses Redis INCR for high-performance, race-condition-free counting.
- **Graceful Fallback:** Automatically switches to File or DB cache if Redis is unavailable.
- **Static Cache Friendly:** Tracking is handled via a JS-ready API endpoint.
- **Time-Decay Algorithm:** Rankings reward newer content and naturally phase out older popular posts.
- **Multi-List Support:** Create different trending lists (e.g., news, blog, videos).

## Installation

### Composer

Install the plugin requirements.

```bash
composer require yiisoft/yii2-redis # If using Redis
```

### Plugin Setup

Install the plugin through the Craft Control Panel or via CLI.

### Redis Config (Optional)

If using Redis, ensure your `config/app.php` is configured to use the `yii redis\Cache` component.

## How It Works

### 1. Tracking Views

The plugin provides a Controller action to track views via POST. This bypasses static page caching (Cloudflare, Varnish, etc.).

- **Endpoint:** `/actions/trending-entries/stats/increment`
- **Payload:** `entryId` (int), `listKey` (string, optional)

**JavaScript**

```javascript
// Example Frontend Implementation
const data = new FormData();
data.push('entryId', 123);
data.push('listKey', 'news');
data.push(window.csrfTokenName, window.csrfTokenValue);

fetch('/actions/trending-entries/stats/increment', {
    method: 'POST',
    body: data
});
```

### 2. Ranking Calculation

The trending score is calculated using the following formula:

$$Score = \\frac{(Views - 1)^{0.8}}{(AgeInHours + 2)^{1.2}}$$

This ensures that a post with 1,000 views from last year won't outrank a post with 100 views from today.

### Automation (Cron Job)

To update the rankings, you must run the sync command periodically. We recommend once per hour.

**Standard Crontab**

```
0 * * * * php /path/to/project/craft trending-entries/sync/calculate news blog
```

**Servd / Managed Hosting**
Add a scheduled task pointing to the command:
`trending-entries/sync/calculate`

## Usage in Twig

The plugin provides a simple way to fetch trending entries while ensuring a fallback to "Latest Posts" if the trending list is short or empty.

### Basic Trending List

**Twig**

```twig
{# Fetch top 5 trending news #}
{% set trending = craft.trendingEntries.get('news').limit(5).all() %}
```

### Advanced: Trending with Fallback (No duplicates)

This ensures you always show 5 items, filling gaps with recent posts if needed:

**Twig**

```twig
{# 1. Get Trending #}
{% set trending = craft.trendingEntries.get('news').limit(5).collect() %}

{# 2. Get Latest (excluding IDs already in trending) #}
{% set latest = craft.entries()
    .section('news')
    .id(['not']|merge(trending.pluck('id').all()))
    .limit(5)
    .orderBy('postDate DESC')
    .collect()
%}

{# 3. Merge and Take 5 #}
{% set finalItems = trending.merge(latest).take(5) %}

{% for item in finalItems %}
    <p>{{ item.title }}</p>
{% endfor %}
```

## Environment Variables

The following environment variables can be configured in your `.env` file or `config/app.php` to customize the plugin's behavior:

- `TRENDING_SECTIONS` (string, required): A comma-separated list of section handles that the plugin should track for trending entries (e.g., `news,blog,videos`). *This variable is required for the plugin to work correctly.*
- `TRENDING_WINDOW_DAYS` (int, optional): The number of days to query entries for trending calculations. Defaults to `7`.
- `TRENDING_GRAVITY` (float, optional): Adjusts the impact of "age" in the trending score formula. A higher value means older content decays faster. Defaults to `1.2`.
- `TRENDING_POWER` (float, optional): Adjusts the impact of "views" in the trending score formula. A higher value means views have a greater impact. Defaults to `0.8`.

## Technical Architecture

### CounterService

Handles the "Hit" logic.

- **Redis Mode:** Uses raw `incr()` for speed.
- **Standard Mode:** Uses `get() + set()` with a 30-day TTL.

### RankingService

Handles the math and DB persistence.

- Fetches entries from a configurable "Window" (default 7 days).
- Uses upsert to keep the `trending_entries_scores` table lean.

## Support

Created by Gongarce. Optimized for performance and high-traffic environments.
