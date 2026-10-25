//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_teacher_disclosure.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportTeacherDisclosure {
  /// Returns a new [SupportTeacherDisclosure] instance.
  SupportTeacherDisclosure({
    required this.id,

    required this.learnerId,

    required this.learnerName,

    required this.revision,

    required this.expiresOn,

    required this.accommodations,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'expires_on', required: true, includeIfNull: false)
  final String expiresOn;

  @JsonKey(name: r'accommodations', required: true, includeIfNull: false)
  final String accommodations;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportTeacherDisclosure &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.revision == revision &&
          other.expiresOn == expiresOn &&
          other.accommodations == accommodations;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      revision.hashCode +
      expiresOn.hashCode +
      accommodations.hashCode;

  factory SupportTeacherDisclosure.fromJson(Map<String, dynamic> json) =>
      _$SupportTeacherDisclosureFromJson(json);

  Map<String, dynamic> toJson() => _$SupportTeacherDisclosureToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
