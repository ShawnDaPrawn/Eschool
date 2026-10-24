//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/subject_standard.dart';
import 'package:json_annotation/json_annotation.dart';

part 'subject_standards_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SubjectStandardsResponseData {
  /// Returns a new [SubjectStandardsResponseData] instance.
  SubjectStandardsResponseData({
    required this.standards,

    required this.currentPage,

    required this.lastPage,

    required this.total,
  });

  @JsonKey(name: r'standards', required: true, includeIfNull: false)
  final List<SubjectStandard> standards;

  // minimum: 1
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SubjectStandardsResponseData &&
          other.standards == standards &&
          other.currentPage == currentPage &&
          other.lastPage == lastPage &&
          other.total == total;

  @override
  int get hashCode =>
      standards.hashCode +
      currentPage.hashCode +
      lastPage.hashCode +
      total.hashCode;

  factory SubjectStandardsResponseData.fromJson(Map<String, dynamic> json) =>
      _$SubjectStandardsResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$SubjectStandardsResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
