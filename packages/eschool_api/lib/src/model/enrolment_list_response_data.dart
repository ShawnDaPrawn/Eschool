//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/enrolment.dart';
import 'package:eschool_api/src/model/pagination.dart';
import 'package:json_annotation/json_annotation.dart';

part 'enrolment_list_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EnrolmentListResponseData {
  /// Returns a new [EnrolmentListResponseData] instance.
  EnrolmentListResponseData({required this.items, required this.pagination});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<Enrolment> items;

  @JsonKey(name: r'pagination', required: true, includeIfNull: false)
  final Pagination pagination;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EnrolmentListResponseData &&
          other.items == items &&
          other.pagination == pagination;

  @override
  int get hashCode => items.hashCode + pagination.hashCode;

  factory EnrolmentListResponseData.fromJson(Map<String, dynamic> json) =>
      _$EnrolmentListResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$EnrolmentListResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
