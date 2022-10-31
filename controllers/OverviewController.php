<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\wiki\controllers;

use humhub\modules\wiki\helpers\Url;
use humhub\modules\wiki\models\WikiPage;
use Yii;
use yii\data\ActiveDataProvider;


/**
 * Class OverviewController
 * @package humhub\modules\wiki\controllers
 */
class OverviewController extends BaseController
{

    /**
     * @return $this|void|\yii\web\Response
     * @throws \yii\base\Exception
     */
    public function actionIndex()
    {
        $homePage = $this->getHomePage();
        if ($homePage !== null) {
            return $this->redirect(Url::toWiki($homePage));
        }

        return $this->redirect(Url::toOverview($this->contentContainer));
    }


    /**
     * @return OverviewController|string|\yii\console\Response|\yii\web\Response
     * @throws \yii\base\Exception
     */
    public function actionListCategories()
    {
        if (!$this->hasPages()) {
            return $this->render('no-pages', [
                'canCreatePage' => $this->canCreatePage(),
                'createPageUrl' => $this->contentContainer->createUrl('/wiki/page/edit'),
                'contentContainer' => $this->contentContainer
            ]);
        }

        return $this->renderSidebarContent(['list-categories', 'last-edited'], [
            'contentContainer' => $this->contentContainer,
            'canCreate' => $this->canCreatePage(),
            'dataProvider' => $this->getLastEditedDataProvider(),
            'hideSidebarOnSmallScreen' => false,
        ]);
    }

    public function actionLastEdited()
    {
        if (!$this->hasPages()) {
            return $this->render('no-pages', [
                'canCreatePage' => $this->canCreatePage(),
                'createPageUrl' => $this->contentContainer->createUrl('/wiki/page/edit'),
                'contentContainer' => $this->contentContainer
            ]);
        }

        return $this->renderSidebarContent('last-edited', [
            'contentContainer' => $this->contentContainer,
            'canCreate' => $this->canCreatePage(),
            'dataProvider' => $this->getLastEditedDataProvider(),
        ]);
    }

    private function getLastEditedDataProvider(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => WikiPage::find()
                ->contentContainer($this->contentContainer)
                ->readable()
                ->andWhere(['is_category' => 0]),
            'pagination' => ['pageSize' => 10],
            'sort' => [
                'attributes' => [
                    'title',
                    'updated_at' => [
                        'asc' => ['content.updated_at' => SORT_ASC],
                        'desc' => ['content.updated_at' => SORT_DESC],
                    ],
                ],
                'defaultOrder' => [
                    'updated_at' => SORT_DESC,
                ],
            ],
        ]);
    }

    public function actionSearch()
    {
        return $this->renderSidebarContent('search', [
            'contentContainer' => $this->contentContainer,
            'canCreate' => $this->canCreatePage(),
            'hideSidebarOnSmallScreen' => false,
        ]);
    }

    public function actionUpdateFoldingState(int $categoryId)
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        if (empty($categoryId)) {
            return;
        }

        $userSettings = Yii::$app->user->getIdentity()->getSettings();
        $foldingStateParamName = 'wiki.foldedCategory.' . $categoryId;

        if (Yii::$app->request->get('state')) {
            $userSettings->set($foldingStateParamName, true);
        } else {
            $userSettings->delete($foldingStateParamName);
        }
    }
}