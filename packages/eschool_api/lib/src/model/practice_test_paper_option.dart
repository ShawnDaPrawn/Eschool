//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_paper_option.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestPaperOption {
  /// Returns a new [PracticeTestPaperOption] instance.
  PracticeTestPaperOption({
    required this.versionId,

    required this.title,

    required this.paperYear,

    required this.paperCode,
  });

  @JsonKey(name: r'version_id', required: true, includeIfNull: false)
  final String versionId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  // minimum: 1
  @JsonKey(name: r'paper_year', required: true, includeIfNull: false)
  final int paperYear;

  @JsonKey(name: r'paper_code', required: true, includeIfNull: false)
  final String paperCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestPaperOption &&
          other.versionId == versionId &&
          other.title == title &&
          other.paperYear == paperYear &&
          other.paperCode == paperCode;

  @override
  int get hashCode =>
      versionId.hashCode +
      title.hashCode +
      paperYear.hashCode +
      paperCode.hashCode;

  factory PracticeTestPaperOption.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestPaperOptionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestPaperOptionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
