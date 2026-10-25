//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'cover_create_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverCreateInput {
  /// Returns a new [CoverCreateInput] instance.
  CoverCreateInput({
    required this.operationId,

    required this.sessionYearId,

    required this.teacherId,

    required this.startsOn,

    required this.endsOn,

    this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'teacher_id', required: true, includeIfNull: false)
  final int teacherId;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'reason', required: false, includeIfNull: false)
  final String? reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverCreateInput &&
          other.operationId == operationId &&
          other.sessionYearId == sessionYearId &&
          other.teacherId == teacherId &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      sessionYearId.hashCode +
      teacherId.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      (reason == null ? 0 : reason.hashCode);

  factory CoverCreateInput.fromJson(Map<String, dynamic> json) =>
      _$CoverCreateInputFromJson(json);

  Map<String, dynamic> toJson() => _$CoverCreateInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
