<?php

namespace App\PluginsOption\User\Infectionmonitors;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\LazyCollection;

use App\Models\Common\Buckets;
use App\Models\Common\Frame;
use App\Models\Core\Configs;

use App\ModelsOption\User\Infectionmonitors\InfectionmonitorDomestic;
use App\ModelsOption\User\Infectionmonitors\InfectionmonitorPrefecture;

use App\PluginsOption\User\UserPluginOptionBase;

/**
 * Infectionmonitors プラグイン
 *
 * DB 定義コマンド
 * DBなし
 *
 * @author 永原　篤 <nagahara@opensource-workshop.jp>
 * @copyright OpenSource-WorkShop Co.,Ltd. All Rights Reserved
 * @category Infectionmonitors プラグイン
 * @package Controller
 */
class InfectionmonitorsPlugin extends UserPluginOptionBase
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

    /**
     * 都道府県取得
     */
    private function getPrefectureName($prefecture_code)
    {
        $prefectures = $this->getPrefectures();
        return $prefectures[$prefecture_code];
    }

    /**
     * 都道府県取得
     */
    private function getPrefectures()
    {
        return [
            '01' => '北海道', '02' => '青森県', '03' => '岩手県', '04' => '宮城県', '05' => '秋田県',
            '06' => '山形県', '07' => '福島県', '08' => '茨城県', '09' => '栃木県', '10' => '群馬県',
            '11' => '埼玉県', '12' => '千葉県', '13' => '東京都', '14' => '神奈川県', '15' => '新潟県',
            '16' => '富山県', '17' => '石川県', '18' => '福井県', '19' => '山梨県', '20' => '長野県',
            '21' => '岐阜県', '22' => '静岡県', '23' => '愛知県', '24' => '三重県', '25' => '滋賀県',
            '26' => '京都府', '27' => '大阪府', '28' => '兵庫県', '29' => '奈良県', '30' => '和歌山県',
            '31' => '鳥取県', '32' => '島根県', '33' => '岡山県', '34' => '広島県', '35' => '山口県',
            '36' => '徳島県', '37' => '香川県', '38' => '愛媛県', '39' => '高知県', '40' => '福岡県',
            '41' => '佐賀県', '42' => '長崎県', '43' => '熊本県', '44' => '大分県', '45' => '宮崎県',
            '46' => '鹿児島県', '47' => '沖縄県'
        ];
    }

    /**
     * データ取得
     */
    private function getCsv($file_name)
    {
        // データURL
        $request_url = "https://www3.nhk.or.jp/n-data/opendata/coronavirus/" . $file_name;

        // NHK からデータ取得
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_ENCODING, "gzip");

        //リクエストヘッダ出力設定
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        // データ取得実行
        $http_str = curl_exec($ch);

        // HTTPヘッダ取得
        $http_header = curl_getinfo($ch);
        if (empty($http_header) || !array_key_exists('http_code', $http_header) || $http_header['http_code'] != 200) {
            // データが取得できなかったため、スルー。
            return;
        }

        // ファイルに保存
        \Storage::put('plugins/infectionmonitors/' . $file_name, $http_str);

        return;
    }

    /**
     * 全国データのインポート
       曜日配列 を用意して、常に更新していく。
       [
           "previous" => [  // 前の一週間
               0 => 10,     // Sun
               1 => 11,     // Mon
               2 => 12,     // Tue
               3 => 13,     // Wed
               4 => 14,     // Thu
               5 => 15,     // Fri
               6 => 16,     // Sat
           ],
           "recent" => [    // 直近一週間
               0 => 10,     // Sun
               1 => 11,     // Mon
               2 => 12,     // Tue
               3 => 13,     // Wed
               4 => 14,     // Thu
               5 => 15,     // Fri
               6 => 16,     // Sat
           ]
       ]
     */
    private function importDomesticsCsv($date = null)
    {
        // 曜日配列の準備
        $infection_count = ["previous" => [0, 0, 0, 0, 0, 0, 0], "recent" => [0, 0, 0, 0, 0, 0, 0]];

        LazyCollection::make(function () {
            $filePath = storage_path('app/plugins/infectionmonitors/nhk_news_covid19_domestic_daily_data.csv');
            $file = new \SplFileObject($filePath);
            $file->setFlags(\SplFileObject::READ_CSV);
            foreach ($file as $lines) {
                yield $lines;
            }
        })
            ->skip(1)     //ヘッダー行をスキップ
            ->chunk(1000) //分割
            ->each(function ($lines) use ($date, &$infection_count) {
                $data = [];
                foreach($lines as $line){
                    if (is_array($line) && count($line) == 5) {
                        // 週の数値の前週への繰り越しと今日の代入
                        $week_no = date('w', strtotime($line[0]));
                        $infection_count["previous"][$week_no] = $infection_count["recent"][$week_no];
                        $infection_count["recent"][$week_no] = intval($line[1]);

                        // DBへ保存する準備
                        $line[0] = date('Y-m-d', strtotime($line[0]));
                        if (empty($date) || (!empty($date) && $line[0] > $date)) {
                            $data[] = [
                                'date'                  => $line[0],
                                'daily_infections'      => $line[1],
                                'sum_infections'        => $line[2],
                                'daily_deaths'          => $line[3],
                                'sum_deaths'            => $line[4],
                                'difference_infections' => intval($line[1]) - $infection_count["previous"][$week_no],
                                'previous_week_ratio'   => $infection_count["previous"][$week_no] > 0 ? round(intval($line[1]) / $infection_count["previous"][$week_no] * 100, 1) : 0,
                                'week_ratio'            => array_sum($infection_count["previous"]) > 0 ? round(array_sum($infection_count["recent"]) / array_sum($infection_count["previous"]) * 100, 1) : 0,
                            ];
                        }
                    }
                }
                //バルクインサート
                InfectionmonitorDomestic::insert($data);
            });

        return;
    }

    /**
     * 都道府県データのインポート
     * 日本語を含むCSVでは、LazyCollection や fgetcsv では、日本語項目の直後のカンマが認識されないなど問題がったため、基礎的な実装とする。
     */
    private function importPrefecturesCsv($date = null)
    {
        if (($handle = fopen(storage_path('app/plugins/infectionmonitors/nhk_news_covid19_prefectures_daily_data.csv'), "r")) !== FALSE) {
            fgets($handle); // １行目（タイトル）は捨てる。

            // 曜日配列の準備（前週、今週、その下に都道府県、曜日の配列）
            $infection_count = ["previous" => [], "recent" => []];
            for ($i = 1; $i <= 47; $i++) {
                foreach ($infection_count as &$infection_item) {
                    $infection_item[sprintf("%02d", $i)] = [0, 0, 0, 0, 0, 0, 0];  // 都道府県コードは 01 と 2桁の 0埋め
                }
            }

            $count = 0;
            while (($line = fgets($handle)) !== FALSE) {
                $line= trim($line);
                if (empty($line)) {
                    continue;
                }

                $cols = explode(',', $line);
                $cols[0] = date('Y-m-d', strtotime($cols[0]));

                // 週の数値の前週への繰り越しと今日の代入（対象日付の判断の前でないと、過去データの集計ができないので注意）
                $week_no = date('w', strtotime($cols[0]));
                $infection_count["previous"][$cols[1]][$week_no] = $infection_count["recent"][$cols[1]][$week_no];
                $infection_count["recent"][$cols[1]][$week_no] = intval($cols[3]);

                // 日付指定時の対象判断
                if (!empty($date) && $cols[0] <= $date) {
                    continue;
                }

                $data[] = [
                    'date'                        => $cols[0],
                    'prefecture_code'             => $cols[1],
                    'prefecture_name'             => $cols[2],
                    'prefecture_daily_infections' => $cols[3],
                    'prefecture_sum_infections'   => $cols[4],
                    'prefecture_daily_deaths'     => $cols[5],
                    'prefecture_sum_deaths'       => $cols[6],
                    'difference_infections'       => intval($cols[3]) - $infection_count["previous"][$cols[1]][$week_no],
                    'infected_per100000'          => empty($cols[7]) ? 0 : $cols[7],
                    'previous_week_ratio'         => $infection_count["previous"][$cols[1]][$week_no] > 0 ? round(intval($cols[3]) / $infection_count["previous"][$cols[1]][$week_no] * 100, 1) : 0,
                    'week_ratio'                  => array_sum($infection_count["previous"][$cols[1]]) > 0 ? round(array_sum($infection_count["recent"][$cols[1]]) / array_sum($infection_count["previous"][$cols[1]]) * 100, 1) : 0,
                ];

                $count++;
                if ($count >= 1000) {
                    //バルクインサート
                    InfectionmonitorPrefecture::insert($data);
                    $data = [];
                    $count = 0;
                }
            }
            if (!empty($data)) {
                //バルクインサート
                InfectionmonitorPrefecture::insert($data);
            }

            fclose($handle);
        }
    }

    /* 画面アクション関数 */

    /**
     * データ初期表示関数
     * コアがページ表示の際に呼び出す関数
     *
     * @method_title 記事編集
     * @method_desc 記事一覧を表示します。
     * @method_detail
     */
    public function index($request, $page_id, $frame_id, $post_id = null)
    {
        // ---------------------------------------
        // - データの確認と取得
        // ---------------------------------------

        // データが１件もない場合は、全件をバルクインポートする。
        if (InfectionmonitorDomestic::count() == 0) {
            // データ取得
            $this->getCsv('nhk_news_covid19_domestic_daily_data.csv');
            // バルクインポート
            $this->importDomesticsCsv();
        }

        if (InfectionmonitorPrefecture::count() == 0) {
            // データ取得
            $this->getCsv('nhk_news_covid19_prefectures_daily_data.csv');
            // バルクインポート
            $this->importPrefecturesCsv();
        }

        // 最新日付のデータを取得し、それが昨日よりも前の場合は、データを取りに行き、インポートされていない日のデータをインポートする。
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // 全国のデータ
        $last_domestic = InfectionmonitorDomestic::orderBy('date', 'desc')->first();
        if (!empty($last_domestic) && $yesterday > $last_domestic->date) {
            // データ取得
            $this->getCsv('nhk_news_covid19_domestic_daily_data.csv');
            // バルクインサート
            $this->importDomesticsCsv($last_domestic->date);
        }

        // 都道府県のデータ
        $last_prefecture = InfectionmonitorPrefecture::orderBy('date', 'desc')->first();
        if (!empty($last_prefecture) && $yesterday > $last_prefecture->date) {
            // データ取得
            $this->getCsv('nhk_news_covid19_prefectures_daily_data.csv');
            // バルクインサート
            $this->importPrefecturesCsv($last_prefecture->date);
        }

        // ---------------------------------------
        // - データの表示用処理
        // ---------------------------------------

        // 指定の日付
        $term = empty($request->term) ? 3 : $request->term;
        $month_ago = null;
        if ($term == '1' || '2' || '3' || '6' || '12') {
            $month_ago = date('Y-m-d', strtotime('-' . $term . ' month'));
        }

        // 指定の都道府県の取得（デフォルトは東京都）
        $prefecture_code = empty($request->prefecture_code) ? 13 : $request->prefecture_code;
        if (empty($month_ago)) {
            $prefecture_infections = InfectionmonitorPrefecture::where('prefecture_code', $prefecture_code)->orderBy('date', 'asc')->get();
        } else {
            $prefecture_infections = InfectionmonitorPrefecture::where('prefecture_code', $prefecture_code)->where('date', '>=', $month_ago)->orderBy('date', 'asc')->get();
        }

        // 全国の取得
        if (empty($month_ago)) {
            $domestic_infections = InfectionmonitorDomestic::orderBy('date', 'asc')->get();
        } else {
            $domestic_infections = InfectionmonitorDomestic::where('date', '>=', $month_ago)->orderBy('date', 'asc')->get();
        }

        // 都道府県と全国を日付をキーにした１つの配列にマージする（グラフ生成時にループで処理しやすいように）
        $infections = array();
        foreach ($prefecture_infections as $prefecture_infection) {
            $infections[$prefecture_infection->date]['prefecture'] = $prefecture_infection;
        }
        foreach ($domestic_infections as $domestic_infection) {
            $infections[$domestic_infection->date]['domestic'] = $domestic_infection;
        }

        // 表示する項目のキー配列を生成（Viewをシンプルにするため）
        $view_keys = [
            'prefecture_previous_week_ratio' => [
                'view' => $request->previous_week_ratio_view,
                'position' => 0
            ],
            'prefecture_week_ratio' => [
                'view' => 'on',
                'position' => 0
            ],
            'domestic_previous_week_ratio' => [
                'view' => ($request->previous_week_ratio_view == 'on' && $request->domestic_view == 'on') ? 'on' : null,
                'position' => 0
            ],
            'domestic_view' => [
                'view' => $request->domestic_view,
                'position' => 0
            ],
            'daily_infections' => [
                'view' => $request->daily_infections,
                'position' => 1
            ],
        ];

        // 表示テンプレートを呼び出す。
        return $this->view('index', [
            "prefectures"      => $this->getPrefectures(),    // 都道府県リスト
            "infections"       => $infections,                // 感染者数、前週比較の配列
            "prefecture_code"  => $prefecture_code,           // 画面選択値（都道府県）
            "term"             => $term,                      // 画面選択値（期間）
            "domestic_view"    => $request->domestic_view,    // 画面選択値（都道府県のみ）
            "previous_week_ratio_view" => $request->previous_week_ratio_view,  // 画面選択値（週間比率のみ）
            "daily_infections" => $request->daily_infections, // 画面選択値（都道府県の感染者数も表示）
            "view_keys"        => $view_keys,                 // 表示する項目のキー配列
        ]);
    }
}
