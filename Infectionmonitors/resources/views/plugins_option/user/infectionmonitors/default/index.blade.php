{{--
 * 表示画面テンプレート（デフォルト）
 *
 * @author 永原　篤 <nagahara@opensource-workshop.jp>
 * @copyright OpenSource-WorkShop Co.,Ltd. All Rights Reserved
 * @category Infectionmonitor プラグイン
 --}}
@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

<form action="{{url('/')}}/plugin/infectionmonitors/index/{{$page->id}}/{{$frame_id}}#frame-{{$frame_id}}" method="POST">
    {{ csrf_field() }}
<div class="form-group form-row">
    <div class="col-sm">
        <select name="prefecture_code" class="custom-select" onchange="javascript:submit(this.form);">
            <option value="">都道府県</option>
            @foreach($prefectures as $loop_prefecture_code => $loop_prefecture_name)
                <option value="{{$loop_prefecture_code}}" @if (old('prefecture_code', $prefecture_code) == $loop_prefecture_code) selected="selected" @endif>{{$loop_prefecture_name}}</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm">
        <select name="term" class="custom-select" onchange="javascript:submit(this.form);">
            <option selected>表示期間</option>
            <option value="1"   @if (old('term', $term) == '1') selected="selected" @endif>1ヶ月</option>
            <option value="2"   @if (old('term', $term) == '2') selected="selected" @endif>2ヶ月</option>
            <option value="3"   @if (old('term', $term) == '3') selected="selected" @endif>3ヶ月</option>
            <option value="6"   @if (old('term', $term) == '6') selected="selected" @endif>6ヶ月</option>
            <option value="12"  @if (old('term', $term) == '12') selected="selected" @endif>1年</option>
            <option value="all" @if (old('term', $term) == 'all') selected="selected" @endif>全件</option>
        </select>
    </div>
    <div class="col-sm">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" name="domestic_view" value="on" class="custom-control-input" id="domestic_view" onchange="javascript:submit(this.form);" @if (old('domestic_view', $domestic_view) == 'on') checked="checked" @endif>
            <label class="custom-control-label" for="domestic_view">全国も表示</label>
        </div>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" name="previous_week_ratio_view" value="on" class="custom-control-input" id="previous_week_ratio_view" onchange="javascript:submit(this.form);" @if (old('previous_week_ratio_view', $previous_week_ratio_view) == 'on') checked="previous_week_ratio_view" @endif>
            <label class="custom-control-label" for="previous_week_ratio_view">前週同曜日比率も表示</label>
        </div>
    </div>
    <div class="col-sm">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" name="daily_infections" value="on" class="custom-control-input" id="daily_infections" onchange="javascript:submit(this.form);" @if (old('daily_infections', $daily_infections) == 'on') checked="checked" @endif>
            <label class="custom-control-label" for="daily_infections">都道府県の感染者数も表示</label>
        </div>
    </div>
</div>
</form>

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
            ['日付'
                @if ($view_keys['prefecture_previous_week_ratio']['view'] == 'on') ,'{{$prefectures[$prefecture_code]}}-前週同曜日比率' @endif
                @if ($view_keys['prefecture_week_ratio']['view']          == 'on') ,'{{$prefectures[$prefecture_code]}}-週間比率' @endif
                @if ($view_keys['domestic_previous_week_ratio']['view']   == 'on') ,'全国-前週同曜日比率' @endif
                @if ($view_keys['domestic_view']['view']                  == 'on') ,'全国-週間比率' @endif
                @if ($view_keys['daily_infections']['view']               == 'on') ,'感染者数' @endif
            ],
            @foreach($infections as $date => $infection)
                ['{{$date}}'
                @if ($previous_week_ratio_view == 'on') , {{$infection['prefecture']->previous_week_ratio}} @endif
                @if ($view_keys['prefecture_week_ratio']['view']          == 'on') ,{{$infection['prefecture']->week_ratio}} @endif
                @if ($view_keys['domestic_previous_week_ratio']['view']   == 'on') ,{{$infection['domestic']->previous_week_ratio}} @endif
                @if ($view_keys['domestic_view']['view']                  == 'on') ,{{$infection['domestic']->week_ratio}} @endif
                @if ($view_keys['daily_infections']['view']               == 'on') ,{{$infection['prefecture']->prefecture_daily_infections}} @endif
                @if ($loop->last) ] @else ], @endif
            @endforeach
        ]);

        // オプションの設定
        var options = {
            title: '感染者数増減の前週比率等',
            vAxes: {
                0: {
                    title: '比率（%）',
                    format: '#\'%\'',
                },
                1: {
                    title: '感染者数',
                }
            },
            series:[
                @foreach($view_keys as $view_key)
                    @if ($view_key['view'] == 'on' && $view_key['position'] == 0)
                        {targetAxisIndex:0}, // 第1系列は左のY軸を使用
                    @elseif($view_key['view'] == 'on' && $view_key['position'] == 1)
                        {targetAxisIndex:1}, // 第1系列は右のY軸を使用
                    @endif
                @endforeach
            ],
        };

        // 指定されたIDの要素に折れ線グラフを作成
        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));

        // グラフの描画
        chart.draw(data, options);
    }

</script>

<!--  グラフの描画エリア -->
<div id="chart_div" style="width: 100%; height: 500px"></div>

<hr />

<p style="line-height:0.9rem;">
<small>
<b>新型コロナ関連の情報提供:NHK</b><br />
予測などの計算、およびグラフなどの表示・編集は当システム作者の永原篤が行っているものであり、NHKには関係がありません。<br />
NHKには、有用なデータの提供を行っていただいていることに感謝いたします。<br />
NHKのデータ利用規約は以下になります。<br />
<a href="https://www3.nhk.or.jp/news/special/coronavirus/data/rules.html" target="_blank">https://www3.nhk.or.jp/news/special/coronavirus/data/rules.html</a>
</small>
</p>

<p style="line-height:0.9rem;">
<small>
<b>グラのライブラリ:Google Charts</b><br />
<a href="https://developers.google.com/chart" target="_blank">https://developers.google.com/chart</a>
</small>
</p>

@endsection
