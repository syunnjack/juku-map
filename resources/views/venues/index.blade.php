@extends('layouts.plain')

@php
    $pageHeading = $area ? $area . 'の学習塾・予備校' : '学習塾マップ';
    $pageTitle = $area
        ? $pageHeading . number_format($total) . '件｜' . config('app.name')
        : config('app.name') . ' | 小学生〜大学受験まで対応の学習塾マップ';
    $pageDescription = $area
        ? $area . 'の学習塾・個別指導塾・予備校' . number_format($total) . '件を地図と一覧から探せます。月謝の口コミは利用者の投稿です。'
        : '全国' . number_format($total) . '件の学習塾・個別指導塾・予備校を地図から検索。現在地から近い教室をすぐ見つけられ、実際の月謝の口コミを確認できます。';
@endphp

@section('title', $pageTitle)
@section('description', $pageDescription)

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'WebSite',
  'name' => config('app.name'),
  'url' => url('/'),
  'description' => '全国の学習塾・個別指導塾を地図から検索できる投稿型マップ。実際の月謝・費用の口コミや写真付き口コミを確認できる。',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'ItemList',
  'itemListElement' => $venues->take(50)->values()->map(function ($venue, $i) {
      return [
          '@type' => 'ListItem',
          'position' => $i + 1,
          'url' => url("/venues/{$venue->id}"),
          'name' => $venue->name,
      ];
  })->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container my-4">
  <div class="text-center mb-4">
    <h1 class="fw-bold h3">✏️ 学習塾マップ</h1>
    <p class="text-muted">小学生〜大学受験まで対応 | 現在地から近い教室をすぐ見つける・実際の月謝がわかる地図</p>
    <a href="{{ route('venues.create') }}" class="btn btn-juku shadow-sm px-4">➕ 学習塾・個別指導塾を投稿</a>
  </div>

  <div class="d-flex justify-content-center mb-3">
    <button id="locateButton" class="btn btn-outline-primary">📍 現在地から近い順に探す</button>
  </div>
  <p id="locateMessage" class="text-center text-muted small mb-3"></p>

  @php
      $mapVenues = $venues->getCollection()->map(fn ($v) => [
          'id' => $v->id, 'name' => $v->name, 'area' => $v->area, 'lat' => $v->lat, 'lng' => $v->lng,
      ])->values();
  @endphp
  <div id="map" data-venues="{{ $mapVenues->toJson() }}" style="height: 360px;" class="rounded shadow-sm border mb-4"></div>

  @if($area)
    <nav aria-label="パンくず" class="small mb-3">
      <a href="{{ route('venues.index') }}">学習塾マップ</a>
      <span class="text-muted mx-1">/</span><span class="text-muted">{{ $area }}</span>
    </nav>
  @endif

  @if($areaCounts->isNotEmpty())
    <h2 class="h6">都道府県から探す</h2>
    <p class="d-flex flex-wrap gap-2 mb-4">
      @foreach($areaCounts as $row)
        <a href="{{ route('areas.show', $row['slug']) }}"
           class="btn btn-sm {{ $areaSlug === $row['slug'] ? 'btn-primary' : 'btn-outline-secondary' }}">
          {{ $row['area'] }} <span class="text-muted">{{ number_format($row['total']) }}</span>
        </a>
      @endforeach
    </p>
  @endif

  <form method="GET" action="{{ $areaSlug ? route('areas.show', $areaSlug) : route('venues.index') }}" class="row g-2 mb-4">
    <div class="col-md-3">
      <label class="form-label">対象学年</label>
      <select name="grade" class="form-select">
        <option value="">すべて</option>
        @foreach(\App\Models\Venue::GRADE_OPTIONS as $grade)
          <option value="{{ $grade }}" @selected(request('grade') == $grade)>{{ $grade }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">授業形式</label>
      <select name="lesson_style" class="form-select">
        <option value="">すべて</option>
        @foreach(\App\Models\Venue::LESSON_STYLE_OPTIONS as $style)
          <option value="{{ $style }}" @selected(request('lesson_style') == $style)>{{ $style }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2 align-self-end">
      <button type="submit" class="btn btn-outline-primary w-100">絞り込む</button>
    </div>
    @if(request()->hasAny(['grade','lesson_style']))
      <div class="col-md-1 align-self-end">
        <a href="{{ route('venues.index') }}" class="btn btn-outline-secondary w-100">クリア</a>
      </div>
    @endif
  </form>

  <p class="text-muted small">
    {{ number_format($total) }}件のうち
    {{ number_format($venues->firstItem() ?? 0) }}〜{{ number_format($venues->lastItem() ?? 0) }}件目を表示しています。
  </p>

  <div class="row" id="venueList">
    @forelse($venues as $venue)
      <div class="col-md-6 col-lg-4 mb-3" data-venue-card data-lat="{{ $venue->lat }}" data-lng="{{ $venue->lng }}">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h2 class="h6 card-title">
              <a href="{{ route('venues.show', $venue) }}" class="text-decoration-none">{{ $venue->name }}</a>
              <span class="badge bg-secondary float-end">{{ $venue->city ?: ($venue->area ?? '未設定') }}</span>
            </h2>
            @if($venue->target_grades)
              <div class="mb-1">
                @foreach($venue->target_grades as $grade)
                  <span class="badge {{ str_contains($grade, '大学受験') ? 'bg-danger' : (str_contains($grade, '高校') ? 'bg-warning text-dark' : (str_contains($grade, '中学') ? 'bg-primary' : 'bg-success')) }} me-1" style="font-size:0.7rem">{{ $grade }}</span>
                @endforeach
              </div>
            @endif
            @if($venue->lesson_style)
              <span class="badge bg-light text-dark border me-1 mb-1" style="font-size:0.7rem">{{ $venue->lesson_style }}</span>
            @endif
            <p class="card-text text-muted small mb-1">{{ $venue->place_label }}</p>
            @if($venue->description)
              <p class="card-text text-muted small">{{ $venue->description }}</p>
            @endif
            <small class="text-muted d-block">
              @if($venue->cost_reports_avg_monthly_fee)
                平均月謝：約{{ number_format((int) round($venue->cost_reports_avg_monthly_fee)) }}円
              @else
                月謝の口コミ：まだありません
              @endif
            </small>
            <small class="text-muted d-block distance-label"></small>
          </div>
        </div>
      </div>
    @empty
      <p class="text-muted">該当する学習塾・個別指導塾がありません。</p>
    @endforelse
  </div>

  <div class="d-flex justify-content-center my-3">
    {{ $venues->onEachSide(1)->links() }}
  </div>

  <p class="text-muted small">
    教室の名称・場所・電話番号は OpenStreetMap のデータ（&copy; OpenStreetMap contributors、ODbL 1.0）をもとにしています。
    対象学年・授業形式・月謝は、利用者が投稿した教室にのみ表示されます。金額は学年・コース・季節講習の有無で大きく変わり、
    当サイトでは内容を確認していません。お問い合わせの前に各教室へ直接ご確認ください。
  </p>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('map');
    const venues = JSON.parse(mapEl.dataset.venues || '[]');

    const map = L.map('map').setView([35.6812, 139.7671], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    venues.forEach(function (v) {
      L.marker([v.lat, v.lng]).addTo(map)
        .bindPopup('<a href="/venues/' + v.id + '">' + v.name + '</a><br><small>' + (v.area || '') + '</small>');
    });

    function haversineKm(lat1, lng1, lat2, lng2) {
      const R = 6371;
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLng = (lng2 - lng1) * Math.PI / 180;
      const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
      return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    const locateButton = document.getElementById('locateButton');
    const locateMessage = document.getElementById('locateMessage');

    locateButton.addEventListener('click', function () {
      if (!navigator.geolocation) {
        locateMessage.textContent = 'このブラウザは現在地取得に対応していません。';
        return;
      }

      locateMessage.textContent = '現在地を取得しています…';

      navigator.geolocation.getCurrentPosition(function (position) {
        const userLat = position.coords.latitude;
        const userLng = position.coords.longitude;

        map.setView([userLat, userLng], 11);
        L.marker([userLat, userLng], { title: '現在地' })
          .addTo(map)
          .bindPopup('現在地')
          .openPopup();

        const cards = Array.from(document.querySelectorAll('[data-venue-card]'));
        cards.forEach(function (card) {
          const lat = parseFloat(card.dataset.lat);
          const lng = parseFloat(card.dataset.lng);
          const distance = haversineKm(userLat, userLng, lat, lng);
          card.dataset.distance = distance;
          const label = card.querySelector('.distance-label');
          if (label) label.textContent = '現在地から約' + distance.toFixed(1) + 'km';
        });

        cards.sort(function (a, b) {
          return parseFloat(a.dataset.distance) - parseFloat(b.dataset.distance);
        });

        const list = document.getElementById('venueList');
        cards.forEach(function (card) { list.appendChild(card); });

        locateMessage.textContent = '現在地から近い順に並び替えました。';
      }, function () {
        locateMessage.textContent = '現在地を取得できませんでした。ブラウザの位置情報許可をご確認ください。';
      });
    });
  });
</script>
@endsection
