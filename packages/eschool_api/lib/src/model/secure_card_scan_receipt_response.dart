//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_card_scan_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_scan_receipt_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardScanReceiptResponse {
  /// Returns a new [SecureCardScanReceiptResponse] instance.
  SecureCardScanReceiptResponse({
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
  final SecureCardScanReceipt data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SecureCardScanReceiptResponseCodeEnum.unknownDefaultOpenApi,
  )
  final SecureCardScanReceiptResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardScanReceiptResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SecureCardScanReceiptResponse.fromJson(Map<String, dynamic> json) =>
      _$SecureCardScanReceiptResponseFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardScanReceiptResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SecureCardScanReceiptResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const SecureCardScanReceiptResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
