//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_released_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_released_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReleasedData {
  /// Returns a new [AcademicReleasedData] instance.
  AcademicReleasedData({required this.learnerId, required this.results});

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'results', required: true, includeIfNull: false)
  final List<AcademicReleasedResult> results;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReleasedData &&
          other.learnerId == learnerId &&
          other.results == results;

  @override
  int get hashCode => learnerId.hashCode + results.hashCode;

  factory AcademicReleasedData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReleasedDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReleasedDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
