//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/recognition_award_profile.dart';
import 'package:json_annotation/json_annotation.dart';

part 'recognition_award_profile_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionAwardProfileResponse {
  /// Returns a new [RecognitionAwardProfileResponse] instance.
  RecognitionAwardProfileResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final RecognitionAwardProfile data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionAwardProfileResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory RecognitionAwardProfileResponse.fromJson(Map<String, dynamic> json) =>
      _$RecognitionAwardProfileResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$RecognitionAwardProfileResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
