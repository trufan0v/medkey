<?php
namespace app\modules\medical\models\orm;

use app\common\db\ActiveQuery;
use app\common\db\ActiveRecord;
use app\common\validators\ForeignKeyValidator;

/**
 * Class Ehr
 *
 * @property string $number
 * @property int $type
 * @property int $status
 * @property string $patient_id
 * @property-read Patient $patient
 *
 * @package Module\Medical
 * @copyright 2012-2019 Medkey
 */
class Ehr extends ActiveRecord
{
    const TYPE_AMBULATORY = 1;
    const TYPE_AMBULATORY_NAME = 'ambulatory';
    const TYPE_HOSPITAL = 2;
    const TYPE_HOSPITAL_NAME = 'hospital';

    const STATUS_ACTIVE = 1;
    const STATUS_ACTIVE_NAME = 'active';
    const STATUS_INACTIVE = 2;
    const STATUS_INACTIVE_NAME = 'inactive';


    public function init()
    {
        if ($this->isNewRecord) {
            $this->number = $this->generateNumber();
        }
        parent::init();
    }

    /**
     * @todo number в БД является строкой
     * @todo лучше SEQUENCE на уровне БД делать
     * @return int
     */
    public function generateNumber()
    {
        $db = \Yii::$app->db;
        if ($db->driverName === 'pgsql') {
            $cast = 'cast(number as INT)';
        } elseif ($db->driverName === 'mysql') {
            $cast = 'cast(number as SIGNED)';
        } else {
            $cast = null;
        }
        $max = static::find()
            ->max($cast);
        return (string)++$max;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [ 'number', 'unique', 'filter' => function (ActiveQuery $query) {
                return $query
                    ->notDeleted();
            }, ],
            [ ['number', 'type', 'status', 'patient_id'],
                'required',
                'on' => [ActiveRecord::SCENARIO_CREATE, ActiveRecord::SCENARIO_UPDATE] ],
            [ ['patient_id'], ForeignKeyValidator::class ],
            [ ['number'],
                'string',
                'on' => [ActiveRecord::SCENARIO_CREATE, ActiveRecord::SCENARIO_UPDATE] ],
            [ ['type', 'status'],
                'integer',
                'on' => [ActiveRecord::SCENARIO_CREATE, ActiveRecord::SCENARIO_UPDATE] ],
            [ ['number'], 'default', 'value' => function () {
                return $this->generateNumber();
            } ],
//            [ ['number'],
//                'unique', 'filter' => function (ActiveQuery $query) {
//                return $query->notDeleted();
//            }, 'on' => 'create' ],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabelsOverride()
    {
        return [
            'number' => 'Номер',
            'status' => 'Статус',
            'type' => 'Тип',
            'patient_id' => 'Пациент',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPatient()
    {
        return $this->hasOne(Patient::class, ['id' => 'patient_id']);
    }

    /**
     * @return array
     */
    public static function types()
    {
        return [
            self::TYPE_AMBULATORY => 'Амбулаторный',
            self::TYPE_HOSPITAL => 'Стационар',
        ];
    }

    /**
     * Get sex name
     *
     * @return string
     */
    public function getTypeName()
    {
        $types  = $this::types();

        return !empty($types[$this->type]) ? $types[$this->type] : '';
    }

    /**
     * @return array
     */
    public static function statusListData()
    {
        return [
            self::STATUS_ACTIVE => 'Активная',
            self::STATUS_INACTIVE => 'Неактивная',
        ];
    }

    /**
     * Get sex name
     *
     * @return string
     */
    public function getStatusName()
    {
        $statuses  = $this::statusListData();

        return !empty($statuses[$this->status]) ? $statuses[$this->type] : '';
    }
}
