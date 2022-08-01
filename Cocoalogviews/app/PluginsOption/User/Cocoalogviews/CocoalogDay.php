<?php

namespace App\PluginsOption\User\Cocoalogviews;

use Carbon\Carbon;

/**
 * Cocoaログの日単位のオブジェクト
 *
 * @author 永原　篤 <nagahara@opensource-workshop.jp>
 * @copyright OpenSource-WorkShop Co.,Ltd. All Rights Reserved
 * @category CocoaLogView プラグイン
 * @package Controller
 */
class CocoalogDay
{
    /* オブジェクト変数 */
    public $date = null;
    public $format_date = null;
    public $score_sum = 0;
    public $maximum_score = 0;
    public $count = 0;
    public $totaltime = 0;
    public $calendar_event = "";

    // 最大リスク設定
    public $max_scale = 3000;

    /**
     * コンストラクタ
     *
     * @date Y-m-d 形式の日付
     */
    function __construct($date) {
        $this->date = $date;
        $carbon = new Carbon($date);
        $this->format_date = $carbon->isoFormat('YYYY年MM月DD(ddd)');;
    }

    /**
     * 日付配列を返す。
     */
    private function getDayLog()
    {
        //return $this->dates;
    }

    /**
     * 接触ログを保持する。(daily_summariesの項目)
     */
    public function setDailySummaries($daily_summaries)
    {
        $this->score_sum = $daily_summaries['DaySummary']['ScoreSum'];
        $this->maximum_score = $daily_summaries['DaySummary']['MaximumScore'];
    }

    /**
     * 接触ログを保持する。(exposure_windowsの項目)
     */
    public function setExposureWindows($exposure_windows)
    {
        $this->count++;
        foreach ($exposure_windows['ScanInstances'] as $scanInstances) {
            $this->totaltime += $scanInstances['SecondsSinceLastScan'];
        }
    }

    /**
     * 接触時間合計
     */
    public function getTotaltime()
    {
        $hours = floor($this->totaltime / 3600); //時間
        $minutes = floor(($this->totaltime / 60) % 60); //分
        $seconds = floor($this->totaltime % 60); //秒
        $hms = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        return $hms;
    }

    /**
     * 行の背景色
     */
    public function getBackgroundColor()
    {
        // ScoreSum が 0 の場合は背景は白
        if ($this->score_sum == 0) {
            return "#ffffff";
        }

        // 最大リスク設定値を256階調で割り、目盛りを作成。ScoreSumを目盛りで割り、個別の階調を算出。
        $tmp1 = ceil($this->max_scale / 256);
        $tmp2 = ceil($this->score_sum / $tmp1);
        $tmp2 = $tmp2 > 256 ? $tmp2 = 256 : $tmp2;

        return "#ff" . $this->getHex(256 - $tmp2) . $this->getHex(256 - $tmp2) . ";";
    }

    /**
     * 行の背景色
     */
    private function getHex($num)
    {
        $tmp = dechex($num);
        if (strlen($tmp) == 1) {
            return "0" . $tmp;
        }
        return $tmp;
    }
}
