//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_publish_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumPublishWrite {
  /// Returns a new [CurriculumPublishWrite] instance.
  CurriculumPublishWrite({required this.revision});

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumPublishWrite && other.revision == revision;

  @override
  int get hashCode => revision.hashCode;

  factory CurriculumPublishWrite.fromJson(Map<String, dynamic> json) =>
      _$CurriculumPublishWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumPublishWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
