{{--
 * 表示画面テンプレート（デフォルト）
 *
 * @author 永原　篤 <nagahara@opensource-workshop.jp>
 * @copyright OpenSource-WorkShop Co.,Ltd. All Rights Reserved
 * @category CocoaLogView プラグイン
 --}}
@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@php
    // 月毎の要約
    $month_digests = array();
    /*
        ["2022年08月" =>
            "max_score_sum" => [
                "date"      => "2022年08月03日",
                "score_sum" => "999"
            ],
            "min_attenuation_db" => [
                "date"               => "2022年08月11日",
                "min_attenuation_db" => "45",
                "scan_instance"      => {$scan_instance}
            ]
        ]
    */
@endphp

<form action="{{url('/')}}/plugin/cocoalogviews/viewJson/{{$page->id}}/{{$frame_id}}#frame-{{$frame->id}}" method="POST" class="" enctype="multipart/form-data">
    {{ csrf_field() }}

    <div class="form-group row">
        <label class="col-md-2 control-label text-md-right">COCOAログ（貼り付け or ファイル）</label>
        <div class="col-md-10">
            <textarea name="json[{{$frame_id}}]" class="form-control @if ($errors->has("json.$frame_id")) border-danger @endif" id="json{{$frame_id}}" rows=8>{!!old("json.$frame_id", $json)!!}</textarea>
            <div class="custom-file mt-1">
                <input type="file" class="custom-file-input" name="file[{{$frame_id}}]" id="file[{{$frame_id}}]">
                <label class="custom-file-label" for="file[{{$frame_id}}]" data-browse="ファイル選択">COCOAログファイル</label>
            </div>
            @if ($errors && $errors->has("json.$frame_id"))
                <div class="text-danger"><i class="fas fa-exclamation-circle"></i> {{$errors->first("json.*")}}</div>
            @endif
        </div>
    </div>

    <div class="form-group row">
        <label class="col-md-2 control-label text-md-right">スケジュール（貼り付け or ファイル）</label>
        <div class="col-md-10">
            <textarea name="calendar[{{$frame_id}}]" class="form-control @if ($errors->has("calendar.$frame_id")) border-danger @endif" id="calendar{{$frame_id}}" rows=8>{!!old("calendar.$frame_id", $calendar)!!}</textarea>
            <div class="custom-file mt-1">
                <input type="file" class="custom-file-input" name="calendar_file[{{$frame_id}}]" id="calendar_file[{{$frame_id}}]">
                <label class="custom-file-label" for="calendar_file[{{$frame_id}}]" data-browse="ファイル選択">スケジュールファイル</label>
            </div>
            @if ($errors && $errors->has("calendar.$frame_id"))
                <div class="text-danger"><i class="fas fa-exclamation-triangle"></i> {{$errors->first("calendar.*")}}</div>
            @endif
        </div>
    </div>

    <div class="mt-3 text-center">
        <input type="submit" class="btn btn-primary" value="アップロード＆解析" id="json_submit_{{$frame->id}}">
    </div>
</form>

<style type="text/css">
    .table_frame {
      height: 500px;
    }
    .hiddenRow {
        padding: 0 !important;
    }
</style>

@if(isset($dates))
<br />
<h5><span class="badge badge-pill badge-info">日毎の陽性者との接触記録</span></h5>
<div class="table_frame table-responsive">
<table class="table table-bordered table-sm">
<thead class="sticky-top bg-light">
    <tr>
        <th class="text-nowrap"></th>
        <th class="text-nowrap">日付</th>
        <th class="text-nowrap">接触者数</th>
        <th class="text-nowrap">記録時間合計</th>
        <th class="text-nowrap">スコア合計</th>
        <th class="text-nowrap">最大スコア</th>
        @if (!empty($calendar))
            <th class="text-nowrap">スケジュール</th>
        @endisset($calendar)
    </tr>
</thead>
<tbody>

@foreach ($dates as $key => $date)  {{-- $date = \CocoalogDay --}}
    @php
        // 配列準備(一通り、空で作っておく)
        if (!array_key_exists($date->date_ym, $month_digests)) {
            $month_digests[$date->date_ym] = [
                "max_score_sum" => [
                    "date" => '', "score_sum" => 0
                ],
                "min_attenuation_db" => [
                    "date" => '', "name" => "", "MinAttenuationDb" => 0, "SecondsSinceLastScan" => 0, "TypicalAttenuationDb" => 0
                ]
            ];
        }
        // スコア合計のチェック＆最大値の入れ替え
        if ($date->score_sum >= $month_digests[$date->date_ym]["max_score_sum"]["score_sum"]) {
            $month_digests[$date->date_ym]["max_score_sum"]["date"] = $date->format_date;
            $month_digests[$date->date_ym]["max_score_sum"]["score_sum"] = $date->score_sum;
        }
    @endphp
<tr style="background-color: {{$date->getBackgroundColor($date->score_sum)}}">
    @if ($date->getCount() > 0)
        <td class="text-nowrap text-center" style="cursor: pointer;" data-toggle="collapse" data-target="#R{{$date->date}}" class="accordion-toggle">
            <i class="fas fa-eye"></i>
        </td>
    @else
        <td></td>
    @endif
    <td class="text-nowrap">{{$date->format_date}}</td>
    <td>{{$date->getCount()}}</td>
    <td>{{$date->getTotaltime()}}</td>
    <td>{{$date->score_sum}}</td>
    <td>{{$date->maximum_score}}</td>
    @if (!empty($calendar))
        <td>{{$date->calendar_event}}</td>
    @endisset($calendar)
</tr>
<tr class="hiddenRow">
    @if (empty($calendar))
        <td colspan="6" class="py-0">
    @else
        <td colspan="7" class="py-0">
    @endif
        <div id="R{{$date->date}}" class="accordian-body collapse">
            @foreach ($date->exposure_windows as $exposure_window)
                <a href="#" data-toggle="collapse" data-target="#R{{$date->date}}-{{$loop->iteration}}" class="accordion-toggle">［接触者-{{$loop->iteration}}］</a>合計時間：{{$date->sumSecondsSinceLastScan($exposure_window)}}{{-- 、個別秒：{{$date->getSecondsSinceLastScan($exposure_window)}} --}}<br />
                <div id="R{{$date->date}}-{{$loop->iteration}}" class="accordian-body collapse ml-3">
                    最小減衰量 - 平均減衰量 - 秒<br />
                    @foreach ($exposure_window['ScanInstances'] as $scan_instance)
                        {{$scan_instance["MinAttenuationDb"]}}dB - {{$scan_instance["TypicalAttenuationDb"]}}dB - {{$scan_instance["SecondsSinceLastScan"]}}秒<br />

                        @php
                            // 最小減衰量のチェック＆入れ替え
                            if ($month_digests[$date->date_ym]["min_attenuation_db"]["MinAttenuationDb"] == 0 ||
                                $scan_instance["MinAttenuationDb"] <= $month_digests[$date->date_ym]["min_attenuation_db"]["MinAttenuationDb"]) {
                                $month_digests[$date->date_ym]["min_attenuation_db"]["date"] = $date->format_date;
                                $month_digests[$date->date_ym]["min_attenuation_db"]["name"] = "接触者-" . $loop->parent->iteration;
                                $month_digests[$date->date_ym]["min_attenuation_db"]["MinAttenuationDb"] = $scan_instance["MinAttenuationDb"];
                                $month_digests[$date->date_ym]["min_attenuation_db"]["SecondsSinceLastScan"] = $scan_instance["SecondsSinceLastScan"];
                                $month_digests[$date->date_ym]["min_attenuation_db"]["TypicalAttenuationDb"] = $scan_instance["TypicalAttenuationDb"];
                            }
                        @endphp

                    @endforeach
                </div>
            @endforeach
        </div>
    </td>
</tr>
@endforeach

</tbody>
</table>
</div>

<h5><span class="badge badge-pill badge-info mt-4">月毎の接触記録最大値等</span></h5>

<div class="table-responsive">
<table class="table table-bordered table-sm">
<thead class="sticky-top bg-light">
    <tr>
        <th class="text-nowrap" rowspan="2" style="vertical-align: middle;">年月</th>
        <th class="text-nowrap" colspan="2">スコア合計の最大</th>
        <th class="text-nowrap" colspan="3">最小減衰量の最小</th>
    </tr>
    <tr>
        <th class="text-nowrap">日付</th>
        <th class="text-nowrap">スコア</th>
        <th class="text-nowrap">日付</th>
        <th class="text-nowrap">dB</th>
        <th class="text-nowrap">接触者</th>
    </tr>
</thead>
<tbody>
@foreach ($month_digests as $date_ym => $month_digest)
<tr>
    <td class="text-nowrap">{{$date_ym}}</td>
    <td class="text-nowrap" style="background-color: {{\App\PluginsOption\User\Cocoalogviews\CocoalogDay::getBackgroundColor($month_digest["max_score_sum"]["score_sum"])}}">{{$month_digest["max_score_sum"]["date"]}}</td>
    <td class="text-nowrap" style="background-color: {{\App\PluginsOption\User\Cocoalogviews\CocoalogDay::getBackgroundColor($month_digest["max_score_sum"]["score_sum"])}}">{{$month_digest["max_score_sum"]["score_sum"]}}</td>
    <td class="text-nowrap">{{$month_digest["min_attenuation_db"]["date"]}}</td>
    <td class="text-nowrap">{{$month_digest["min_attenuation_db"]["MinAttenuationDb"]}}</td>
    <td class="text-nowrap">{{$month_digest["min_attenuation_db"]["name"]}}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endif

<script src="/themes/Users/cocoalog/bs-custom-file-input.js"></script>
<script>
bsCustomFileInput.init();
</script>
</body>

@endsection
