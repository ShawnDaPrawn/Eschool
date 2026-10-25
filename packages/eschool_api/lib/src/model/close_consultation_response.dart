//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'close_consultation_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CloseConsultationResponse {
  /// Returns a new [CloseConsultationResponse] instance.
  CloseConsultationResponse({
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
  final ConsultationReceipt data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CloseConsultationResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory CloseConsultationResponse.fromJson(Map<String, dynamic> json) =>
      _$CloseConsultationResponseFromJson(json);

  Map<String, dynamic> toJson() => _$CloseConsultationResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
