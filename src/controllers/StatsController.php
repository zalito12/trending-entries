<?php

namespace ggio\TrendingEntries\controllers;

use Craft;
use craft\helpers\App;
use craft\web\Controller;
use ggio\TrendingEntries\Plugin;
use ggio\TrendingEntries\traits\AmpResourceRequests;
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
    use AmpResourceRequests;

    protected array|int|bool $allowAnonymous = true;

    private bool $_isAmp = false;

    private ?string $_ampDomain = null;

    public function beforeAction($action): bool
    {
        $this->_ampDomain = App::parseEnv('$TRENDING_AMP_DOMAIN');
        if ($this->_ampDomain && $this->isAmpRequestValid($this->_ampDomain)) {
            $this->_isAmp = true;
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIncrement(): Response
    {
        if ($this->_isAmp) {
            $this->setAmpResponseHeaders();
        } else {
            $this->requirePostRequest();
        }

        $request = Craft::$app->getRequest();
        $entryId = $request->getParam('entryId');
        $listKey = $request->getParam('listKey', 'default');

        if (! $entryId) {
            Craft::$app->response->setStatusCode(400);

            return $this->asJson(['success' => false, 'error' => 'No ID provided']);
        }

        Plugin::getInstance()->counter->increment((int) $entryId, $listKey);

        return $this->asJson(['success' => true]);
    }
}
