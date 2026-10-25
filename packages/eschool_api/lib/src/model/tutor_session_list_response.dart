//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_session_list.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_session_list_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorSessionListResponse {
  /// Returns a new [TutorSessionListResponse] instance.
  TutorSessionListResponse({
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
  final TutorSessionList data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorSessionListResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory TutorSessionListResponse.fromJson(Map<String, dynamic> json) =>
      _$TutorSessionListResponseFromJson(json);

  Map<String, dynamic> toJson() => _$TutorSessionListResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
