<?php

namespace App\ModelsOption\User\Infectionmonitors;

use Illuminate\Database\Eloquent\Model;

use App\UserableNohistory;

class InfectionmonitorPrefecture extends Model
{
    // 保存時のユーザー関連データの保持
    use UserableNohistory;

    // 更新する項目の定義
    //protected $fillable = ['date', 'prefecture_code', 'prefecture_name', 'prefecture_daily_infections', 'prefecture_sum_infections', 'prefecture_daily_deaths', 'prefecture_sum_deaths', 'infected_per100000', 'previous_week_ratio', 'week_ratio'];
}
