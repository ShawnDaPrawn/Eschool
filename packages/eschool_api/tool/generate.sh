#!/usr/bin/env bash
# Regenerates packages/eschool_api/lib from admin-panel/openapi.yaml.
#
# Run it after every change to openapi.yaml and commit the result; CI runs it
# too and fails when the committed code differs. Needs Java 11 or newer and
# the Dart SDK (Flutter's is fine).
#
# Generator: openapi-generator's dart-dio generator with json_serializable
# (plain immutable null-safe classes with fromJson/toJson, and a Dio client
# like the apps' ApiClient), pinned to one version and checked by its
# SHA-256 so every run produces the same code.
set -euo pipefail

GENERATOR_VERSION=7.25.0
GENERATOR_SHA256=41ce4f6b07f196676439d710759fa1ced7a08066d06ff1bf314681470289efae

package_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
spec="$package_dir/../../admin-panel/openapi.yaml"
cache_dir="${XDG_CACHE_HOME:-$HOME/.cache}/openapi-generator"
jar="$cache_dir/openapi-generator-cli-$GENERATOR_VERSION.jar"

sha256() {
  if command -v sha256sum >/dev/null 2>&1; then sha256sum "$1" | cut -d' ' -f1; else shasum -a 256 "$1" | cut -d' ' -f1; fi
}

if [ ! -f "$jar" ] || [ "$(sha256 "$jar")" != "$GENERATOR_SHA256" ]; then
  mkdir -p "$cache_dir"
  curl -fsSL -o "$jar.part" "https://repo1.maven.org/maven2/org/openapitools/openapi-generator-cli/$GENERATOR_VERSION/openapi-generator-cli-$GENERATOR_VERSION.jar"
  if [ "$(sha256 "$jar.part")" != "$GENERATOR_SHA256" ]; then
    rm -f "$jar.part"
    echo "openapi-generator-cli-$GENERATOR_VERSION.jar does not match its checksum" >&2
    exit 1
  fi
  mv "$jar.part" "$jar"
fi

# Stale models of removed schemas must not survive.
rm -rf "$package_dir/lib" "$package_dir/.openapi-generator"

java -jar "$jar" generate \
  --input-spec "$spec" \
  --generator-name dart-dio \
  --output "$package_dir" \
  --global-property apiTests=false,modelTests=false,apiDocs=false,modelDocs=false \
  --additional-properties pubName=eschool_api,pubLibrary=eschool_api,serializationLibrary=json_serializable,skipCopyWith=true,enumUnknownDefaultCase=true,disallowAdditionalPropertiesIfNotPresent=false \
  >/dev/null

cd "$package_dir"
dart pub get >/dev/null
dart run build_runner build >/dev/null
dart format lib >/dev/null
echo "Regenerated $(find lib -name '*.dart' | wc -l | tr -d ' ') files in packages/eschool_api/lib from admin-panel/openapi.yaml"
