//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_mark_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_learner_preview.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicLearnerPreview {
  /// Returns a new [AcademicLearnerPreview] instance.
  AcademicLearnerPreview({required this.learnerId, required this.result});

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'result', required: true, includeIfNull: false)
  final AcademicMarkResult result;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicLearnerPreview &&
          other.learnerId == learnerId &&
          other.result == result;

  @override
  int get hashCode => learnerId.hashCode + result.hashCode;

  factory AcademicLearnerPreview.fromJson(Map<String, dynamic> json) =>
      _$AcademicLearnerPreviewFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicLearnerPreviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
