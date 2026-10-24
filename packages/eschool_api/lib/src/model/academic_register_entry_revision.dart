//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_entry_revision.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterEntryRevision {
  /// Returns a new [AcademicRegisterEntryRevision] instance.
  AcademicRegisterEntryRevision({
    required this.learnerId,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterEntryRevision &&
          other.learnerId == learnerId &&
          other.revision == revision;

  @override
  int get hashCode => learnerId.hashCode + revision.hashCode;

  factory AcademicRegisterEntryRevision.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterEntryRevisionFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterEntryRevisionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
