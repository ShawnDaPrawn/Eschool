//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/recognition_point_history.dart';
import 'package:json_annotation/json_annotation.dart';

part 'recognition_point_history_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionPointHistoryResponse {
  /// Returns a new [RecognitionPointHistoryResponse] instance.
  RecognitionPointHistoryResponse({
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
  final RecognitionPointHistory data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionPointHistoryResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory RecognitionPointHistoryResponse.fromJson(Map<String, dynamic> json) =>
      _$RecognitionPointHistoryResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$RecognitionPointHistoryResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
