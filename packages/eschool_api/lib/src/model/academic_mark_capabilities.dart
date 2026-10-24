//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_mark_capabilities.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicMarkCapabilities {
  /// Returns a new [AcademicMarkCapabilities] instance.
  AcademicMarkCapabilities({
    required this.capture,

    required this.correct,

    required this.moderate,

    required this.release,
  });

  @JsonKey(name: r'capture', required: true, includeIfNull: false)
  final bool capture;

  @JsonKey(name: r'correct', required: true, includeIfNull: false)
  final bool correct;

  @JsonKey(name: r'moderate', required: true, includeIfNull: false)
  final bool moderate;

  @JsonKey(name: r'release', required: true, includeIfNull: false)
  final bool release;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicMarkCapabilities &&
          other.capture == capture &&
          other.correct == correct &&
          other.moderate == moderate &&
          other.release == release;

  @override
  int get hashCode =>
      capture.hashCode +
      correct.hashCode +
      moderate.hashCode +
      release.hashCode;

  factory AcademicMarkCapabilities.fromJson(Map<String, dynamic> json) =>
      _$AcademicMarkCapabilitiesFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicMarkCapabilitiesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
