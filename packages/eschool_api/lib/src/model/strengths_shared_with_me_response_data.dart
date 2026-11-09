//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_shared_with_me.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_shared_with_me_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsSharedWithMeResponseData {
  /// Returns a new [StrengthsSharedWithMeResponseData] instance.
  StrengthsSharedWithMeResponseData({
    required this.schoolId,

    required this.readerId,

    required this.shares,
  });

  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'shares', required: true, includeIfNull: false)
  final List<StrengthsSharedWithMe> shares;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsSharedWithMeResponseData &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.shares == shares;

  @override
  int get hashCode => schoolId.hashCode + readerId.hashCode + shares.hashCode;

  factory StrengthsSharedWithMeResponseData.fromJson(
    Map<String, dynamic> json,
  ) => _$StrengthsSharedWithMeResponseDataFromJson(json);

  Map<String, dynamic> toJson() =>
      _$StrengthsSharedWithMeResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
