//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/my_grade_access.dart';
import 'package:json_annotation/json_annotation.dart';

part 'my_grade_access_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MyGradeAccessResponse {
  /// Returns a new [MyGradeAccessResponse] instance.
  MyGradeAccessResponse({
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
  final MyGradeAccess data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MyGradeAccessResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory MyGradeAccessResponse.fromJson(Map<String, dynamic> json) =>
      _$MyGradeAccessResponseFromJson(json);

  Map<String, dynamic> toJson() => _$MyGradeAccessResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
