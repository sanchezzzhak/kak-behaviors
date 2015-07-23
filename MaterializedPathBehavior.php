<?php
namespace kak\models\behaviors;

use yii\base\Behavior;
use yii\db\ActiveQuery;
use \yii\db\ActiveRecord;

/**
 * Class MaterializedPathBehavior
 * @package kak\models\behaviors
 * @see The project fork for matperez/yii2-materialized-path
 * README.md for examples
 */
class MaterializedPathBehavior extends Behavior
{

    public $pathAttribute = 'path';
    public $parentAttribute = 'parent_id';
    public $positionAttribute = null;

    public $delimiter = '.';

    private $_children = [];
    private $_treeIsLoaded = false;


    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT  => 'onAfterUpdate',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'onAfterUpdate',
            ActiveRecord::EVENT_AFTER_DELETE  => 'onAfterDelete',
        ];
    }

    /**
     * Set node position among siblings
     * @param int $position
     * @param bool $runValidation
     * @param array $attributes
     * @return ActiveRecord
     * @throws \Exception
     */
    public function setPosition($position = null, $runValidation = true, $attributes = null) {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $path = $this->getParentId() ? $this->getParent()->{$this->pathAttribute} : '.' ;
        $posFrom = (int) $owner->{$this->positionAttribute};
        if ($position) {
            $posTo = (int) $position;
            $lower = $posTo < $posFrom;
            $owner->find()
                ->andWhere(['like', $this->pathAttribute , $path])
                ->andWhere(['between', $this->positionAttribute, min($posFrom, $posTo), max($posFrom, $posTo)])
                ->createCommand()->update($owner->tableName(), [
                    $this->positionAttribute => new \yii\db\Expression($this->positionAttribute . ($lower ? '+' : '-') . 1)
                ]);
            $owner->{$this->positionAttribute} = $position;
            $owner->update($runValidation, $attributes);
        } else {
            $owner->find()
                ->andWhere(['like', $this->pathAttribute , $path])
                ->andWhere(['>', $this->positionAttribute, $posFrom])
                ->createCommand()->update($owner->tableName(), [
                    $this->positionAttribute => new \yii\db\Expression($this->positionAttribute.' - 1')
                ]);
        }
        return $this;
    }



    public function onAfterDelete()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $owner->deleteAll(['like', $this->pathAttribute , $this->delimiter .$owner->primaryKey . $this->delimiter  ]);
    }

    /**
     * Load whole tree at once
     * @param ActiveQuery $query
     * @param bool $forceReload
     * @return MaterializedPathBehavior|ActiveRecord
     */
    public function loadTree(ActiveQuery $query = null, $forceReload = false) {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        if ($this->_treeIsLoaded && !$forceReload)
            return $owner;

        $this->_treeIsLoaded = true;
        $query || $query = $owner->find();
        if ($owner->{$this->pathAttribute} || $owner->primaryKey) {
            $path = $owner->primaryKey ?
                $this->delimiter . "{$owner->primaryKey}" . $this->delimiter  :
                $owner->{$this->pathAttribute};
				
            $query->andWhere(['like', $this->pathAttribute , $path]);
        } else {
            return $owner;
        }
        if(!empty($this->positionAttribute))
             $query->orderBy([$this->positionAttribute => SORT_ASC]);

        $items = $query->all();
        $levels = [];
        foreach($items as $item) {
            $l = $item->{$this->parentAttribute};
            if (empty($levels[$l]))
                $levels[$l] = [];
            $levels[$l][] = $item;
        }
        ksort($levels);
        foreach($levels as $level) {
            foreach($level as $element) {
                $this->addDescendant($element);
            }
        }
        return $owner;
    }

    /**
     * Check if node is child of current
     * @param MaterializedPathBehavior|ActiveRecord $node
     * @return bool
     */
    public function isChildOf($node) {
        return $node->isParentOf($this->owner);
    }


    /**
     * Get node children
     * @return ActiveRecord[]
     */
    public function getChildren() {
        if(!$this->_treeIsLoaded)
            return $this->loadTree()->getChildren();
        return $this->_children;
    }

    /**
     * Set node children
     * @param ActiveRecord[] $children
     */
    public function setChildren($children)
    {
        $this->_children = $children;
    }

    /**
     * Add node as a child
     * @param ActiveRecord $node
     * @return ActiveRecord
     */
    public function addChild($node) {
        if ($node->primaryKey) {
            $this->_children[$node->primaryKey] = $node;
        }
        return $this->owner;
    }

    /**
     * Add descendant node
     * @param ActiveRecord $node
     */
    public function addDescendant($node) {
        if ($this->isParentOf($node, true)) {
            $this->addChild($node);
        } else if ($child = $this->getChildParentOf($node)) {
            /** @var $child MaterializedPathBehavior */
            $child->addDescendant($node);
        }

    }

    public function onAfterUpdate()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $primaryKey = $owner->primaryKey()[0];

        $query = clone $owner;
        $model = $query->find()->where([$primaryKey => $owner->{$this->parentAttribute}])
            ->one();

        $path  = $model ?  $model->{$this->pathAttribute} : '';

        $owner->updateAttributes([
            $this->pathAttribute => $path . $this->delimiter  .$owner->getPrimaryKey(true)[$primaryKey]
        ]);
    }

    /**
     * Get parent ids array
     * @return array
     */
    public function getParentIds()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $ids = explode( $this->delimiter , $owner->{$this->pathAttribute});
        array_pop($ids);
        foreach ($ids as &$id) { $id = (int)$id; }
        return $ids;
    }

    /**
     * Get closest parent id
     * @return mixed
     */
    public function getParentId() {
        $ids = $this->getParentIds();
        return array_pop($ids);
    }

    /**
     * @return ActiveRecord
     */
    public function getParent()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        if ($this->getParentId()) {
            $primaryKey = $owner->primaryKey()[0];
            return $owner->find()
                ->andWhere([$primaryKey => $this->getParentId()])
                ->one();
        }
        return null;
    }

    /**
     * Check that node is parent of current
     * @param MaterializedPathBehavior|ActiveRecord $node
     * @param bool $closestOnly
     * @return bool
     */
    public function isParentOf(ActiveRecord $node, $closestOnly = false) {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        return $closestOnly ?
            $owner->primaryKey == $node->getParentId() :
            in_array($owner->primaryKey, $node->getParentIds());
    }
    /**
     * @param MaterializedPathBehavior $node
     * @return ActiveRecord
     */
    public function getChildParentOf($node) {
        foreach ($this->_children as $child) {
            if (in_array($child->primaryKey, $node->getParentIds())) {
                return $child;
            }
        }
        return null;
    }



} 