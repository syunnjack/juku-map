@extends('layouts.plain')

@section('title', $area . 'の学習塾・個別指導塾一覧（小学生〜大学受験） | ' . config('app.name'))
@section('description', $area . 'の学習塾・個別指導塾を一覧で比較。小学生・中学生・高校生・大学受験生向けの塾を対象学年・授業形式で絞り込めます。実際の月謝の口コミも掲載。')

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'CollectionPage',
  'name' => $area . 'の学習塾・個別指導塾一覧',
  'url' => url('/areas/' . urlencode($area)),
  'description' => $area . 'の学習塾・個別指導塾を小学生〜大学受験生まで対象学年別に比較。',
  'breadcrumb' => [
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => config('app.name'), 'item' => url('/')],
      ['@type' => 'ListItem', 'position' => 2, 'name' => $area, 'item' => url('/areas/' . urlencode($area))],
    ],
  ],
  'mainEntity' => [
    '@type' => 'ItemList',
    'numberOfItems' => count($venues),
    'itemListElement' => $venues->take(10)->values()->map(fn($v, $i) => [
      '@type' => 'ListItem',
      'position' => $i + 1,
      'name' => $v->name,
      'url' => url("/venues/{$v->id}"),
    ])->all(),
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container my-4">
  <nav aria-label="パンくず" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('venues.index') }}">{{ config('app.name') }}</a></li>
      <li class="breadcrumb-item active">{{ $area }}</li>
    </ol>
  </nav>

  <h1 class="h3 fw-bold mb-1">{{ $area }}の学習塾・個別指導塾 一覧</h1>
  <p class="text-muted mb-3">{{ $venues->count() }}件の塾・教室が見つかりました。小学生〜大学受験まで対応。</p>

  {{-- フィルター --}}
  <form method="GET" action="{{ url('/areas/' . urlencode($area)) }}" class="row g-2 mb-4">
    <div class="col-md-4">
      <label class="form-label small">対象学年</label>
      <select name="grade" class="form-select form-select-sm">
        <option value="">すべて</option>
        @foreach(\App\Models\Venue::GRADE_OPTIONS as $grade)
          <option value="{{ $grade }}" @selected(request('grade') == $grade)>{{ $grade }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label small">授業形式</label>
      <select name="lesson_style" class="form-select form-select-sm">
        <option value="">すべて</option>
        @foreach(\App\Models\Venue::LESSON_STYLE_OPTIONS as $style)
          <option value="{{ $style }}" @selected(request('lesson_style') == $style)>{{ $style }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2 align-self-end">
      <button type="submit" class="btn btn-outline-primary btn-sm w-100">絞り込む</button>
    </div>
    @if(request()->hasAny(['grade','lesson_style']))
    <div class="col-md-2 align-self-end">
      <a href="{{ url('/areas/' . urlencode($area)) }}" class="btn btn-outline-secondary btn-sm w-100">クリア</a>
    </div>
    @endif
  </form>

  <div class="row">
    @foreach($venues as $venue)
    <div class="col-md-6 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h2 class="h6 card-title mb-1">
            <a href="{{ route('venues.show', $venue) }}" class="text-decoration-none">{{ $venue->name }}</a>
          </h2>
          @if($venue->target_grades)
          <div class="mb-1">
            @foreach($venue->target_grades as $grade)
              <span class="badge {{ str_contains($grade, '大学受験') ? 'bg-danger' : (str_contains($grade, '高校') ? 'bg-warning text-dark' : (str_contains($grade, '中学') ? 'bg-primary' : 'bg-success')) }} me-1" style="font-size:0.7rem">{{ $grade }}</span>
            @endforeach
          </div>
          @endif
          @if($venue->lesson_style)
          <span class="badge bg-light text-dark border mb-1" style="font-size:0.7rem">{{ $venue->lesson_style }}</span>
          @endif
          <p class="card-text text-muted small mt-1">{{ Str::limit($venue->description, 60) }}</p>
          <small class="text-muted">
            @if($venue->cost_reports_avg_monthly_fee)
              平均月謝：約{{ number_format((int)round($venue->cost_reports_avg_monthly_fee)) }}円
            @else
              月謝口コミ：まだありません
            @endif
          </small>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  <div class="mt-3">
    <a href="{{ route('venues.index') }}" class="btn btn-outline-secondary btn-sm">← すべてのエリアから探す</a>
  </div>
</div>
@endsection
