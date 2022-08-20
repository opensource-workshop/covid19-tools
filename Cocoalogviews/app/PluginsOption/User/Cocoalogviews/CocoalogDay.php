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
    public $date_ym = null;
    public $score_sum = 0;
    public $maximum_score = 0;
    public $calendar_event = "";

    // 同じ日の中の exposure_windows の値の配列
    public $exposure_windows = array();

    // 最大リスク設定
    public static $max_scale = 3000;

    /**
     * コンストラクタ
     *
     * @date Y-m-d 形式の日付
     */
    function __construct($date) {
        $this->date = $date;
        $carbon = new Carbon($date);
        $this->format_date = $carbon->isoFormat('YYYY年MM月DD(ddd)');
        $this->date_ym = $carbon->isoFormat('YYYY年MM月');
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
        $this->exposure_windows[] = $exposure_windows;
    }

    /**
     * 時間フォーマット
     */
    public function getFormatTime($format, $seconds)
    {
        $hours = floor($seconds / 3600); //時間
        $minutes = floor(($seconds / 60) % 60); //分
        $seconds = floor($seconds % 60); //秒
        if ($format == "i:s") {
            $ret = sprintf("%02d:%02d", $minutes, $seconds);
        } else {
            $ret = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        }
        return $ret;
    }

    /**
     * 接触時間合計
     */
    public function getTotaltime()
    {
        $totaltime = 0;
        foreach ($this->exposure_windows as $exposure_window) {
            if (array_key_exists('ScanInstances', $exposure_window)) {
                foreach ($exposure_window['ScanInstances'] as $scan_instance) {
                    $totaltime += array_key_exists('SecondsSinceLastScan', $scan_instance) ? $scan_instance['SecondsSinceLastScan'] : 0;
                }
            }
        }

        return $this->getFormatTime('H:i:s', $totaltime);
    }

    /**
     * カウントの取得
     */
    public function getCount()
    {
        return count($this->exposure_windows);
    }

    /**
     * ScanInstances のSecondsSinceLastScan の合計
     */
    public function sumSecondsSinceLastScan($exposure_window)
    {
        $sum_seconds_since_last_scan = 0;
        foreach ($exposure_window['ScanInstances'] as $scan_instance) {
            $sum_seconds_since_last_scan += $scan_instance["SecondsSinceLastScan"];
        }
        return $this->getFormatTime('H:i:s', $sum_seconds_since_last_scan);
    }

    /**
     * ScanInstances のSecondsSinceLastScan の取得
     */
    public function getSecondsSinceLastScan($exposure_window)
    {
        $array_seconds_since_last_scan = array();
        foreach ($exposure_window['ScanInstances'] as $scan_instance) {
            $array_seconds_since_last_scan[] = $scan_instance["SecondsSinceLastScan"];
        }
        return implode(',', $array_seconds_since_last_scan);
    }

    /**
     * 行の背景色
     */
    public static function getBackgroundColor($score_sum)
    {
        // ScoreSum が 0 の場合は背景は白
        if ($score_sum == 0) {
            return "#ffffff";
        }

        // 最大リスク設定値を256階調で割り、目盛りを作成。ScoreSumを目盛りで割り、個別の階調を算出。
        $tmp1 = ceil(self::$max_scale / 256);
        $tmp2 = ceil($score_sum / $tmp1);
        $tmp2 = $tmp2 > 256 ? $tmp2 = 256 : $tmp2;

        return "#ff" . self::getHex(256 - $tmp2) . self::getHex(256 - $tmp2) . ";";
    }

    /**
     * 行の背景色
     */
    private static function getHex($num)
    {
        $tmp = dechex($num);
        if (strlen($tmp) == 1) {
            return "0" . $tmp;
        }
        return $tmp;
    }
}
