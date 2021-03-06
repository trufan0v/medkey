<?php
namespace app\modules\medical\application;

use app\common\data\ActiveDataProvider;
use app\common\db\ActiveRecord;
use app\common\dto\Dto;
use app\common\helpers\ArrayHelper;
use app\common\helpers\CommonHelper;
use app\common\helpers\Json;
use app\common\service\ApplicationService;
use app\common\service\exception\AccessApplicationServiceException;
use app\common\service\exception\ApplicationServiceException;
use app\modules\medical\models\finders\AttendanceFilter;
use app\modules\medical\models\orm\Attendance;
use app\modules\medical\models\form\Attendance as AttendanceForm;
use app\modules\medical\models\orm\Patient;
use app\modules\medical\models\orm\Referral;
use yii\base\Model;

/**
 * Class AttendanceService
 * @package Module\Medical
 * @copyright 2012-2019 Medkey
 */
class AttendanceService extends ApplicationService implements AttendanceServiceInterface
{
    /**
     * @inheritdoc
     */
    public function getAttendanceById($id)
    {
        $model = Attendance::findOne($id);
        if (!$this->isAllowed('getAttendanceById')) {
            throw new AccessApplicationServiceException('Доступ запрещен.');
        }
        return $model;
    }

    /**
     * @todo переименовать в bySchedule
     * @inheritdoc
     */
    public function cancelAttendance(string $attendanceId, string $referralId)
    {
        $referral = Referral::findOneEx($referralId);
        $attendance = Attendance::findOneEx($attendanceId);
        $attendance->setScenario(ActiveRecord::SCENARIO_UPDATE);
        $attendance->status = Attendance::STATUS_CANCEL;
        if (!$attendance->save()) {
            throw new ApplicationServiceException('Не удалось сохранить запись. Причина: ' . Json::encode($attendance->getErrors()));
        }
        $attendance->unlink('referrals', $referral, true);
        return $attendance;
    }

    /**
     * @todo переименовать в bySchedule
     * @inheritdoc
     */
    public function createAttendanceBySchedule(Dto $dto)
    {
        if (!$this->isAllowed('createAttendanceBySchedule')) {
            throw new AccessApplicationServiceException('Доступ запрещен.');
        }
        if (!$dto instanceof Dto) {
            throw new ApplicationServiceException('param in not Dto.');
        }
        $referral = Referral::findOneEx($dto->referralId);
        $attendance = new Attendance([
            'scenario' => ActiveRecord::SCENARIO_CREATE
        ]);
        $attendance->ehr_id = $dto->ehrId;
        $attendance->employee_id = $dto->employeeId;
        $attendance->datetime = $dto->datetime;
        if (!$attendance->save()) {
            throw new ApplicationServiceException('Не удалось сохранить запись. Причина: ' . Json::encode($attendance->getErrors()));
        }
        $attendance->link('referrals', $referral);
        return $attendance;
    }

    /**
     * @inheritdoc
     */
    public function getAttendanceForm($raw)
    {
        $model = Attendance::ensureWeak($raw);
//        if (!$model->isNewRecord) { // хук пока такой из-за ensure
//            $this->setProprietary($model);
//        if ($this->isAllowed('getAttendanceById')) {
//            throw new AccessApplicationServiceException('Доступ к просмотру записи запрещен.');
//        }
//        }
        $ehr = ArrayHelper::toArray($model->ehr);
        $employee = ArrayHelper::toArray($model->employee);
        $attendanceForm = new AttendanceForm();
        $attendanceForm->loadAr($model);
        $attendanceForm->id = $model->id;
        $attendanceForm->ehr = $ehr;
        $attendanceForm->employee = $employee;
        return $attendanceForm;
    }

    /**
     * @inheritdoc
     */
    public function getAttendanceList(Model $form)
    {
        /** @var $form AttendanceFilter */
        if (!$this->isAllowed('getAttendanceList')) {
            throw new AccessApplicationServiceException('Доступ к списку записей на прием запрещен.');
        }
        $query = Attendance::find();
        if (!empty($form->patientId)) {
            $query
                ->distinct(true)
                ->joinWith(['ehr.patient'])
                ->andFilterWhere([
                    Patient::tableColumns('id') => $form->patientId,
                ]);
        }
        if (!empty($form->referralId)) {
            $query
                ->distinct(true)
                ->joinWith(['referrals'])
                ->andFilterWhere([
                    Referral::tableColumns('id') => $form->referralId,
                ]);
        }
        $query
            ->andFilterWhere([
                'ehr_id' => $form->ehrId,
                'employee_id' => $form->employeeId,
                'status' => $form->status,
                'type' => $form->type,
                'cast(updated_at as date)' =>
                    empty($form->updatedAt) ? null : \Yii::$app->formatter->asDate($form->updatedAt, CommonHelper::FORMAT_DATE_DB),
                'datetime' =>
                    empty($form->datetime) ? null : \Yii::$app->formatter->asDatetime($form->datetime . date_default_timezone_get(), CommonHelper::FORMAT_DATETIME_DB)
            ]);
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getPrivileges()
    {
        return [
            'getAttendanceById' => 'Просмотр записи на приём',
            'createAttendanceBySchedule' => 'Создать запись на прием',
            'cancelAttendance' => 'Отменить запись на прием',
            'getAttendanceList' => 'Список всех записей на прием',
//            'getMyAttendanceList' => 'Список моих записей на прием',
        ];
    }

    /**
     * @inheritdoc
     */
    public function aclAlias()
    {
        return 'Запись на приём';
    }

    /**
     * @inheritdoc
     */
    public function checkRecordByDatetime(string $ehrId, string $employeeId, $datetime)
    {
        $attendance = Attendance::find()
            ->notDeleted()
            ->where([
                'ehr_id' => $ehrId,
                'employee_id' => $employeeId,
                'datetime' => \Yii::$app->formatter->asDatetime($datetime . date_default_timezone_get(), CommonHelper::FORMAT_DATETIME_DB),
                'status' => [
                    Attendance::STATUS_NEW,
                    Attendance::STATUS_PROGRESS,
                    Attendance::STATUS_ARCHIVE,
                    Attendance::STATUS_ABSENCE,
                    Attendance::STATUS_CONFIRM
                ],
            ])
            ->one();
        if ($attendance) {
            return $attendance->id;
        }
        return '';
    }
}
