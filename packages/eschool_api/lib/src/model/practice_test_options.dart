//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_paper_option.dart';
import 'package:eschool_api/src/model/practice_test_options_limits.dart';
import 'package:eschool_api/src/model/practice_test_topic_option.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_options.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestOptions {
  /// Returns a new [PracticeTestOptions] instance.
  PracticeTestOptions({
    required this.groupId,

    required this.topics,

    required this.papers,

    required this.weightings,

    required this.limits,

    required this.writable,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<PracticeTestTopicOption> topics;

  @JsonKey(name: r'papers', required: true, includeIfNull: false)
  final List<PracticeTestPaperOption> papers;

  @JsonKey(
    name: r'weightings',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeTestOptionsWeightingsEnum.unknownDefaultOpenApi,
  )
  final List<PracticeTestOptionsWeightingsEnum> weightings;

  @JsonKey(name: r'limits', required: true, includeIfNull: false)
  final PracticeTestOptionsLimits limits;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestOptions &&
          other.groupId == groupId &&
          other.topics == topics &&
          other.papers == papers &&
          other.weightings == weightings &&
          other.limits == limits &&
          other.writable == writable;

  @override
  int get hashCode =>
      groupId.hashCode +
      topics.hashCode +
      papers.hashCode +
      weightings.hashCode +
      limits.hashCode +
      writable.hashCode;

  factory PracticeTestOptions.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestOptionsFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestOptionsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeTestOptionsWeightingsEnum {
  @JsonValue(r'even')
  even(r'even'),
  @JsonValue(r'past_papers')
  pastPapers(r'past_papers'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestOptionsWeightingsEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
