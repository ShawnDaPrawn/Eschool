//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_bank_choices.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeBankChoices {
  /// Returns a new [PracticeBankChoices] instance.
  PracticeBankChoices({
    required this.groupId,

    required this.topics,

    required this.sources,

    required this.writable,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<PracticeChoice> topics;

  @JsonKey(name: r'sources', required: true, includeIfNull: false)
  final List<PracticeChoice> sources;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeBankChoices &&
          other.groupId == groupId &&
          other.topics == topics &&
          other.sources == sources &&
          other.writable == writable;

  @override
  int get hashCode =>
      groupId.hashCode + topics.hashCode + sources.hashCode + writable.hashCode;

  factory PracticeBankChoices.fromJson(Map<String, dynamic> json) =>
      _$PracticeBankChoicesFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeBankChoicesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
