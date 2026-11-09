//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_share.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_shares_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsSharesResponseData {
  /// Returns a new [StrengthsSharesResponseData] instance.
  StrengthsSharesResponseData({
    required this.schoolId,

    required this.readerId,

    required this.shares,
  });

  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'shares', required: true, includeIfNull: false)
  final List<StrengthsShare> shares;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsSharesResponseData &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.shares == shares;

  @override
  int get hashCode => schoolId.hashCode + readerId.hashCode + shares.hashCode;

  factory StrengthsSharesResponseData.fromJson(Map<String, dynamic> json) =>
      _$StrengthsSharesResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsSharesResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
