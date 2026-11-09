//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_snapshot.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_shared_view.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsSharedView {
  /// Returns a new [StrengthsSharedView] instance.
  StrengthsSharedView({
    required this.schoolId,

    required this.readerId,

    required this.id,

    required this.kind,

    required this.sharedOn,

    required this.expiresAt,

    required this.notice,

    required this.mirror,
  });

  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: StrengthsSharedViewKindEnum.unknownDefaultOpenApi,
  )
  final StrengthsSharedViewKindEnum kind;

  @JsonKey(name: r'shared_on', required: true, includeIfNull: false)
  final DateTime sharedOn;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @JsonKey(name: r'notice', required: true, includeIfNull: false)
  final String notice;

  @JsonKey(name: r'mirror', required: true, includeIfNull: false)
  final StrengthsSnapshot mirror;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsSharedView &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.id == id &&
          other.kind == kind &&
          other.sharedOn == sharedOn &&
          other.expiresAt == expiresAt &&
          other.notice == notice &&
          other.mirror == mirror;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      id.hashCode +
      kind.hashCode +
      sharedOn.hashCode +
      expiresAt.hashCode +
      notice.hashCode +
      mirror.hashCode;

  factory StrengthsSharedView.fromJson(Map<String, dynamic> json) =>
      _$StrengthsSharedViewFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsSharedViewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum StrengthsSharedViewKindEnum {
  @JsonValue(r'guardian')
  guardian(r'guardian'),
  @JsonValue(r'teacher')
  teacher(r'teacher'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const StrengthsSharedViewKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
