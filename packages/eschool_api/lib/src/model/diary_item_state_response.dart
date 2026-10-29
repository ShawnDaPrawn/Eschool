//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diary_item_state.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diary_item_state_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryItemStateResponse {
  /// Returns a new [DiaryItemStateResponse] instance.
  DiaryItemStateResponse({
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
  final DiaryItemState data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiaryItemStateResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory DiaryItemStateResponse.fromJson(Map<String, dynamic> json) =>
      _$DiaryItemStateResponseFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryItemStateResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
