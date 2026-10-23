//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_summary.dart';
import 'package:eschool_api/src/model/school_document.dart';
import 'package:eschool_api/src/model/term_dates.dart';
import 'package:json_annotation/json_annotation.dart';

part 'my_school_info.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MySchoolInfo {
  /// Returns a new [MySchoolInfo] instance.
  MySchoolInfo({
    this.school,

    required this.website,

    required this.phone,

    required this.email,

    required this.address,

    required this.documents,

    required this.termDates,
  });

  @JsonKey(name: r'school', required: false, includeIfNull: false)
  final SchoolSummary? school;

  @JsonKey(name: r'website', required: true, includeIfNull: true)
  final String? website;

  @JsonKey(name: r'phone', required: true, includeIfNull: true)
  final String? phone;

  @JsonKey(name: r'email', required: true, includeIfNull: true)
  final String? email;

  @JsonKey(name: r'address', required: true, includeIfNull: true)
  final String? address;

  @JsonKey(name: r'documents', required: true, includeIfNull: false)
  final List<SchoolDocument> documents;

  @JsonKey(name: r'term_dates', required: true, includeIfNull: false)
  final List<TermDates> termDates;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MySchoolInfo &&
          other.school == school &&
          other.website == website &&
          other.phone == phone &&
          other.email == email &&
          other.address == address &&
          other.documents == documents &&
          other.termDates == termDates;

  @override
  int get hashCode =>
      school.hashCode +
      (website == null ? 0 : website.hashCode) +
      (phone == null ? 0 : phone.hashCode) +
      (email == null ? 0 : email.hashCode) +
      (address == null ? 0 : address.hashCode) +
      documents.hashCode +
      termDates.hashCode;

  factory MySchoolInfo.fromJson(Map<String, dynamic> json) =>
      _$MySchoolInfoFromJson(json);

  Map<String, dynamic> toJson() => _$MySchoolInfoToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
