//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reenrolment_contract_list.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_contract_list_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentContractListResponse {
  /// Returns a new [ReenrolmentContractListResponse] instance.
  ReenrolmentContractListResponse({
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
  final ReenrolmentContractList data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ReenrolmentContractListResponseCodeEnum.unknownDefaultOpenApi,
  )
  final ReenrolmentContractListResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentContractListResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory ReenrolmentContractListResponse.fromJson(Map<String, dynamic> json) =>
      _$ReenrolmentContractListResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ReenrolmentContractListResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ReenrolmentContractListResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const ReenrolmentContractListResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
