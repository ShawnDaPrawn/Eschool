//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_assistance_staff_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_staff_choices.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceStaffChoices {
  /// Returns a new [SchoolAssistanceStaffChoices] instance.
  SchoolAssistanceStaffChoices({
    required this.staff,

    required this.schoolId,

    required this.readerId,
  });

  @JsonKey(name: r'staff', required: true, includeIfNull: false)
  final List<SchoolAssistanceStaffChoice> staff;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceStaffChoices &&
          other.staff == staff &&
          other.schoolId == schoolId &&
          other.readerId == readerId;

  @override
  int get hashCode => staff.hashCode + schoolId.hashCode + readerId.hashCode;

  factory SchoolAssistanceStaffChoices.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceStaffChoicesFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceStaffChoicesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
