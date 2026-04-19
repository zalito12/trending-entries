<?php

namespace ggio\TrendingEntries;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\web\twig\variables\CraftVariable;
use ggio\TrendingEntries\services\CounterService;
use ggio\TrendingEntries\services\RankingService;
use ggio\TrendingEntries\variables\TrendingEntriesVariable;
use yii\base\Event;

/**
 * Trending Entries plugin
 *
 * @method static Plugin getInstance()
 *
 * @property CounterService counter
 * @property RankingService ranking
 *
 * @author Gonzalo García Arce <info@gongarce.io>
 * @copyright Gonzalo García Arce
 * @license MIT
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'ggio\TrendingEntries\console\controllers';
        }

        $this->setComponents([
            'counter' => CounterService::class,
            'ranking' => RankingService::class,
        ]);

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('trendingEntries', TrendingEntriesVariable::class);
            }
        );
    }
}
