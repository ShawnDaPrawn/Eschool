//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/admission_transfer_ack.dart';
import 'package:json_annotation/json_annotation.dart';

part 'admission_transfer_ack_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionTransferAckResponse {
  /// Returns a new [AdmissionTransferAckResponse] instance.
  AdmissionTransferAckResponse({
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
  final AdmissionTransferAck data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionTransferAckResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory AdmissionTransferAckResponse.fromJson(Map<String, dynamic> json) =>
      _$AdmissionTransferAckResponseFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionTransferAckResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
