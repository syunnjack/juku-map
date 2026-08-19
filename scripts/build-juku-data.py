"""OpenStreetMap から、学習塾・予備校を取り出す。

出典: OpenStreetMap contributors（ODbL 1.0） https://www.openstreetmap.org/copyright

以前このリポジトリには、実在の塾を名乗る75件のデータが入っていたが、
名称・住所・電話番号が実在と一致せず、いいね数まで作られたものだった。
実在の企業について誤った連絡先を載せるのは実害が出るため、すべて捨てて、
出典をたどれる OpenStreetMap の amenity=prep_school（学習塾・予備校）から
作り直す。元データに無い項目（料金・合格実績など）は補わない。

都道府県ごとに問い合わせるので、座標から都道府県を求め直す必要がない。

使い方: python scripts/build-juku-data.py
  → database/data/juku-osm.json を書き出す
"""
import json
import re
import time
import urllib.parse
import urllib.request
from datetime import date
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
CACHE = ROOT / 'scripts' / '.cache'
OUTPUT = ROOT / 'database' / 'data' / 'juku-osm.json'

# 本家が混んでいるときのために、同じデータを配っているミラーも順に試す。
OVERPASS_ENDPOINTS = [
    'https://overpass-api.de/api/interpreter',
    'https://overpass.kumi.systems/api/interpreter',
    'https://overpass.osm.ch/api/interpreter',
]
UA = 'juku-map-data/1.0 (+https://juku-map.net)'
DELAY = 8.0  # Overpass への間隔（秒）

PREFECTURES = [
    '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県',
    '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県',
    '山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府',
    '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県',
    '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県',
    '鹿児島県', '沖縄県',
]

QUERY = """
[out:json][timeout:300];
area["name"="{prefecture}"]["admin_level"="4"]->.pref;
(
  nwr["amenity"="prep_school"]["name"](area.pref);
);
out tags center;
"""

# 施設ではないもの（バス停や交差点の名前に施設名が入っている場合がある）
NOT_A_PLACE = ('highway', 'railway', 'public_transport', 'aeroway', 'barrier', 'junction')
# 貸室として案内するのがふさわしくない名前
DENY_NAME = re.compile('跡$|跡地|案内図|バス停|駐車場$')


def fetch(prefecture: str) -> list[dict]:
    CACHE.mkdir(exist_ok=True)
    path = CACHE / f'overpass-{PREFECTURES.index(prefecture):02d}.json'

    if not path.exists():
        body = urllib.parse.urlencode({'data': QUERY.format(prefecture=prefecture)}).encode()

        # Overpass は混んでいると 429 や 504 を返す。待ち時間を延ばしながら、
        # ミラーも順に試す。
        payload = None
        last_error = None

        for attempt in range(6):
            endpoint = OVERPASS_ENDPOINTS[attempt % len(OVERPASS_ENDPOINTS)]
            request = urllib.request.Request(endpoint, data=body, headers={'User-Agent': UA})

            try:
                with urllib.request.urlopen(request, timeout=320) as response:
                    payload = json.loads(response.read().decode('utf-8', 'replace'))

                # 中身が空の応答をそのまま信じない。ミラーによっては、
                # エラーを返さずに空の結果を返してくることがある。実際、
                # 愛媛県が0件として取り込まれかけた（本当は12件ある）。
                if not payload.get('elements'):
                    raise RuntimeError('結果が空でした')

                break
            except Exception as error:
                last_error = error
                wait = DELAY * (attempt + 1)
                print(f'  {prefecture}: {error} のため {wait:.0f} 秒待って別のサーバで再試行します', flush=True)
                time.sleep(wait)

        if payload is None:
            raise RuntimeError(f'取得できませんでした: {last_error}')

        path.write_text(json.dumps(payload['elements'], ensure_ascii=False), encoding='utf-8')
        time.sleep(DELAY)

    return json.loads(path.read_text(encoding='utf-8'))


def main() -> None:
    records = []
    seen = set()

    for prefecture in PREFECTURES:
        try:
            elements = fetch(prefecture)
        except Exception as error:
            print(f'{prefecture} の取得に失敗しました: {error}', flush=True)
            continue

        added = 0
        for element in elements:
            tags = element.get('tags', {})
            name = (tags.get('name') or '').strip()

            if not name or DENY_NAME.search(name):
                continue
            if any(key in tags for key in NOT_A_PLACE):
                continue

            center = element.get('center') or element
            lat, lng = center.get('lat'), center.get('lon')
            if lat is None or lng is None:
                continue

            key = (element['type'], element['id'])
            if key in seen:
                continue
            seen.add(key)

            address = tags.get('addr:full') or ''.join(filter(None, [
                tags.get('addr:province'), tags.get('addr:city'), tags.get('addr:suburb'),
                tags.get('addr:neighbourhood'), tags.get('addr:block_number'), tags.get('addr:housenumber'),
            ]))

            records.append({
                'name': name,
                'area': prefecture,
                'city': tags.get('addr:city'),
                'address': address or None,
                'operator': tags.get('operator'),
                'phone': tags.get('phone') or tags.get('contact:phone'),
                'website': tags.get('website') or tags.get('contact:website'),
                'openingHours': tags.get('opening_hours'),
                'lat': round(float(lat), 7),
                'lng': round(float(lng), 7),
                'sourceRef': f"{element['type']}/{element['id']}",
            })
            added += 1

        print(f'{prefecture} {added}件', flush=True)

    records.sort(key=lambda record: (record['area'], record['name']))

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT.write_text(json.dumps({
        'confirmedOn': date.today().isoformat(),
        'sourceLabel': 'OpenStreetMap contributors（ODbL 1.0）',
        'sourceUrl': 'https://www.openstreetmap.org/copyright',
        'schools': records,
    }, ensure_ascii=False), encoding='utf-8')

    print(f'{len(records)}件を書き出しました')


main()
