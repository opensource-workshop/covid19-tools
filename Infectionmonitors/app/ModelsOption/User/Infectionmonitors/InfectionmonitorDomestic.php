<?php

namespace App\ModelsOption\User\Infectionmonitors;

use Illuminate\Database\Eloquent\Model;

use App\UserableNohistory;

class InfectionmonitorDomestic extends Model
{
    // 保存時のユーザー関連データの保持
    use UserableNohistory;

    // 更新する項目の定義
    protected $fillable = ['date', 'daily_infections', 'sum_infections', 'daily_deaths', 'sum_deaths', 'previous_week_ratio', 'week_ratio'];
}
