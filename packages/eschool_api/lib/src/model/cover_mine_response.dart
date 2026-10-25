//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/cover_mine.dart';
import 'package:json_annotation/json_annotation.dart';

part 'cover_mine_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverMineResponse {
  /// Returns a new [CoverMineResponse] instance.
  CoverMineResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final CoverMine data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverMineResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory CoverMineResponse.fromJson(Map<String, dynamic> json) =>
      _$CoverMineResponseFromJson(json);

  Map<String, dynamic> toJson() => _$CoverMineResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
