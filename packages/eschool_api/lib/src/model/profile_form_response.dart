//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/profile_form.dart';
import 'package:json_annotation/json_annotation.dart';

part 'profile_form_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProfileFormResponse {
  /// Returns a new [ProfileFormResponse] instance.
  ProfileFormResponse({
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
  final ProfileForm data;

  /// Always 200.
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileFormResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory ProfileFormResponse.fromJson(Map<String, dynamic> json) =>
      _$ProfileFormResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileFormResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
