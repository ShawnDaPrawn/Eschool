//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_release_learner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_release_preview_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReleasePreviewData {
  /// Returns a new [AcademicReleasePreviewData] instance.
  AcademicReleasePreviewData({
    required this.mode,

    required this.provisional,

    required this.fingerprint,

    required this.canRelease,

    required this.learners,
  });

  @JsonKey(name: r'mode', required: true, includeIfNull: false)
  final String mode;

  @JsonKey(name: r'provisional', required: true, includeIfNull: false)
  final bool provisional;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'can_release', required: true, includeIfNull: false)
  final bool canRelease;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<AcademicReleaseLearner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReleasePreviewData &&
          other.mode == mode &&
          other.provisional == provisional &&
          other.fingerprint == fingerprint &&
          other.canRelease == canRelease &&
          other.learners == learners;

  @override
  int get hashCode =>
      mode.hashCode +
      provisional.hashCode +
      fingerprint.hashCode +
      canRelease.hashCode +
      learners.hashCode;

  factory AcademicReleasePreviewData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReleasePreviewDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReleasePreviewDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
