//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_turn_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_turn_result_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorTurnResultResponse {
  /// Returns a new [TutorTurnResultResponse] instance.
  TutorTurnResultResponse({
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
  final TutorTurnResult data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorTurnResultResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory TutorTurnResultResponse.fromJson(Map<String, dynamic> json) =>
      _$TutorTurnResultResponseFromJson(json);

  Map<String, dynamic> toJson() => _$TutorTurnResultResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
