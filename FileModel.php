<?php
/**
 * CodeUP yihai using Yii Framework
 * @link http://codeup.orangeit.id/yihai
 * @copyright Copyright (c) 2018 OrangeIT.ID
 * @author Upik Saleh <upxsal@gmail.com>
 */

namespace codeup\uploadfile;

use Yii;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use yii\imagine\Image;

/**
 * This is the model class for table "uploaded_file".
 *
 * @property integer $id
 * @property string $name
 * @property string $group
 * @property string $filename
 * @property integer $size
 * @property string $type
 */
class FileModel extends \yii\db\ActiveRecord
{
    /**
     * @var string 
     */
    public static $defaultUploadPath = '@runtime/upload';
    /**
     * @var integer
     */
    public static $defaultDirectoryLevel = 1;
    /**
     * @var UploadedFile 
     */
    public $file;

    /**
     * @var string Upload path
     */
    public $uploadPath;
    /**
     * @var integer the level of sub-directories to store uploaded files. Defaults to 1.
     * If the system has huge number of uploaded files (e.g. one million), you may use a bigger value
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     */
    public $directoryLevel;
    /**
     * @var \Closure
     */
    public $saveCallback;
    public $cropParam;
    public $resizeParam;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%sys_uploaded_file}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['file'], 'required'],
            [['file'], 'file', 'skipOnEmpty' => false],
            [['uploadPath'], 'default', 'value' => static::$defaultUploadPath],
            [['name', 'size'], 'default', 'value' => function($obj, $attribute) {
                    return $obj->file->$attribute;
                }],
            [['type'], 'default', 'value' => function() {
                    return FileHelper::getMimeType($this->file->tempName);
                }],
            [['filename'], 'default', 'value' => function() {
                    $level = $this->directoryLevel === null ? static::$defaultDirectoryLevel : $this->directoryLevel;
                    $key = md5(microtime() . $this->file->name);
                    $base = $this->uploadPath;
                    if ($level > 0) {
                        for ($i = 0; $i < $level; ++$i) {
                            if (($prefix = substr($key, 0, 2)) !== false) {
                                $base .= DIRECTORY_SEPARATOR . $prefix;
                                $key = substr($key, 2);
                            }
                        }
                    }
                    return $base . DIRECTORY_SEPARATOR . "{$key}_{$this->file->name}";
                }],
            [['size'], 'integer'],
            ['group', 'string', 'max' => 20],
            //['group', 'default', 'value' => $this->group],
            [['name'], 'string', 'max' => 256],
            [['type'], 'string', 'max' => 64],
            [['filename'], 'string', 'max' => 256]
        ];
    }

    public function behaviors()
    {
        return [
            '\codeup\behaviors\BlameableGroupBehavior',
            '\yii\behaviors\TimeStampBehavior'
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Basename',
            'filename' => 'Filename',
            'size' => 'Filesize',
            'group' => 'Group',
            'type' => 'Content Type',
        ];
    }

    /**
     * @inherited
     */
    public function beforeSave($insert)
    {
        if ($this->file && $this->file instanceof UploadedFile && parent::beforeSave($insert)) {
            $fullPath = Yii::getAlias($this->filename);
            FileHelper::createDirectory(dirname($fullPath));
            if ($this->saveCallback !== null) {
                return call_user_func($this->saveCallback, $this);
            } elseif ($this->cropParam && ($crop = Yii::$app->getRequest()->post($this->cropParam))) {
                return $this->cropImage($crop);
            } elseif ($this->resizeParam && ($resize = Yii::$app->getRequest()->post($this->resizeParam))) {
                return $this->resizeImage($resize);
            } else {
                return $this->file->saveAs($fullPath, false);
            }
        }
        return false;
    }

    protected function cropImage($crop)
    {
        $image = Image::crop($this->file->tempName, $crop['w'], $crop['h'], [$crop['x'], $crop['y']]);
        $image->save($this->filename);
        $this->size = filesize($this->filename);
        return true;
    }

    protected function resizeImage($resize)
    {
        $image = Image::thumbnail($this->file->tempName, $resize['width'], $resize['height']);
        $image->save($this->filename);
        $this->size = filesize($this->filename);
        return true;
    }

    /**
     * @inherited
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            @unlink($this->filename);
            return true;
        }
        return false;
    }

    /**
     * Save file
     * @param UploadedFile|string $file
     * @param array $options
     * @return boolean|static
     */
    public static function saveAs($file, $options = [])
    {
        if (!($file instanceof UploadedFile)) {
            $file = UploadedFile::getInstanceByName($file);
        }
        $options['file'] = $file;
        $model = new static($options);
        return $model->save() ? $model : false;
    }

    public function getContent()
    {
        return file_get_contents($this->filename);
    }

    public function getFilename(){
        return Yii::getAlias($this->filename);
    }
}
