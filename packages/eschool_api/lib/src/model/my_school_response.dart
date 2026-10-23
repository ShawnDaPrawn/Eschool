//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/my_school_info.dart';
import 'package:json_annotation/json_annotation.dart';

part 'my_school_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MySchoolResponse {
  /// Returns a new [MySchoolResponse] instance.
  MySchoolResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  /// Always false.
  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final MySchoolInfo data;

  /// Always 200.
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MySchoolResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory MySchoolResponse.fromJson(Map<String, dynamic> json) =>
      _$MySchoolResponseFromJson(json);

  Map<String, dynamic> toJson() => _$MySchoolResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
