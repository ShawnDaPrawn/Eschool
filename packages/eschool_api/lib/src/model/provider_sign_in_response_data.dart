//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pool_profile_school.dart';
import 'package:json_annotation/json_annotation.dart';

part 'provider_sign_in_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProviderSignInResponseData {
  /// Returns a new [ProviderSignInResponseData] instance.
  ProviderSignInResponseData({
    required this.token,

    required this.app,

    required this.school,
  });

  /// The app token (null for a web portal, which gets a session cookie).
  @JsonKey(name: r'token', required: true, includeIfNull: true)
  final String? token;

  @JsonKey(
    name: r'app',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ProviderSignInResponseDataAppEnum.unknownDefaultOpenApi,
  )
  final ProviderSignInResponseDataAppEnum app;

  @JsonKey(name: r'school', required: true, includeIfNull: false)
  final PoolProfileSchool school;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProviderSignInResponseData &&
          other.token == token &&
          other.app == app &&
          other.school == school;

  @override
  int get hashCode =>
      (token == null ? 0 : token.hashCode) + app.hashCode + school.hashCode;

  factory ProviderSignInResponseData.fromJson(Map<String, dynamic> json) =>
      _$ProviderSignInResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$ProviderSignInResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ProviderSignInResponseDataAppEnum {
  @JsonValue(r'teacher')
  teacher(r'teacher'),
  @JsonValue(r'student')
  student(r'student'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ProviderSignInResponseDataAppEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
