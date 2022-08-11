<?php

namespace App\PluginsOption\User\Cocoalogviews;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

use App\PluginsOption\User\Cocoalogviews\CocoalogDay;

use App\Models\Common\Buckets;
use App\Models\Common\Frame;
use App\Models\Core\Configs;

use App\PluginsOption\User\UserPluginOptionBase;

/**
 * CocoaLogView プラグイン
 *
 * DB 定義コマンド
 * DBなし
 *
 * @author 永原　篤 <nagahara@opensource-workshop.jp>
 * @copyright OpenSource-WorkShop Co.,Ltd. All Rights Reserved
 * @category CocoaLogView プラグイン
 * @package Controller
 */
class CocoalogviewsPlugin extends UserPluginOptionBase
{
    /* オブジェクト変数 */

    /* コアから呼び出す関数 */

    /**
     * 関数定義（コアから呼び出す）
     */
    public function getPublicFunctions()
    {
        // 標準関数以外で画面などから呼ばれる関数の定義
        $functions = array();
        $functions['get']  = ['index'];
        $functions['post']  = ['index', 'viewJson'];
        return $functions;
    }

    /**
     *  権限定義
     */
    public function declareRole()
    {
        // 権限チェックテーブル
        $role_check_table = array();
        return $role_check_table;
    }

    /* 画面アクション関数 */

    /**
     * データ初期表示関数
     * コアがページ表示の際に呼び出す関数
     *
     * @method_title 初期画面
     * @method_desc ログ貼り付け、結果一覧を表示します。
     * @method_detail
     */
    public function index($request, $page_id, $frame_id, $post_id = null)
    {
        // json をテキストエリアで受け取り
        $json_name = "json." . $frame_id;
        $json = $request->$json_name;

        // calendar をテキストエリアで受け取り
        $calendar_name = "calendar." . $frame_id;
        $calendar = $request->$calendar_name;

        // 表示テンプレートを呼び出す。
        return $this->view('index', [
            "json" => $json,
            "calendar" => $calendar,
        ]);
    }

    /**
     * COCOAログを解析し、画面に表示します。
     */
    public function viewJson($request, $page_id, $frame_id)
    {
        // json をテキストエリアで受け取り
        $json_name = "json." . $frame_id;
        $json = $request->$json_name;

        // 表示する最低スコアの設定
        $cut_score = 100;

        // json のデコード
        $exposure_array = json_decode($json, true);

        // json チェック(形式チェック)
        if (empty($exposure_array)) {
            $validator = Validator::make($request->all(), []);
            $validator->errors()->add('json.' . $frame_id, 'ログデータが正しくありません。（JSON形式エラー）');
            return $this->index($request, $page_id, $frame_id)->withErrors($validator);
        }
        // json チェック(データ件数チェック)
        if (!array_key_exists('daily_summaries', $exposure_array) || count($exposure_array['daily_summaries']) == 0) {
            $validator = Validator::make($request->all(), []);
            $validator->errors()->add('json.' . $frame_id, 'ログデータが正しくありません。（daily_summaries がないか0件です。）');
            return $this->index($request, $page_id, $frame_id)->withErrors($validator);
        }
        // json チェック(データ件数チェック)
        if (!array_key_exists('exposure_windows', $exposure_array) || count($exposure_array['exposure_windows']) == 0) {
            $validator = Validator::make($request->all(), []);
            $validator->errors()->add('json.' . $frame_id, 'ログデータが正しくありません。（exposure_windows がないか0件です。）');
            return $this->index($request, $page_id, $frame_id)->withErrors($validator);
        }

        // 表示用の配列(日ごとのログクラスを配列に格納)
        $dates = array();

        // json を変換した配列の最初(ログの最初の日)から今日(システム日)までを配列に用意する。
        // 接触ログには接触がないと、その日のログはないが、表示では連続した日にしたいため、ここで連続した日を用意しておく。
        $first = current($exposure_array['daily_summaries']);
        $start = date('Y-m-d', substr($first['DateMillisSinceEpoch'], 0, 10));
        $end = date('Y-m-d');
        for ($i = $start; $i <= $end; $i = date('Y-m-d', strtotime($i . '+1 day'))) {
            // ココアログ表示クラスに値を保持する。
            $dates[$i] = new CocoalogDay($i);
        }

        // daily_summaries の処理
        foreach ($exposure_array['daily_summaries'] as $key => $daily_summaries) {
            // 接触日付を使って、該当の日のログオブジェクトを呼び出す。
            $date = date('Y-m-d', substr($daily_summaries['DateMillisSinceEpoch'], 0, 10));
            $dates[$date]->setDailySummaries($daily_summaries);
        }

        // exposure_windows の処理
        foreach ($exposure_array['exposure_windows'] as $key => $exposure_windows) {
            // 接触日付を使って、該当の日のログオブジェクトを呼び出す。
            $date = date('Y-m-d', substr($exposure_windows['DateMillisSinceEpoch'], 0, 10));
            $dates[$date]->setExposureWindows($exposure_windows);
        }

        // calendar をテキストエリアで受け取り。日付オブジェクトにセットする。
        $calendar_name = "calendar." . $frame_id;
        $calendar_textarea = trim($request->$calendar_name);
        if ($calendar_textarea) {
            $calendar_array = explode("\n", $calendar_textarea);
            if (is_array($calendar_array) && count($calendar_array) > 1) {
                foreach($calendar_array as $calendar_row) {
                    $calendar_day_event = explode(",", $calendar_row);
                    $calendar_day_ymd = date('Y-m-d', strtotime($calendar_day_event[0]));
                    if (array_key_exists($calendar_day_ymd, $dates)) {
                        $dates[$calendar_day_ymd]->calendar_event = $calendar_day_event[1];
                    }
                }
            }
        }

        // 表示テンプレートを呼び出す。
        return $this->view('index', [
            "json"  => $json,
            "calendar" => $calendar_textarea,
            "dates" => $dates,
        ]);
    }
}
