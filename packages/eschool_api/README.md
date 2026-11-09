# eschool_api

Dart models and a [Dio](https://pub.dev/packages/dio) client for the NavigateEd API v2, generated from [`admin-panel/openapi.yaml`](../../admin-panel/openapi.yaml), the v2 contract. Plain Dart, so the apps, the shared packages and tools can all use it.

Everything in `lib/` is generated: never edit it by hand. Change `openapi.yaml`, then regenerate.

## Use

```dart
import 'package:eschool_api/eschool_api.dart';

final api = EschoolApi(basePathOverride: '${AppConfig.apiBaseUrl}/api/v2');
api.setBearerAuth('bearerAuth', token); // the Sanctum token from sign-in

final context = (await api.getPlatformApi().getMeContext()).data!.data;
context.currentSchool.name;      // the school's name
context.children;                // a parent's children, each with their school
```

Failures arrive as a `DioException` whose `response` has the real HTTP status and the error envelope: `ErrorResponse.fromJson(e.response!.data)`, with `code` an `ErrorCode` (`ErrorCode.unknownDefaultOpenApi` for a code added after this version). A 401 means the token is gone: sign the user out.

Pass your own `Dio` (`EschoolApi(dio: ...)`) to share timeouts, logging or other interceptors with the rest of the app; the auth interceptors are added to it unless you pass `interceptors`, in which case include a `BearerAuthInterceptor` yourself.

## Regenerate

```bash
packages/eschool_api/tool/generate.sh
```

Needs Python 3, Java 11 or newer and the Dart SDK (Flutter's is fine). The script downloads the pinned generator JAR once (checked by its SHA-256) into `~/.cache/openapi-generator`, rewrites `lib/`, runs `build_runner` for the `*.g.dart` files and formats the result. CI runs it on every pull request and fails when the committed code differs, so commit what it produces with the change to `openapi.yaml`.

`tool/repair_generated.py` applies bounded compatibility repairs for dart-dio 7.25.0 before serialization: Boolean enums use explicit Boolean converters, enum lists use their member fallback, enum defaults retain their typed constants, mixed scalar response codes retain checked scalar values, and null-only response data uses a strict null converter. It also removes an unused import of a multipart-only model that the generator does not emit. The published server schema stays authoritative. `test/regeneration_boundaries_test.dart` verifies these wire representations. Remove a workaround only after an upstream generator produces equivalent models and those tests pass.

Practice response unions use the adapters in `tool/compat/`: a response validates one complete contract and serializes that same variant. Common identifiers remain accessible directly; contract 2 callers use `.v2` for answer-free question items and richer marking results. A pending teacher mark retains null correctness and scheduling. Legacy callers keep the legacy representation.

## Why this generator

openapi-generator's `dart-dio` generator with `serializationLibrary=json_serializable`:

- plain immutable classes with `fromJson`/`toJson`, null-safe from the spec (`type: [string, 'null']` becomes `String?`, a required field a required parameter);
- a Dio client, the HTTP stack the apps' `ApiClient` already uses;
- forward compatible: unknown fields are ignored and enums get an `unknownDefaultOpenApi` case (`enumUnknownDefaultCase`), so a server that adds fields or codes does not break installed apps;
- one pinned JAR and pinned `json_serializable`/`build_runner`, so regenerating gives identical code.

The default `built_value` serialisation was not chosen (builder classes and a heavier dependency set for the same models), nor `swagger_parser` (Retrofit plus more code generators, and less complete OpenAPI 3.1 support).

## Test

```bash
flutter pub get --enforce-lockfile
flutter analyze --no-fatal-infos
flutter test
```

`test/fixtures/me_context_parent.json` is checked against `openapi.yaml` by the admin panel's Contracts suite too, so this package's tests and the server read the same shape.
