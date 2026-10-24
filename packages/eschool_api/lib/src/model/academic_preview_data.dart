//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_learner_preview.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_preview_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicPreviewData {
  /// Returns a new [AcademicPreviewData] instance.
  AcademicPreviewData({required this.previews});

  @JsonKey(name: r'previews', required: true, includeIfNull: false)
  final List<AcademicLearnerPreview> previews;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicPreviewData && other.previews == previews;

  @override
  int get hashCode => previews.hashCode;

  factory AcademicPreviewData.fromJson(Map<String, dynamic> json) =>
      _$AcademicPreviewDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicPreviewDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
