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
    use kak\models\behaviors\MaterializedPathBehavior

    // ...

    // step 1
     public function behaviors()
     {
         return [
            [
                'class' => MaterializedPathBehavior::class
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
Create class MaterializedPathQuery inherited from ActiveQuery
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
use kak\models\behaviors\SluggableBehavior;

     public function behaviors()
     {
         return [
            [
                 'class' => SluggableBehavior::class,
                 'attribute' => 'title',  
                 'slugAttribute' => 'slug',           
                 // 'replacements' => [ '_' => '-']   
                 // 'limit' => '100'                  // truncate text
                 // 'delimiter' => '-'                // default delimiter
             ]
         ];
     }
```

#CallbackBehavior
-----
insert the following code to your ActiveRecord class:


```php
use kak\models\behaviors\CallbackBehavior;

// ...

    public function behaviors()
    {
        return [
            'class' => CallbackBehavior::class,
            'params' => [
                'operators' => [
                    'attribute' => 'operator_list',
                    // implode/explode method
                    'method' => CallbackBehavior::METHOD_STRING,
                    'delimiter' => '|', // set other  delimiter only METHOD_STRING
                    // or json_encode/json_decode method
                    'method' => CallbackBehavior::METHOD_JSON,
                    // or custom callback
                    'set' => function ($value) {
                        return implode(',', $value);
                    },
                    'get' => function ($value) {
                        return explode(',', $value);
                    },
                ],
            ],
        ];
    }
```



#UUIDBehavior
-----

* is use postgress set actived module `uuid-ossp` 
* `create extension if not exists "uuid-ossp"`


insert the following code to your ActiveRecord class:

```php
use kak\models\behaviors\UUIDBehavior;

// ...

 public function behaviors()
 {
     return [
         [
             'class' => UUIDBehavior::class,
             'createViaDb' => true   // is use postgress
         ],
      ;
  }


```

