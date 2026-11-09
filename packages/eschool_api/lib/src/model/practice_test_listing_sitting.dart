//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_listing_sitting.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestListingSitting {
  /// Returns a new [PracticeTestListingSitting] instance.
  PracticeTestListingSitting({
    required this.id,

    required this.state,

    required this.deadlineAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeTestListingSittingStateEnum.unknownDefaultOpenApi,
  )
  final PracticeTestListingSittingStateEnum state;

  @JsonKey(name: r'deadline_at', required: true, includeIfNull: false)
  final DateTime deadlineAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestListingSitting &&
          other.id == id &&
          other.state == state &&
          other.deadlineAt == deadlineAt;

  @override
  int get hashCode => id.hashCode + state.hashCode + deadlineAt.hashCode;

  factory PracticeTestListingSitting.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestListingSittingFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestListingSittingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeTestListingSittingStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'time_up')
  timeUp(r'time_up'),
  @JsonValue(r'handed_in')
  handedIn(r'handed_in'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestListingSittingStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
