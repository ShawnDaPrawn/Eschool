//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_save_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportSaveInput {
  /// Returns a new [SupportSaveInput] instance.
  SupportSaveInput({
    required this.operationId,

    required this.learnerId,

    this.revision,

    required this.startedOn,

    required this.siasStage,

    required this.needs,

    required this.accommodations,

    this.reviewDueOn,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  @JsonKey(name: r'started_on', required: true, includeIfNull: false)
  final String startedOn;

  @JsonKey(name: r'sias_stage', required: true, includeIfNull: false)
  final String siasStage;

  @JsonKey(name: r'needs', required: true, includeIfNull: false)
  final String needs;

  @JsonKey(name: r'accommodations', required: true, includeIfNull: false)
  final String accommodations;

  @JsonKey(name: r'review_due_on', required: false, includeIfNull: false)
  final String? reviewDueOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportSaveInput &&
          other.operationId == operationId &&
          other.learnerId == learnerId &&
          other.revision == revision &&
          other.startedOn == startedOn &&
          other.siasStage == siasStage &&
          other.needs == needs &&
          other.accommodations == accommodations &&
          other.reviewDueOn == reviewDueOn;

  @override
  int get hashCode =>
      operationId.hashCode +
      learnerId.hashCode +
      revision.hashCode +
      startedOn.hashCode +
      siasStage.hashCode +
      needs.hashCode +
      accommodations.hashCode +
      (reviewDueOn == null ? 0 : reviewDueOn.hashCode);

  factory SupportSaveInput.fromJson(Map<String, dynamic> json) =>
      _$SupportSaveInputFromJson(json);

  Map<String, dynamic> toJson() => _$SupportSaveInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
