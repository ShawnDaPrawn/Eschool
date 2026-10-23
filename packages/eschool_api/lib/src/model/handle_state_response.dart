//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/handle_state.dart';
import 'package:json_annotation/json_annotation.dart';

part 'handle_state_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class HandleStateResponse {
  /// Returns a new [HandleStateResponse] instance.
  HandleStateResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  /// Always false.
  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final HandleState data;

  /// Always 200.
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is HandleStateResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory HandleStateResponse.fromJson(Map<String, dynamic> json) =>
      _$HandleStateResponseFromJson(json);

  Map<String, dynamic> toJson() => _$HandleStateResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
