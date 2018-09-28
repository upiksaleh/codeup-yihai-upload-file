<?php
/**
 * CodeUP yihai using Yii Framework
 * @link http://codeup.orangeit.id/yihai
 * @copyright Copyright (c) 2018 OrangeIT.ID
 * @author Upik Saleh <upxsal@gmail.com>
 */

namespace codeup\uploadfile;

use Yii;
use yii\helpers\FileHelper;
use yii\imagine\Image;
use yii\web\NotFoundHttpException;

/**
 * Class ImageShowAction
 * @package codeup\uploadfile
 * @author Upik Saleh <upxsal@gmail.com>
 */
class ImageShowAction extends \yii\base\Action
{
    public $thumbPath = '@runtime/thumbnails';

    private $_model = 1;


    public function run($group, $id, $name, $width = null, $height = null)
    {
        $params = ['group' => $group, 'id' => $id, 'name' => $name];
        $model = $this->_model = $this->findModel($params);
        if (!file_exists($model->getFilename())) {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
        $response = Yii::$app->getResponse();
        if ($width === null && $height === null) {
            return $response->sendFile($model->getFilename(), $model->name, [
                'mimeType' => $model->type,
                'fileSize' => $model->size,
                'inline' => true
            ]);
        } else {
            $dir = '';
            if ($width !== null) {
                $dir .= 'w' . (int)$width;
            }
            if ($height !== null) {
                $dir .= 'h' . (int)$height;
            }

            $filename = sprintf('%s/%s/%x/%d_%s', Yii::getAlias($this->thumbPath), $dir, $id % 255, $id, $model->name);
            if (!file_exists($filename)) {
                FileHelper::createDirectory(dirname($filename));
                $image = Image::thumbnail($model->filename, $width, $height);
                $image->save($filename);
            }
            return $response->sendFile($filename, $model->name, [
                'mimeType' => $model->type,
                'inline' => true
            ]);
        }
    }


    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->_model;
    }
    /**
     * Get model
     * @param array $params
     * @return FileModel
     * @throws NotFoundHttpException
     */
    protected function findModel($params)
    {
        if (($model = FileModel::findOne($params)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }
}