//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/giving_campaign_list.dart';
import 'package:json_annotation/json_annotation.dart';

part 'giving_campaign_list_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingCampaignListResponse {
  /// Returns a new [GivingCampaignListResponse] instance.
  GivingCampaignListResponse({
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
  final GivingCampaignList data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GivingCampaignListResponseCodeEnum.unknownDefaultOpenApi,
  )
  final GivingCampaignListResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingCampaignListResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory GivingCampaignListResponse.fromJson(Map<String, dynamic> json) =>
      _$GivingCampaignListResponseFromJson(json);

  Map<String, dynamic> toJson() => _$GivingCampaignListResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GivingCampaignListResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const GivingCampaignListResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
