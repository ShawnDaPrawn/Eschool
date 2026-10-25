//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_access.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationAccess {
  /// Returns a new [ConsultationAccess] instance.
  ConsultationAccess({
    required this.schoolId,

    required this.accountId,

    required this.sessionYearId,

    required this.today,

    required this.timezone,

    required this.writable,

    required this.canManage,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'account_id', required: true, includeIfNull: false)
  final int accountId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'today', required: true, includeIfNull: false)
  final String today;

  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationAccess &&
          other.schoolId == schoolId &&
          other.accountId == accountId &&
          other.sessionYearId == sessionYearId &&
          other.today == today &&
          other.timezone == timezone &&
          other.writable == writable &&
          other.canManage == canManage;

  @override
  int get hashCode =>
      schoolId.hashCode +
      accountId.hashCode +
      sessionYearId.hashCode +
      today.hashCode +
      timezone.hashCode +
      writable.hashCode +
      canManage.hashCode;

  factory ConsultationAccess.fromJson(Map<String, dynamic> json) =>
      _$ConsultationAccessFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationAccessToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
