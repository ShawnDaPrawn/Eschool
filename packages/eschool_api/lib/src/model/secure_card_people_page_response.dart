//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_card_people_page.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_people_page_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardPeoplePageResponse {
  /// Returns a new [SecureCardPeoplePageResponse] instance.
  SecureCardPeoplePageResponse({
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
  final SecureCardPeoplePage data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SecureCardPeoplePageResponseCodeEnum.unknownDefaultOpenApi,
  )
  final SecureCardPeoplePageResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardPeoplePageResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SecureCardPeoplePageResponse.fromJson(Map<String, dynamic> json) =>
      _$SecureCardPeoplePageResponseFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardPeoplePageResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SecureCardPeoplePageResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const SecureCardPeoplePageResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
