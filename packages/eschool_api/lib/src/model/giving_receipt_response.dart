//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/giving_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'giving_receipt_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingReceiptResponse {
  /// Returns a new [GivingReceiptResponse] instance.
  GivingReceiptResponse({
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
  final GivingReceipt data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GivingReceiptResponseCodeEnum.unknownDefaultOpenApi,
  )
  final GivingReceiptResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingReceiptResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory GivingReceiptResponse.fromJson(Map<String, dynamic> json) =>
      _$GivingReceiptResponseFromJson(json);

  Map<String, dynamic> toJson() => _$GivingReceiptResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GivingReceiptResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const GivingReceiptResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
