<?php

namespace ggio\TrendingEntries\controllers;

use craft\web\Controller;
use ggio\TrendingEntries\Plugin;
use yii\web\Response;

/**
 * Stats Controller
 *
 * This controller provides public endpoints to track entry engagement.
 * It is designed to be called via AJAX from the frontend to ensure
 * compatibility with static caching (like Cloudflare or Varnish).
 *
 * @author Gonzalo García Arce <info@gongarce.io>
 * @copyright Gonzalo García Arce
 * @license MIT
 *
 * @since     1.0.0
 */
class StatsController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    public function actionIncrement(): Response
    {
        $this->requirePostRequest();

        $request = \Craft::$app->getRequest();
        $entryId = $request->getBodyParam('entryId');
        $listKey = $request->getBodyParam('listKey', 'default');

        if (! $entryId) {
            return $this->asJson(['success' => false, 'error' => 'No ID provided']);
        }

        Plugin::getInstance()->counter->increment((int) $entryId, $listKey);

        return $this->asJson(['success' => true]);
    }
}
