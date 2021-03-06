<?php
namespace app\seeds;

use app\common\seeds\Seed;
use app\common\helpers\ArrayHelper;

/**
 * Seed with default dashlets configuration for default dashboard
 * @package Seed
 * @copyright 2012-2019 Medkey
 */
class DashboardItem extends Seed
{
    public function run()
    {
        $dashboards = $this->call('dashboard')->models;
        $this->model = \app\modules\dashboard\models\orm\DashboardItem::class;

        $this->data = [
            [
                'title' => 'Список заказов',
                'dashboard_id' => ArrayHelper::findBy($dashboards, ['key' => 'default'])->id,
                'widget' => 'OrderListDashlet',
                'position' => 0,
                'order' => 0,
            ],
            [
                'title' => 'Список пациентов',
                'dashboard_id' => ArrayHelper::findBy($dashboards, ['key' => 'default'])->id,
                'widget' => 'PatientListDashlet',
                'position' => 0,
                'order' => 1,
            ],
            [
                'title' => 'Список медицинских карт',
                'dashboard_id' => ArrayHelper::findBy($dashboards, ['key' => 'default'])->id,
                'widget' => 'EhrListDashlet',
                'position' => 0,
                'order' => 2,
            ],
            [
                'title' => 'График заказов',
                'dashboard_id' => ArrayHelper::findBy($dashboards, ['key' => 'default'])->id,
                'widget' => 'OrderChartDashlet',
                'position' => 1,
                'order' => 0,
            ],
        ];
    }
}
