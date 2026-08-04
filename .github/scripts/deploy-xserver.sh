#!/usr/bin/env bash

set -euo pipefail

release_sha="${1:?release SHA is required}"
ga_measurement_id="${2:?GA measurement ID is required}"
site_verification="${3:-}"

app_root="$HOME/juku-map-app"
release_dir="$app_root/releases/$release_sha"
shared_dir="$app_root/shared"
public_root="$HOME/juku-map.jp/public_html"
archive="$app_root/uploads/release-$release_sha.tar.gz"

if command -v php8.2 >/dev/null 2>&1; then
    php_binary="$(command -v php8.2)"
elif command -v php >/dev/null 2>&1; then
    php_binary="$(command -v php)"
else
    echo "PHP CLI was not found on the server." >&2
    exit 1
fi

php_version="$("$php_binary" -r 'echo PHP_VERSION_ID;')"
if [ "$php_version" -lt 80200 ]; then
    echo "Laravel requires PHP 8.2 or newer; found $("$php_binary" -r 'echo PHP_VERSION;')." >&2
    exit 1
fi

mkdir -p \
    "$release_dir" \
    "$shared_dir/database" \
    "$shared_dir/storage/app/public" \
    "$shared_dir/storage/framework/cache/data" \
    "$shared_dir/storage/framework/sessions" \
    "$shared_dir/storage/framework/views" \
    "$shared_dir/storage/logs" \
    "$public_root"

tar -xzf "$archive" -C "$release_dir"

environment_file="$shared_dir/.env"
if [ ! -f "$environment_file" ]; then
    umask 077
    app_key="$("$php_binary" -r "echo 'base64:'.base64_encode(random_bytes(32));")"
    cat > "$environment_file" <<EOF
APP_NAME=juku-map
APP_ENV=production
APP_KEY=$app_key
APP_DEBUG=false
APP_URL=https://juku-map.jp
APP_LOCALE=ja
APP_FALLBACK_LOCALE=ja
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=sqlite
DB_DATABASE=$shared_dir/database/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
LINE_LOGIN_REDIRECT_URI=https://juku-map.jp/line/callback
EOF
fi

update_environment_value() {
    "$php_binary" -r '
        [$script, $path, $key, $value] = $argv;
        $contents = file_get_contents($path);
        $line = $key."=".$value;
        $pattern = "/^".preg_quote($key, "/")."=.*$/m";
        $updated = preg_match($pattern, $contents)
            ? preg_replace($pattern, $line, $contents)
            : rtrim($contents).PHP_EOL.$line.PHP_EOL;
        if (file_put_contents($path, $updated, LOCK_EX) === false) {
            fwrite(STDERR, "Unable to update ".$path.PHP_EOL);
            exit(1);
        }
    ' "$environment_file" "$1" "$2"
}

update_environment_value "GA_MEASUREMENT_ID" "$ga_measurement_id"
update_environment_value "GOOGLE_SITE_VERIFICATION" "$site_verification"

touch "$shared_dir/database/database.sqlite"
if [ ! -e "$release_dir/.env" ] && [ ! -L "$release_dir/.env" ]; then
    ln -s "$environment_file" "$release_dir/.env"
fi
if [ ! -e "$release_dir/storage" ] && [ ! -L "$release_dir/storage" ]; then
    ln -s "$shared_dir/storage" "$release_dir/storage"
fi
chmod -R u+rwX "$shared_dir/storage" "$release_dir/bootstrap/cache"

(
    cd "$release_dir"
    "$php_binary" artisan migrate --force --no-interaction
    "$php_binary" artisan config:cache
    "$php_binary" artisan view:cache
)

backup_root="$app_root/backups/public-html-before-laravel"
if [ ! -f "$backup_root/.complete" ]; then
    mkdir -p "$backup_root"
    for source in "$release_dir"/public/* "$release_dir"/public/.[!.]*; do
        [ -e "$source" ] || continue
        name="$(basename "$source")"
        if [ -e "$public_root/$name" ] || [ -L "$public_root/$name" ]; then
            cp -a "$public_root/$name" "$backup_root/"
        fi
    done
    touch "$backup_root/.complete"
fi

for blocking_index in index.html index.htm; do
    if [ -f "$public_root/$blocking_index" ]; then
        mv \
            "$public_root/$blocking_index" \
            "$backup_root/$blocking_index.$release_sha"
    fi
done

next_link="$app_root/current-$release_sha"
if [ ! -L "$next_link" ]; then
    ln -s "$release_dir" "$next_link"
fi

for source in "$release_dir"/public/* "$release_dir"/public/.[!.]*; do
    [ -e "$source" ] || continue
    if [ "$(basename "$source")" != "index.php" ]; then
        cp -a "$source" "$public_root/"
    fi
done

mv -Tf "$next_link" "$app_root/current"
cp "$release_dir/public/index.php" "$public_root/index.php.next"
mv -f "$public_root/index.php.next" "$public_root/index.php"

if [ ! -e "$public_root/storage" ] && [ ! -L "$public_root/storage" ]; then
    ln -s "$shared_dir/storage/app/public" "$public_root/storage"
fi
