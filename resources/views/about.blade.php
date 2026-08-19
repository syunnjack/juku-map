@extends('layouts.plain')

@section('title', '塾マップとは - 小学生〜大学受験対応の学習塾比較 | ' . config('app.name'))
@section('description', '小学生から大学受験生まで対応。地域密着の学習塾比較サイト「塾マップ」のサービス説明ページです。')
@section('description', config('app.name') . 'の運営方針、データの取り扱い、口コミ・LINE通知・体験授業予約受付の仕組みについて説明しています。')

@section('content')
<div class="container my-4" style="max-width: 720px;">
  <h1 class="h4 fw-bold mb-4">このサイトについて</h1>

  <section class="mb-4">
    <h2 class="h6">サイトの目的</h2>
    <p class="text-muted small">
      「{{ config('app.name') }}」は、学習塾・個別指導塾・予備校の場所を地図から探せるマップです。
      土台となる教室のデータは公開されている地図データから取り込み、そのうえに利用者の投稿を重ねています。新しい教室は誰でもログイン不要・匿名で投稿でき、
      実際に通っている（通っていた）方の保護者やご本人が月謝・費用の口コミや写真付き口コミを投稿することで情報が更新されていきます。
      公式サイトでは分かりにくい「実際にいくらかかっているか」が分かることが特徴です。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">掲載している教室のデータについて</h2>
    <p class="text-muted small">
      教室の一覧は、OpenStreetMap に登録されている学習塾・予備校をもとにしています
      （&copy; <a href="https://www.openstreetmap.org/copyright" rel="nofollow noopener" target="_blank">OpenStreetMap contributors</a>、ODbL 1.0）。
      名称・所在地・電話番号・公式サイト・開講時間は、OpenStreetMap の記載をそのまま載せています。
      コース内容・料金・合格実績などは掲載しておらず、開講状況が変わっている場合もあります。
      掲載されていることが、当サイトによる推薦を意味するものではありません。
      内容に誤りを見つけた場合や、掲載を希望されない場合はお問い合わせください。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">月謝・費用の口コミについて</h2>
    <p class="text-muted small">
      掲載している月謝・教材費等の年間費用・学年・指導形式は、実際にその教室を利用している（利用していた）方からの投稿によるものです。運営による事実確認は行っておらず、
      学年・コース・季節講習の有無などによって金額は大きく変動するため、あくまで参考情報としてご利用ください。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">お子様の安全・プライバシーへの配慮について</h2>
    <p class="text-muted small">
      写真付き口コミでは、教室の様子や掲示物などの写真投稿を想定しており、生徒（お子様）のお顔が写った写真は投稿しないようお願いしています。
      不適切な写真を発見した場合は速やかに削除などの対応を行います。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">LINE通知について</h2>
    <p class="text-muted small">
      各教室のページから「🔔 新しい月謝・費用の口コミが投稿されたらLINEで通知」を選ぶと、LINEログインのうえその教室を通知対象として登録できます。
      入塾を検討中で複数の教室の費用を比較したい方にもご活用いただけます。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">体験授業予約・資料請求について</h2>
    <p class="text-muted small">
      各教室のページから「📮 LINEで体験授業予約・資料請求する」を選ぶと、LINEログインのうえ受け付けます。
      受付完了はLINE公式アカウントからお知らせしますが、当サイトは資料の発送や授業予約の調整そのものは行っておりません。
      お急ぎの場合は、掲載している電話番号へ直接お問い合わせいただくか、各教室の公式サイトもあわせてご確認ください。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">口コミ・投稿について</h2>
    <p class="text-muted small">
      口コミ（写真を含む）や新規教室の投稿は、どなたでもログイン不要で行えます。投稿内容は運営による事前確認を行わず即時反映されますが、
      不適切な投稿を発見した場合は内容を精査のうえ削除などの対応を行います。
    </p>
  </section>


  <section class="mb-4">
    <h2 class="h6">お問い合わせ</h2>
    <p class="text-muted small">
      掲載内容の誤りのご指摘、掲載を希望されない旨のご連絡は、下記へお送りください。
      内容を確認のうえ対応します。
    </p>
    <p class="small">
      <a href="mailto:{{ config('mail.contact_address') }}">{{ config('mail.contact_address') }}</a>
    </p>
    <p class="text-muted small">
      個別のご相談・お問い合わせの仲介は行っておりません。施設・教室へのご用件は、
      各施設へ直接お問い合わせください。
    </p>
  </section>
  <a href="{{ route('venues.index') }}" class="d-block text-center text-muted mt-4">トップページに戻る</a>
</div>
@endsection
