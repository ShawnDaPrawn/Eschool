//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_card_portrait_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_portrait_receipt_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardPortraitReceiptResponse {
  /// Returns a new [SecureCardPortraitReceiptResponse] instance.
  SecureCardPortraitReceiptResponse({
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
  final SecureCardPortraitReceipt data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SecureCardPortraitReceiptResponseCodeEnum.unknownDefaultOpenApi,
  )
  final SecureCardPortraitReceiptResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardPortraitReceiptResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SecureCardPortraitReceiptResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$SecureCardPortraitReceiptResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SecureCardPortraitReceiptResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SecureCardPortraitReceiptResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const SecureCardPortraitReceiptResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
