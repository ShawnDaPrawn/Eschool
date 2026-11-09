//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/fixture.dart';
import 'package:json_annotation/json_annotation.dart';

part 'fixture_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureResponse {
  /// Returns a new [FixtureResponse] instance.
  FixtureResponse({
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
  final Fixture data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory FixtureResponse.fromJson(Map<String, dynamic> json) =>
      _$FixtureResponseFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
