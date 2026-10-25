//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_report_thread.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingReportThread {
  /// Returns a new [WellbeingReportThread] instance.
  WellbeingReportThread({
    required this.id,

    required this.staffUserId,

    required this.authorType,

    required this.internal,

    required this.body,

    required this.createdAt,

    required this.authorName,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'staff_user_id', required: true, includeIfNull: true)
  final int? staffUserId;

  @JsonKey(
    name: r'author_type',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingReportThreadAuthorTypeEnum.unknownDefaultOpenApi,
  )
  final WellbeingReportThreadAuthorTypeEnum authorType;

  @JsonKey(name: r'internal', required: true, includeIfNull: false)
  final bool internal;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'author_name', required: true, includeIfNull: false)
  final String authorName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingReportThread &&
          other.id == id &&
          other.staffUserId == staffUserId &&
          other.authorType == authorType &&
          other.internal == internal &&
          other.body == body &&
          other.createdAt == createdAt &&
          other.authorName == authorName;

  @override
  int get hashCode =>
      id.hashCode +
      (staffUserId == null ? 0 : staffUserId.hashCode) +
      authorType.hashCode +
      internal.hashCode +
      body.hashCode +
      createdAt.hashCode +
      authorName.hashCode;

  factory WellbeingReportThread.fromJson(Map<String, dynamic> json) =>
      _$WellbeingReportThreadFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingReportThreadToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingReportThreadAuthorTypeEnum {
  @JsonValue(r'reporter')
  reporter(r'reporter'),
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingReportThreadAuthorTypeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
