//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_settings.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentSettings {
  /// Returns a new [ContentSettings] instance.
  ContentSettings({
    required this.transcriptionEnabled,

    required this.dailyTranscriptionSeconds,

    required this.revision,
  });

  @JsonKey(name: r'transcription_enabled', required: true, includeIfNull: false)
  final bool transcriptionEnabled;

  // minimum: 600
  // maximum: 28800
  @JsonKey(
    name: r'daily_transcription_seconds',
    required: true,
    includeIfNull: false,
  )
  final int dailyTranscriptionSeconds;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentSettings &&
          other.transcriptionEnabled == transcriptionEnabled &&
          other.dailyTranscriptionSeconds == dailyTranscriptionSeconds &&
          other.revision == revision;

  @override
  int get hashCode =>
      transcriptionEnabled.hashCode +
      dailyTranscriptionSeconds.hashCode +
      revision.hashCode;

  factory ContentSettings.fromJson(Map<String, dynamic> json) =>
      _$ContentSettingsFromJson(json);

  Map<String, dynamic> toJson() => _$ContentSettingsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
