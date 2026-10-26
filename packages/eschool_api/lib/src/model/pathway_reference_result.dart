//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pathway_reference.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pathway_reference_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayReferenceResult {
  /// Returns a new [PathwayReferenceResult] instance.
  PathwayReferenceResult({
    required this.schoolId,

    required this.readerId,

    required this.reference,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'reference', required: true, includeIfNull: false)
  final PathwayReference reference;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayReferenceResult &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.reference == reference;

  @override
  int get hashCode =>
      schoolId.hashCode + readerId.hashCode + reference.hashCode;

  factory PathwayReferenceResult.fromJson(Map<String, dynamic> json) =>
      _$PathwayReferenceResultFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayReferenceResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
