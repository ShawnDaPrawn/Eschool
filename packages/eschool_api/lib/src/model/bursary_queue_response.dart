//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/bursary_queue.dart';
import 'package:json_annotation/json_annotation.dart';

part 'bursary_queue_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryQueueResponse {
  /// Returns a new [BursaryQueueResponse] instance.
  BursaryQueueResponse({
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
  final BursaryQueue data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: BursaryQueueResponseCodeEnum.unknownDefaultOpenApi,
  )
  final BursaryQueueResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryQueueResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory BursaryQueueResponse.fromJson(Map<String, dynamic> json) =>
      _$BursaryQueueResponseFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryQueueResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum BursaryQueueResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const BursaryQueueResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
