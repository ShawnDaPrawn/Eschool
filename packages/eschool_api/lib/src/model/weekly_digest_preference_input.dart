//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_preference_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestPreferenceInput {
  /// Returns a new [WeeklyDigestPreferenceInput] instance.
  WeeklyDigestPreferenceInput({
    required this.operationId,

    required this.language,

    required this.revision,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestPreferenceInput &&
          other.operationId == operationId &&
          other.language == language &&
          other.revision == revision;

  @override
  int get hashCode =>
      operationId.hashCode + language.hashCode + revision.hashCode;

  factory WeeklyDigestPreferenceInput.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestPreferenceInputFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestPreferenceInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
