//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_term.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_terms_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolTermsResponseData {
  /// Returns a new [SchoolTermsResponseData] instance.
  SchoolTermsResponseData({required this.sessionYearId, required this.terms});

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'terms', required: true, includeIfNull: false)
  final List<SchoolTerm> terms;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolTermsResponseData &&
          other.sessionYearId == sessionYearId &&
          other.terms == terms;

  @override
  int get hashCode => sessionYearId.hashCode + terms.hashCode;

  factory SchoolTermsResponseData.fromJson(Map<String, dynamic> json) =>
      _$SchoolTermsResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolTermsResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
