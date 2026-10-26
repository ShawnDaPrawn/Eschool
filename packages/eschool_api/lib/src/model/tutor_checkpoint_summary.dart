//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_checkpoint_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorCheckpointSummary {
  /// Returns a new [TutorCheckpointSummary] instance.
  TutorCheckpointSummary({
    required this.id,

    required this.operationId,

    required this.state,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TutorCheckpointSummaryStateEnum.unknownDefaultOpenApi,
  )
  final TutorCheckpointSummaryStateEnum state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorCheckpointSummary &&
          other.id == id &&
          other.operationId == operationId &&
          other.state == state;

  @override
  int get hashCode => id.hashCode + operationId.hashCode + state.hashCode;

  factory TutorCheckpointSummary.fromJson(Map<String, dynamic> json) =>
      _$TutorCheckpointSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$TutorCheckpointSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TutorCheckpointSummaryStateEnum {
  @JsonValue(r'ready')
  ready(r'ready'),
  @JsonValue(r'answered')
  answered(r'answered'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorCheckpointSummaryStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
