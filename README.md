Behavior collection for Yii2
================
ManyToManyBehavior (this project fork  https://github.com/voskobovich/ManyToManyBehavior)
MaterializedPathBehavior (this project fork  https://github.com/matperez/yii2-materialized-path)

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```
php composer.phar require --prefer-dist kak/behavior "*"
```
or add
```
"kak/behavior": "*"
```
to the require section of your `composer.json` file.

Usage ManyToManyBehavior
-----
```php

```

#MaterializedPathBehavior
-----
insert the following code to your ActiveRecord class:
```php
    // step 1
     public function behaviors(){
         return [
            [
                'class' => 'kak\models\behaviors\MaterializedPathBehavior'
            ],
         ];
     }
     // step 2
      /**
      * Query factory
      * @return MaterializedPathQuery
      */
     public static function find()
     {
         return new MaterializedPathQuery(get_called_class());
     }
```
Create class MaterializedPathQuery ActiveQuery
```php
/**
Class MaterializedPathQuery
 * @return bool
 */
class MaterializedPathQuery extends ActiveQuery
{
    /**
     * Get root nodes
     * @return MaterializedPathModel
     */
    public function getChildren($path)
    {
        /** @var ActiveQuery $this */
        $this->andWhere(['path' => '.'.$path.'%']);
        return $this;
    }
} 
```
Set materialized path
```php
    $categoryModel = new Category(['parent_id' => 123 ]);
    $categoryModel->save();
```


#SluggableBehavior
-----
insert the following code to your ActiveRecord class:
```php
     public function behaviors(){
         return [
            [
                 'class' => 'kak\models\behaviors\SluggableBehavior',
                 'attribute' => 'title',  
                 'slugAttribute' => 'slug',           
                 // 'replacements' => [ '_' => '-']   
                 // 'limit' => '100'                  // truncate text
                 // 'delimiter' => '-'                // default delimiter
             ]
         ];
     }
```

#IdentityMapBehavior
-----