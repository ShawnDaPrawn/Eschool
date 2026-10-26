//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_form_choice.dart';
import 'package:eschool_api/src/model/family_form_group_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_form_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyFormConfiguration {
  /// Returns a new [FamilyFormConfiguration] instance.
  FamilyFormConfiguration({
    required this.children,

    required this.groups,

    required this.writable,

    required this.signatureName,

    required this.timezone,

    required this.sessionYearId,

    required this.schoolId,

    required this.readerId,
  });

  @JsonKey(name: r'children', required: true, includeIfNull: false)
  final List<FamilyFormChoice> children;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<FamilyFormGroupChoice> groups;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'signature_name', required: true, includeIfNull: false)
  final String signatureName;

  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyFormConfiguration &&
          other.children == children &&
          other.groups == groups &&
          other.writable == writable &&
          other.signatureName == signatureName &&
          other.timezone == timezone &&
          other.sessionYearId == sessionYearId &&
          other.schoolId == schoolId &&
          other.readerId == readerId;

  @override
  int get hashCode =>
      children.hashCode +
      groups.hashCode +
      writable.hashCode +
      signatureName.hashCode +
      timezone.hashCode +
      sessionYearId.hashCode +
      schoolId.hashCode +
      readerId.hashCode;

  factory FamilyFormConfiguration.fromJson(Map<String, dynamic> json) =>
      _$FamilyFormConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyFormConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
