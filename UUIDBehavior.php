<?php
namespace kak\models\behaviors;

use yii\base\InvalidConfigException;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\Expression;

/**
 * Class UUIDBehavior
 * @package app\components\behaviors
 */
class UUIDBehavior extends Behavior
{
    public $attribute = 'id';
    public $createViaDb = false;
    public $sequenceName = null;

    private $isSetAttribute = false;

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            BaseActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
        ];
    }

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if ((string)$this->attribute === '') {
            throw new InvalidConfigException('Either "attribute" property must be specified.');
        }

    }

    /**
     * set beforeSave() -> UUID data
     * @throws
     */
    public function beforeSave()
    {
        if (!$this->owner instanceof ActiveRecord) {
            throw new InvalidConfigException('Behavior works only with models extends from ActiveRecord');
        }

        /** @var ActiveRecord */
        $owner = $this->owner;
        $value = (string)$owner->{$this->attribute};

        if ($value === '' && $this->createViaDb) {
            $connection = $owner::getDb();
            if ($connection->getDriverName() === 'pgsql') {
                $value = new Expression('uuid_generate_v4()');
                $this->isSetAttribute = true;
            }
        }

        if ($value === '') {
            $value = self::generateUIID4();
            $this->isSetAttribute = true;
        }

        $owner->{$this->attribute} = $value;
    }

    public function afterSave()
    {
        /** @var ActiveRecord */
        $owner = $this->owner;
        $value = $owner->{$this->attribute};
        if ($this->createViaDb && $this->isSetAttribute) {
            $connection = $owner::getDb();
            if ($connection->getDriverName() === 'pgsql' && $value instanceof Expression) {
                $this->owner->{$this->attribute} = $connection->getLastInsertID($this->sequenceName);
            }
        }
    }
    
     /**
     * @param string $data
     * @return string
     * @throws \Exception
     */
    public static function generateUIID4(string $data = null): string
    {
        $data = $data ?? random_bytes(16);
        assert(strlen($data) === 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
}
