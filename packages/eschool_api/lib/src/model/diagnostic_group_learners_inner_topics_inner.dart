//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diagnostic_band.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diagnostic_group_learners_inner_topics_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiagnosticGroupLearnersInnerTopicsInner {
  /// Returns a new [DiagnosticGroupLearnersInnerTopicsInner] instance.
  DiagnosticGroupLearnersInnerTopicsInner({
    required this.libraryTopicId,

    required this.band,
  });

  // minimum: 1
  @JsonKey(name: r'library_topic_id', required: true, includeIfNull: false)
  final int libraryTopicId;

  @JsonKey(
    name: r'band',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DiagnosticBand.unknownDefaultOpenApi,
  )
  final DiagnosticBand band;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiagnosticGroupLearnersInnerTopicsInner &&
          other.libraryTopicId == libraryTopicId &&
          other.band == band;

  @override
  int get hashCode => libraryTopicId.hashCode + band.hashCode;

  factory DiagnosticGroupLearnersInnerTopicsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$DiagnosticGroupLearnersInnerTopicsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$DiagnosticGroupLearnersInnerTopicsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
