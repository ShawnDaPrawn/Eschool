//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_moderation_row.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_moderation_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicModerationWrite {
  /// Returns a new [AcademicModerationWrite] instance.
  AcademicModerationWrite({required this.reason, required this.rows});

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'rows', required: true, includeIfNull: false)
  final List<AcademicModerationRow> rows;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicModerationWrite &&
          other.reason == reason &&
          other.rows == rows;

  @override
  int get hashCode => reason.hashCode + rows.hashCode;

  factory AcademicModerationWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicModerationWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicModerationWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
