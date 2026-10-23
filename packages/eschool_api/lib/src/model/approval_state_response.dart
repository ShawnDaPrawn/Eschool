//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/approval_state.dart';
import 'package:json_annotation/json_annotation.dart';

part 'approval_state_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ApprovalStateResponse {
  /// Returns a new [ApprovalStateResponse] instance.
  ApprovalStateResponse({
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
  final ApprovalState data;

  /// Always 200.
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ApprovalStateResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory ApprovalStateResponse.fromJson(Map<String, dynamic> json) =>
      _$ApprovalStateResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ApprovalStateResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
