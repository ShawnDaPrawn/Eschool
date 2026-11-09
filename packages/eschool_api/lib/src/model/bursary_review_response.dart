//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/bursary_review.dart';
import 'package:json_annotation/json_annotation.dart';

part 'bursary_review_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryReviewResponse {
  /// Returns a new [BursaryReviewResponse] instance.
  BursaryReviewResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final BursaryReview data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: BursaryReviewResponseCodeEnum.unknownDefaultOpenApi,
  )
  final BursaryReviewResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryReviewResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory BursaryReviewResponse.fromJson(Map<String, dynamic> json) =>
      _$BursaryReviewResponseFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryReviewResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum BursaryReviewResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const BursaryReviewResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
