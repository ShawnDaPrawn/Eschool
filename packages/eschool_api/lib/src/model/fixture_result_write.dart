//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'fixture_result_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureResultWrite {
  /// Returns a new [FixtureResultWrite] instance.
  FixtureResultWrite({
    required this.operationId,

    required this.hostScore,

    required this.guestScore,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  // maximum: 999
  @JsonKey(name: r'host_score', required: true, includeIfNull: false)
  final int hostScore;

  // minimum: 0
  // maximum: 999
  @JsonKey(name: r'guest_score', required: true, includeIfNull: false)
  final int guestScore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureResultWrite &&
          other.operationId == operationId &&
          other.hostScore == hostScore &&
          other.guestScore == guestScore;

  @override
  int get hashCode =>
      operationId.hashCode + hostScore.hashCode + guestScore.hashCode;

  factory FixtureResultWrite.fromJson(Map<String, dynamic> json) =>
      _$FixtureResultWriteFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureResultWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
