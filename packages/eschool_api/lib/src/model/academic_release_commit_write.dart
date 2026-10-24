//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_release_commit_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReleaseCommitWrite {
  /// Returns a new [AcademicReleaseCommitWrite] instance.
  AcademicReleaseCommitWrite({
    required this.termId,

    required this.mode,

    required this.learnerIds,

    required this.fingerprint,
  });

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: true)
  final int? termId;

  @JsonKey(
    name: r'mode',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicReleaseCommitWriteModeEnum.unknownDefaultOpenApi,
  )
  final AcademicReleaseCommitWriteModeEnum mode;

  @JsonKey(name: r'learner_ids', required: true, includeIfNull: false)
  final Set<int> learnerIds;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReleaseCommitWrite &&
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

  factory AcademicReleaseCommitWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicReleaseCommitWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReleaseCommitWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicReleaseCommitWriteModeEnum {
  @JsonValue(r'official')
  official(r'official'),
  @JsonValue(r'running')
  running(r'running'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicReleaseCommitWriteModeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
