//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/mastery_view.dart';
import 'package:json_annotation/json_annotation.dart';

part 'mastery_view_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryViewResponse {
  /// Returns a new [MasteryViewResponse] instance.
  MasteryViewResponse({
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
  final MasteryView data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryViewResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory MasteryViewResponse.fromJson(Map<String, dynamic> json) =>
      _$MasteryViewResponseFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryViewResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
