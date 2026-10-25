//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'incident_follow_up_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentFollowUpInput {
  /// Returns a new [IncidentFollowUpInput] instance.
  IncidentFollowUpInput({required this.secret, this.before});

  @JsonKey(name: r'secret', required: true, includeIfNull: false)
  final String secret;

  // minimum: 1
  @JsonKey(name: r'before', required: false, includeIfNull: false)
  final int? before;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentFollowUpInput &&
          other.secret == secret &&
          other.before == before;

  @override
  int get hashCode => secret.hashCode + before.hashCode;

  factory IncidentFollowUpInput.fromJson(Map<String, dynamic> json) =>
      _$IncidentFollowUpInputFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentFollowUpInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
