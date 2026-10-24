//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'calendar_removed_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CalendarRemovedResponse {
  /// Returns a new [CalendarRemovedResponse] instance.
  CalendarRemovedResponse({
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

  /// Null on removal.
  @JsonKey(name: r'data', required: true, includeIfNull: true)
  final Object? data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CalendarRemovedResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode +
      message.hashCode +
      code.hashCode +
      (data == null ? 0 : data.hashCode);

  factory CalendarRemovedResponse.fromJson(Map<String, dynamic> json) =>
      _$CalendarRemovedResponseFromJson(json);

  Map<String, dynamic> toJson() => _$CalendarRemovedResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
