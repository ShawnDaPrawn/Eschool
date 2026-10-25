//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'incident_identify_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentIdentifyInput {
  /// Returns a new [IncidentIdentifyInput] instance.
  IncidentIdentifyInput({
    required this.secret,

    required this.revision,

    required this.identifyConsent,

    this.learnerId,
  });

  @JsonKey(name: r'secret', required: true, includeIfNull: false)
  final String secret;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'identify_consent', required: true, includeIfNull: false)
  final bool identifyConsent;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: false, includeIfNull: false)
  final int? learnerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentIdentifyInput &&
          other.secret == secret &&
          other.revision == revision &&
          other.identifyConsent == identifyConsent &&
          other.learnerId == learnerId;

  @override
  int get hashCode =>
      secret.hashCode +
      revision.hashCode +
      identifyConsent.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode);

  factory IncidentIdentifyInput.fromJson(Map<String, dynamic> json) =>
      _$IncidentIdentifyInputFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentIdentifyInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
