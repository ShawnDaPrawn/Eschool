//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/giving_campaign_change.dart';
import 'package:json_annotation/json_annotation.dart';

part 'giving_campaign_change_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingCampaignChangeResponse {
  /// Returns a new [GivingCampaignChangeResponse] instance.
  GivingCampaignChangeResponse({
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
  final GivingCampaignChange data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        GivingCampaignChangeResponseCodeEnum.unknownDefaultOpenApi,
  )
  final GivingCampaignChangeResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingCampaignChangeResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory GivingCampaignChangeResponse.fromJson(Map<String, dynamic> json) =>
      _$GivingCampaignChangeResponseFromJson(json);

  Map<String, dynamic> toJson() => _$GivingCampaignChangeResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GivingCampaignChangeResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const GivingCampaignChangeResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
