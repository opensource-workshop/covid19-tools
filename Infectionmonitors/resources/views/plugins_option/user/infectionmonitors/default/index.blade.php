{{--
 * 表示画面テンプレート（デフォルト）
 *
 * @author 永原　篤 <nagahara@opensource-workshop.jp>
 * @copyright OpenSource-WorkShop Co.,Ltd. All Rights Reserved
 * @category Infectionmonitor プラグイン
 --}}
@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">

    // ライブラリのロード
    // name:visualization(可視化),version:バージョン(1),packages:パッケージ(corechart)
    google.load('visualization', '1', {'packages':['corechart']});

    // グラフを描画する為のコールバック関数を指定
    google.setOnLoadCallback(drawChart);

    // グラフの描画
    function drawChart() {

        // 配列からデータの生成
        var data = google.visualization.arrayToDataTable([
            ['日付'   , '前週同曜日比率', '週間比率'],
@foreach($infections as $infection)
    @if ($loop->last)
            ['{{$infection->date}}', {{$infection->previous_week_ratio}}, {{$infection->week_ratio}}]
    @else
            ['{{$infection->date}}', {{$infection->previous_week_ratio}}, {{$infection->week_ratio}}],
    @endif
@endforeach
        ]);

        // オプションの設定
        var options = {
            title: '感染者数の比率遷移'
        };

{{--
        // 配列からデータの生成
        var data = google.visualization.arrayToDataTable([
            ['年度'   , '所得税', '法人税','消費税'],
            ['H19年度',  16.08, 14.74, 10.27],
            ['H20年度',  14.99, 10.01,  9.97],
            ['H21年度',  12.91,  6.36,  9.81],
            ['H22年度',  12.98,  8.97, 10.03],
            ['H23年度',  13.48,  9.35, 10.19],
            ['H24年度',  13.99,  9.76, 10.35],
            ['H25年度',  15.53, 10.49, 10.83] 
        ]);

        // オプションの設定
        var options = {
            title: '所得税・法人税・消費税の年間推移 ( 単位：兆円 )'
        };
--}}

        // 指定されたIDの要素に折れ線グラフを作成
        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));

        // グラフの描画
        chart.draw(data, options);
    }

</script>

<!--  グラフの描画エリア -->
<div id="chart_div" style="width: 100%; height: 350px"></div>

<hr />

<p style="line-height:0.9rem;">
<small>
「新型コロナ関連の情報提供:NHK」<br />
予測などの計算、およびグラフなどの表示・編集は当システム作者の永原篤が行っているものであり、NHKには関係がありません。<br />
NHKには、有用なデータの提供を行っていただいていることに感謝いたします。<br />
NHKのデータ利用規約は以下になります。<br />
https://www3.nhk.or.jp/news/special/coronavirus/data/rules.html
</small>
</p>

@endsection
