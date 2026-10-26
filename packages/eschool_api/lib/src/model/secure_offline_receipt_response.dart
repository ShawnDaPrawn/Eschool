//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_offline_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_offline_receipt_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureOfflineReceiptResponse {
  /// Returns a new [SecureOfflineReceiptResponse] instance.
  SecureOfflineReceiptResponse({
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
  final SecureOfflineReceipt data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SecureOfflineReceiptResponseCodeEnum.unknownDefaultOpenApi,
  )
  final SecureOfflineReceiptResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureOfflineReceiptResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SecureOfflineReceiptResponse.fromJson(Map<String, dynamic> json) =>
      _$SecureOfflineReceiptResponseFromJson(json);

  Map<String, dynamic> toJson() => _$SecureOfflineReceiptResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SecureOfflineReceiptResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const SecureOfflineReceiptResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
