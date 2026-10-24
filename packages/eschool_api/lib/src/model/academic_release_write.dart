//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_release_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReleaseWrite {
  /// Returns a new [AcademicReleaseWrite] instance.
  AcademicReleaseWrite({
    required this.termId,

    required this.mode,

    required this.learnerIds,

    this.fingerprint,
  });

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: true)
  final int? termId;

  @JsonKey(
    name: r'mode',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicReleaseWriteModeEnum.unknownDefaultOpenApi,
  )
  final AcademicReleaseWriteModeEnum mode;

  @JsonKey(name: r'learner_ids', required: true, includeIfNull: false)
  final Set<int> learnerIds;

  @JsonKey(name: r'fingerprint', required: false, includeIfNull: false)
  final String? fingerprint;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReleaseWrite &&
          other.termId == termId &&
          other.mode == mode &&
          other.learnerIds == learnerIds &&
          other.fingerprint == fingerprint;

  @override
  int get hashCode =>
      (termId == null ? 0 : termId.hashCode) +
      mode.hashCode +
      learnerIds.hashCode +
      fingerprint.hashCode;

  factory AcademicReleaseWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicReleaseWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReleaseWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicReleaseWriteModeEnum {
  @JsonValue(r'official')
  official(r'official'),
  @JsonValue(r'running')
  running(r'running'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicReleaseWriteModeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
