//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/bursary_document.dart';
import 'package:json_annotation/json_annotation.dart';

part 'bursary_document_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryDocumentResponse {
  /// Returns a new [BursaryDocumentResponse] instance.
  BursaryDocumentResponse({
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
  final BursaryDocument data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: BursaryDocumentResponseCodeEnum.unknownDefaultOpenApi,
  )
  final BursaryDocumentResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryDocumentResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory BursaryDocumentResponse.fromJson(Map<String, dynamic> json) =>
      _$BursaryDocumentResponseFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryDocumentResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum BursaryDocumentResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const BursaryDocumentResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
