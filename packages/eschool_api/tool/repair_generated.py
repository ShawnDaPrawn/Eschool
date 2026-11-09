#!/usr/bin/env python3
"""Repair dart-dio 7.25.0 defects before json_serializable runs.

Keep the published schema intact. Remove these workarounds when an upstream
generator produces equivalent, compiling models and the boundary tests pass.
"""

from pathlib import Path
import re
import sys


def repair(source: str) -> str:
    # Multipart parameters are expanded into Dio fields; no request model is
    # emitted, but this unused import otherwise prevents the client compiling.
    source = source.replace("import 'package:eschool_api/src/model/practice_audio_review_input.dart';\n", "")
    # Enum defaults must be enum constants in both the constructor and JsonKey.
    if 'class CommunicationAudienceWrite ' in source:
        source = source.replace("const CommunicationAudienceWriteKindEnum._('fixed')", 'CommunicationAudienceWriteKindEnum.fixed')
        source = source.replace("defaultValue: 'fixed',", 'defaultValue: CommunicationAudienceWriteKindEnum.fixed,')
    # The fallback is an enum member, including when the field is a list.
    source = re.sub(
        r"unknownEnumValue: List<(\w+)>\.unknownDefaultOpenApi",
        r"unknownEnumValue: \1.unknownDefaultOpenApi",
        source,
    )

    boolean_names = []

    def boolean_enum(match: re.Match) -> str:
        block = match[0]
        if not any(marker in block for marker in ("''true''", "''false''", "@JsonValue(true)", "@JsonValue(false)")):
            return block
        name = re.search(r"enum (\w+)", block)[1]
        boolean_names.append(name)
        members = [f"  {value}_({value})," for value in ("true", "false") if f"{value}_(" in block]
        return f'''enum {name} {{
{chr(10).join(members)}
  unknownDefaultOpenApi(null);

  const {name}(this.value);
  final bool? value;

  static {name} fromJson(Object? value) => values.firstWhere(
    (entry) => entry.value != null && entry.value == value,
    orElse: () => {name}.unknownDefaultOpenApi,
  );
  static bool? toJsonValue({name} value) => value.value;
}}
'''

    source = re.sub(r"enum \w+ \{[^}]*\}", boolean_enum, source)
    # json_serializable supports String/int enum annotations, not Boolean ones.
    for name in boolean_names:
        source = source.replace(
            f"unknownEnumValue: {name}.unknownDefaultOpenApi,",
            f"fromJson: {name}.fromJson,\n    toJson: {name}.toJsonValue,",
        )
    if "final ModelNull " in source or "final Null " in source:
        source = source.replace("import 'package:eschool_api/src/model/model_null.dart';\n", "")
        source = source.replace("final ModelNull ", "final Null ")
        # A required null-only property must still be present in serialized JSON.
        source = re.sub(
            r"(@JsonKey\([^)]*)includeIfNull: false,([^)]*\)\s*final Null )",
            r"\1includeIfNull: true,\2",
            source,
        )
        if "_onlyNullFromJson" not in source:
            source = re.sub(
                r"(@JsonKey\()([^)]*\)\s*final Null )",
                r"\1fromJson: _onlyNullFromJson, toJson: _onlyNullToJson,\2",
                source,
            )
            source += '''
Null _onlyNullFromJson(Object? value) {
  if (value != null) {
    throw FormatException('Expected a null-only response payload.');
  }
  return null;
}
Null _onlyNullToJson(Null value) => value;
'''
    return source


def main(root: Path) -> None:
    for path in root.rglob("*.dart"):
        source = path.read_text()
        result = repair(source)
        if result != source:
            path.write_text(result)

    # dart-dio flattens oneOf into an object requiring both variants. These
    # adapters instead validate and serialize exactly the selected contract.
    for template in (Path(__file__).parent / 'compat').glob('*.dart'):
        (root / 'src/model' / template.name).write_text(template.read_text())

    # dart-dio emits an invalid empty object for integer|string|null. Preserve
    # the union as a scalar wrapper, with checked JSON in both directions.
    path = root / "src/model/pool_member_page_response_code.dart"
    if path.exists():
        path.write_text('''// Generated union compatibility for dart-dio 7.25.0.
class PoolMemberPageResponseCode {
  const PoolMemberPageResponseCode._(this.value);

  final Object? value;

  factory PoolMemberPageResponseCode.fromJson(Object? value) {
    if (value != null && value is! int && value is! String) {
      throw FormatException('A response code must be an integer, string or null.');
    }
    return PoolMemberPageResponseCode._(value);
  }

  Object? toJson() => value;

  @override
  bool operator ==(Object other) =>
      other is PoolMemberPageResponseCode && other.value == value;

  @override
  int get hashCode => value.hashCode;
}
''')
        deserializer = root / "src/deserialize.dart"
        source = deserializer.read_text().replace(
            "PoolMemberPageResponseCode.fromJson(value as Map<String, dynamic>)",
            "PoolMemberPageResponseCode.fromJson(value)",
        )
        deserializer.write_text(source)


if __name__ == "__main__":
    main(Path(sys.argv[1]))
