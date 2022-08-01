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
                <div class="text-danger"><i class="fas fa-exclamation-triangle"></i> {{$errors->first("json.*")}}</div>
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
</style>

@if(isset($dates))
<br />
<div class="table_frame table-responsive">
<table class="table table-bordered table-hover table-sm">
<thead class="sticky-top bg-light">
    <th nowrap>日付</th>
    <th nowrap>記録回数</th>
    <th nowrap>記録時間合計</th>
    <th nowrap>リスク合計</th>
    <th nowrap>最大リスク</th>
    @if (!empty($calendar))
        <th>カレンダー</th>
    @endisset($calendar)
</thead>
<tbody>

@foreach ($dates as $key => $date)
<tr style="background-color: {{$date->getBackgroundColor()}}">
    <td nowrap>{{$date->format_date}}</td>
    <td>{{$date->count}}</td>
    <td>{{$date->getTotaltime()}}</td>
    <td>{{$date->score_sum}}</td>
    <td>{{$date->maximum_score}}</td>
    @if (!empty($calendar))
        <td>{{$date->calendar_event}}</td>
    @endisset($calendar)
</tr>
@endforeach

</tbody>
</table>
</div>
@endif

@endsection
