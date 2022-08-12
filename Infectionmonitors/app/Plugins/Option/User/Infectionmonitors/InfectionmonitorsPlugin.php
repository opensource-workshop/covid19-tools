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
//        $functions['get']  = ['index'];
//        $functions['post']  = ['index', 'viewJson'];
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
     */
    private function importDomesticsCsv($date = null)
    {
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
            ->each(function ($lines) use ($date) {
                $data = [];
                foreach($lines as $line){
                    if (is_array($line) && count($line) == 5) {
                        $line[0] = date('Y-m-d', strtotime($line[0]));
                        if (empty($date) || (!empty($date) && $line[0] > $date)) {
                            $data[] = [
                                'date'             => $line[0],
                                'daily_infections' => $line[1],
                                'sum_infections'   => $line[2],
                                'daily_deaths'     => $line[3],
                                'sum_deaths'       => $line[4]
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

            $count = 0;
            while (($line = fgets($handle)) !== FALSE) {
                $line= trim($line);
                if (empty($line)) {
                    continue;
                }

                $cols = explode(',', $line);
                $cols[0] = date('Y-m-d', strtotime($cols[0]));

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
                    'infected_per100000'          => empty($cols[7]) ? 0 : $cols[7]
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

    /**
     * 都道府県データのインポート
     */
    private function importPrefecturesCsv__($date = null)
    {
        // 
        $content = \Storage::get('plugins/infectionmonitors/nhk_news_covid19_prefectures_daily_data.csv');
        $content = mb_convert_encoding($content, "sjis-win", "UTF-8");
        $result = \Storage::put('plugins/infectionmonitors/nhk_news_covid19_prefectures_daily_data.csv', $content);


        LazyCollection::make(function () {
            $filePath = storage_path('app/plugins/infectionmonitors/nhk_news_covid19_prefectures_daily_data.csv');
            $file = new \SplFileObject($filePath);
            $file->setFlags(\SplFileObject::READ_CSV);

            foreach ($file as $lines) {
                yield $lines;
            }
        })
            ->skip(1)     //ヘッダー行をスキップ
            ->chunk(1000) //分割
            ->each(function ($lines) {
                $data = [];
                foreach($lines as $line){
                    if (empty($date) || (!empty($date) && $line[0] > $date)) {
                        if (count($line) >= 7) {
                            $data[] = [
                                'date'                        => $line[0],
                                'prefecture_code'             => $line[1],
                                'prefecture_name'             => mb_convert_encoding($line[2], "UTF-8", "sjis-win"),
                                //'prefecture_name'             => $line[2],
                                'prefecture_daily_infections' => $line[3],
                                'prefecture_sum_infections'   => $line[4],
                                'prefecture_daily_deaths'     => $line[5],
                                'prefecture_sum_deaths'       => $line[6],
                                'infected_per100000'          => empty($line[7]) ? 0 : $line[7]
                            ];
                        }
                    }
                }
                //バルクインサート
                InfectionmonitorPrefecture::insert($data);
            });

        return;
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
        $yesterday = date('Y-m-d', strtotime('-1 day'));;

        $last_domestic = InfectionmonitorDomestic::orderBy('date', 'desc')->first();
        if (!empty($last_domestic) && $yesterday > $last_domestic->date) {
            // データ取得
            $this->getCsv('nhk_news_covid19_domestic_daily_data.csv');
            // バルクインポート
            $this->importDomesticsCsv($last_domestic->date);
        }

        $last_prefecture = InfectionmonitorPrefecture::orderBy('date', 'desc')->first();
        if (!empty($last_prefecture) && $yesterday > $last_prefecture->date) {
            // データ取得
            $this->getCsv('nhk_news_covid19_prefectures_daily_data.csv');
            // バルクインポート
            $this->importPrefecturesCsv($last_prefecture->date);
        }

        // ---------------------------------------
        // - データの表示用処理
        // ---------------------------------------

$infections = 'test';

        // 表示テンプレートを呼び出す。
        return $this->view('index', [
            "infections" => $infections,
        ]);
    }
}
