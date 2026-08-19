"""取り込んだ教室に、市区町村名と町名を付ける。

出典: 国土地理院「逆ジオコーディング」 https://mreversegeocoder.gsi.go.jp/
      市区町村コードの対応表: https://maps.gsi.go.jp/js/muni.js

OpenStreetMap 側は、86%の教室が名前と座標しか持っていない。
そのままだと「KUMON（三重県）」という同じ見出しのページが289枚並んでしまう。
座標から市区町村と町名を引いて、どこの教室なのかが分かるようにする。

一度引いた結果は scripts/.geocache.json に残すので、再実行しても
必要な分しか問い合わせない。

使い方: python scripts/add-city-names.py database/data/juku-osm.json
"""
import json
import re
import sys
import time
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
CACHE = ROOT / 'scripts' / '.geocache.json'
MUNI_URL = 'https://maps.gsi.go.jp/js/muni.js'
REVERSE_URL = 'https://mreversegeocoder.gsi.go.jp/reverse-geocoder/LonLatToAddress?lat={lat}&lon={lng}'
UA = 'juku-map-data/1.0 (+https://juku-map.net)'
DELAY = 0.4


def municipalities() -> dict[str, str]:
    """市区町村コード -> 市区町村名。"""
    request = urllib.request.Request(MUNI_URL, headers={'User-Agent': UA})

    with urllib.request.urlopen(request, timeout=60) as response:
        body = response.read().decode('utf-8', 'replace')

    table = {}

    # GSI.MUNI_ARRAY["13101"] = '13,東京都,13101,千代田区'; の形で並んでいる。
    # 北海道は "1100" のように4桁で書かれているので、数値にそろえて持つ。
    for code, value in re.findall(r"""GSI\.MUNI_ARRAY\["(\d+)"\]\s*=\s*'([^']+)'""", body):
        parts = value.split(',')

        if len(parts) >= 4:
            table[int(code)] = parts[3].replace('　', '')

    return table


def reverse(lat: float, lng: float) -> dict | None:
    url = REVERSE_URL.format(lat=lat, lng=lng)
    request = urllib.request.Request(url, headers={'User-Agent': UA})

    for attempt in range(3):
        try:
            with urllib.request.urlopen(request, timeout=30) as response:
                return json.loads(response.read().decode())['results']
        except Exception as error:
            if attempt == 2:
                print(f'  ({lat},{lng}) を引けませんでした: {error}', flush=True)
                return None
            time.sleep(1 + attempt)

    return None


def main() -> None:
    path = Path(sys.argv[1])
    payload = json.loads(path.read_text(encoding='utf-8'))
    schools = payload['schools']

    table = municipalities()
    print(f'市区町村コード {len(table)}件を読み込みました', flush=True)

    cache = json.loads(CACHE.read_text(encoding='utf-8')) if CACHE.exists() else {}
    asked = 0

    for index, school in enumerate(schools):
        key = f"{school['lat']},{school['lng']}"

        if key not in cache:
            result = reverse(school['lat'], school['lng'])

            if result is None:
                continue

            cache[key] = result
            asked += 1
            time.sleep(DELAY)

            if asked % 100 == 0:
                CACHE.write_text(json.dumps(cache, ensure_ascii=False), encoding='utf-8')
                print(f'  {index + 1}/{len(schools)} 件目まで（新規 {asked}件）', flush=True)

        result = cache[key]
        code = result.get('muniCd')

        # OSM 側に市区町村が入っていればそちらを優先する
        if not school.get('city'):
            school['city'] = table.get(int(code)) if code else None

        school['town'] = result.get('lv01Nm') or None

    CACHE.write_text(json.dumps(cache, ensure_ascii=False), encoding='utf-8')

    filled = sum(1 for school in schools if school.get('city'))
    payload['citySourceLabel'] = '国土地理院「逆ジオコーディング」'
    payload['citySourceUrl'] = 'https://mreversegeocoder.gsi.go.jp/'
    path.write_text(json.dumps(payload, ensure_ascii=False), encoding='utf-8')

    print(f'{filled}/{len(schools)}件に市区町村を付けました（新たに引いたのは {asked}件）')


main()
