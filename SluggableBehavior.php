<?php
namespace kak\models\behaviors;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\validators\UniqueValidator;
/**
 * SluggableBehavior automatically fills the specified attribute with a value that can be used a slug in a URL.
 *
 * To use SluggableBehavior, insert the following code to your ActiveRecord class:
 *
 * ```php
 *
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => 'kak\models\behaviors\SluggableBehavior',
 *             'attribute' => 'title',
 *             // 'slugAttribute' => 'slug',
 *             // 'replacements' => [ '_' => '-']
 *             // 'limit' => '100'                  // truncate text
 *             // 'delimiter' => '-'                // default
 *         ],
 *     ];
 * }
 * ```
 *
 * Class SluggableBehavior
 * @package kak\models\behaviors
 * @see modify \yii\behaviors\SluggableBehavior replaced Inflector::slug my method
 */
class SluggableBehavior extends AttributeBehavior
{
    public $charMap = [
        //Latin
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
        'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
        'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
        'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
        'ÿ' => 'y',
        // Latin symbols
        '©' => '(c)',
        // Greek
        'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
        'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
        'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
        'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
        'Ϋ' => 'Y',
        'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
        'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
        'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
        'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
        'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
        // Turkish
        'Ş' => 'S', 'İ' => 'I', 'Ğ' => 'G',
        'ş' => 's', 'ı' => 'i', 'ğ' => 'g',
        // Russian
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
        'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
        'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
        'Я' => 'Ya',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
        'я' => 'ya',
        // Ukrainian
        'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
        'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
        // Czech
        'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
        'Ž' => 'Z',
        'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
        'ž' => 'z',
        // Polish
        'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ś' => 'S', 'Ź' => 'Z',
        'Ż' => 'Z',
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ś' => 's', 'ź' => 'z',
        'ż' => 'z',
        // Latvian
        'Ā' => 'A', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
        'Ū' => 'u',
        'ā' => 'a', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
        'ū' => 'u'
    ];

    public $replacements = [];
    public $delimiter = '-';
    public $limit = null;
    public $lowercase = true;

    /**
     * @var string the attribute that will receive the slug value
     */
    public $slugAttribute = 'slug';
    /**
     * @var string|array the attribute or list of attributes whose value will be converted into a slug
     */
    public $attribute;
    /**
     * @var string|callable the value that will be used as a slug. This can be an anonymous function
     * or an arbitrary value. If the former, the return value of the function will be used as a slug.
     * The signature of the function should be as follows,
     *
     * ```php
     * function ($event)
     * {
     *     // return slug
     * }
     * ```
     */
    public $value;
    /**
     * @var boolean whether to generate a new slug if it has already been generated before.
     * If true, the behavior will not generate a new slug even if [[attribute]] is changed.
     * @since 2.0.2
     */
    public $immutable = false;
    /**
     * @var boolean whether to ensure generated slug value to be unique among owner class records.
     * If enabled behavior will validate slug uniqueness automatically. If validation fails it will attempt
     * generating unique slug value from based one until success.
     */
    public $ensureUnique = false;
    /**
     * @var array configuration for slug uniqueness validator. Parameter 'class' may be omitted - by default
     * [[UniqueValidator]] will be used.
     * @see UniqueValidator
     */
    public $uniqueValidator = [];
    /**
     * @var callable slug unique value generator. It is used in case [[ensureUnique]] enabled and generated
     * slug is not unique. This should be a PHP callable with following signature:
     *
     * ```php
     * function ($baseSlug, $iteration, $model)
     * {
     *     // return uniqueSlug
     * }
     * ```
     *
     * If not set unique slug will be generated adding incrementing suffix to the base slug.
     */
    public $uniqueSlugGenerator;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty($this->attributes)) {
            $this->attributes = [BaseActiveRecord::EVENT_BEFORE_VALIDATE => $this->slugAttribute];
        }

        if ($this->attribute === null && $this->value === null) {
            throw new InvalidConfigException('Either "attribute" or "value" property must be specified.');
        }
    }

    /**
     * Generate slug
     * @param $str string
     * @return string
     */
    public function generateSlug($str)
    {
        $str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());
        $str = preg_replace(array_keys($this->replacements), $this->replacements , $str);
        $str = str_replace(array_keys($this->charMap), $this->charMap, $str);
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $this->delimiter, $str);
        $str = preg_replace('/(' . preg_quote($this->delimiter, '/') . '){2,}/', '$1', $str);
        $str = trim($str, $this->delimiter);

        if($this->limit)
            $str = StringHelper::truncate($str,$this->limit,'','UTF-8');

        return $this->lowercase ? mb_strtolower($str, 'UTF-8') : $str;
    }

    /**
     * @inheritdoc
     */
    protected function getValue($event)
    {
        $isNewSlug = true;

        if ($this->attribute !== null) {
            $attributes = (array) $this->attribute;
            /* @var $owner BaseActiveRecord */
            $owner = $this->owner;
            if (!empty($owner->{$this->slugAttribute})) {
                $isNewSlug = false;
                if (!$this->immutable) {
                    foreach ($attributes as $attribute) {
                        if ($owner->isAttributeChanged($attribute)) {
                            $isNewSlug = true;
                            break;
                        }
                    }
                }
            }
            if ($isNewSlug) {
                $slugParts = [];
                foreach ($attributes as $attribute) {
                    $slugParts[] = $owner->{$attribute};
                }
                //$slug = Inflector::slug(implode('-', $slugParts));
                 $slug = $this->generateSlug(implode('-', $slugParts));

            } else {
                $slug = $owner->{$this->slugAttribute};
            }
        } else {
            $slug = parent::getValue($event);
        }

        if ($this->ensureUnique && $isNewSlug) {
            $baseSlug = $slug;
            $iteration = 0;
            while (!$this->validateSlug($slug)) {
                $iteration++;
                $slug = $this->generateUniqueSlug($baseSlug, $iteration);
            }
        }
        return $slug;
    }

    /**
     * Checks if given slug value is unique.
     * @param string $slug slug value
     * @return boolean whether slug is unique.
     */
    private function validateSlug($slug)
    {
        /* @var $validator UniqueValidator */
        /* @var $model BaseActiveRecord */
        $validator = Yii::createObject(array_merge(
            [
                'class' => UniqueValidator::className()
            ],
            $this->uniqueValidator
        ));

        $model = clone $this->owner;
        $model->clearErrors();
        $model->{$this->slugAttribute} = $slug;

        $validator->validateAttribute($model, $this->slugAttribute);
        return !$model->hasErrors();
    }

    /**
     * Generates slug using configured callback or increment of iteration.
     * @param string $baseSlug base slug value
     * @param integer $iteration iteration number
     * @return string new slug value
     * @throws \yii\base\InvalidConfigException
     */
    private function generateUniqueSlug($baseSlug, $iteration)
    {
        if (is_callable($this->uniqueSlugGenerator)) {
            return call_user_func($this->uniqueSlugGenerator, $baseSlug, $iteration, $this->owner);
        } else {
            return $baseSlug . '-' . ($iteration + 1);
        }
    }



}