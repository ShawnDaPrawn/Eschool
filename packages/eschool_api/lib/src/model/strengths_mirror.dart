//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_sections.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_mirror.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsMirror {
  /// Returns a new [StrengthsMirror] instance.
  StrengthsMirror({
    required this.schoolId,

    required this.learnerId,

    required this.readerId,

    required this.firstName,

    required this.asOf,

    required this.privacy,

    required this.sections,
  });

  /// The school the mirror belongs to; the app refuses a reply for another school.
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  /// The signed-in learner's user id; the app refuses a reply for another account.
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'first_name', required: true, includeIfNull: false)
  final String firstName;

  @JsonKey(name: r'as_of', required: true, includeIfNull: false)
  final DateTime asOf;

  @JsonKey(name: r'privacy', required: true, includeIfNull: false)
  final String privacy;

  @JsonKey(name: r'sections', required: true, includeIfNull: false)
  final StrengthsSections sections;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsMirror &&
          other.schoolId == schoolId &&
          other.learnerId == learnerId &&
          other.readerId == readerId &&
          other.firstName == firstName &&
          other.asOf == asOf &&
          other.privacy == privacy &&
          other.sections == sections;

  @override
  int get hashCode =>
      schoolId.hashCode +
      learnerId.hashCode +
      readerId.hashCode +
      firstName.hashCode +
      asOf.hashCode +
      privacy.hashCode +
      sections.hashCode;

  factory StrengthsMirror.fromJson(Map<String, dynamic> json) =>
      _$StrengthsMirrorFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsMirrorToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
