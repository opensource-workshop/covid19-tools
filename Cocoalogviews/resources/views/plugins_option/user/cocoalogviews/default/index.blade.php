{{--
 * 表示画面テンプレート（デフォルト）
 *
 * @author 永原　篤 <nagahara@opensource-workshop.jp>
 * @copyright OpenSource-WorkShop Co.,Ltd. All Rights Reserved
 * @category CocoaLogView プラグイン
 --}}
@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

<form action="{{url('/')}}/plugin/cocoalogviews/viewJson/{{$page->id}}/{{$frame_id}}#frame-{{$frame->id}}" method="POST" class="">
    {{ csrf_field() }}

    <div class="form-group row">
        <label class="col-md-2 control-label text-md-right">COCOAログ</label>
        <div class="col-md-10">
            <textarea name="json[{{$frame_id}}]" class="form-control @if ($errors->has("json.$frame_id")) border-danger @endif" id="json{{$frame_id}}" rows=8>{!!old("json.$frame_id", $json)!!}</textarea>
            @if ($errors && $errors->has("json.$frame_id"))
                <div class="text-danger"><i class="fas fa-exclamation-circle"></i> {{$errors->first("json.*")}}</div>
            @endif
        </div>
    </div>

    <div class="form-group row">
        <label class="col-md-2 control-label text-md-right">スケジュール</label>
        <div class="col-md-10">
            <textarea name="calendar[{{$frame_id}}]" class="form-control @if ($errors->has("calendar.$frame_id")) border-danger @endif" id="calendar{{$frame_id}}" rows=8>{!!old("calendar.$frame_id", $calendar)!!}</textarea>
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
<div class="table_frame table-responsive">
<table class="table table-bordered table-hover table-sm">
<thead class="sticky-top bg-light">
    <th class="text-nowrap"></th>
    <th class="text-nowrap">日付</th>
    <th class="text-nowrap">接触者数</th>
    <th class="text-nowrap">記録時間合計</th>
    <th class="text-nowrap">スコア合計</th>
    <th class="text-nowrap">最大スコア</th>
    @if (!empty($calendar))
        <th>スケジュール</th>
    @endisset($calendar)
</thead>
<tbody>

@foreach ($dates as $key => $date)
<tr style="background-color: {{$date->getBackgroundColor()}}">
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
@endif

@endsection
