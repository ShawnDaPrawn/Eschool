//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_mark.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_capture_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicCaptureData {
  /// Returns a new [AcademicCaptureData] instance.
  AcademicCaptureData({required this.marks, required this.recomputePending});

  @JsonKey(name: r'marks', required: true, includeIfNull: false)
  final List<AcademicMark> marks;

  @JsonKey(name: r'recompute_pending', required: true, includeIfNull: false)
  final bool recomputePending;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicCaptureData &&
          other.marks == marks &&
          other.recomputePending == recomputePending;

  @override
  int get hashCode => marks.hashCode + recomputePending.hashCode;

  factory AcademicCaptureData.fromJson(Map<String, dynamic> json) =>
      _$AcademicCaptureDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicCaptureDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
