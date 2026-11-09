//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_topic_option.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestTopicOption {
  /// Returns a new [PracticeTestTopicOption] instance.
  PracticeTestTopicOption({
    required this.id,

    required this.name,

    required this.source_,

    required this.available,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeTestTopicOptionSource_Enum.unknownDefaultOpenApi,
  )
  final PracticeTestTopicOptionSource_Enum source_;

  // minimum: 0
  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final int available;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestTopicOption &&
          other.id == id &&
          other.name == name &&
          other.source_ == source_ &&
          other.available == available;

  @override
  int get hashCode =>
      id.hashCode + name.hashCode + source_.hashCode + available.hashCode;

  factory PracticeTestTopicOption.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestTopicOptionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestTopicOptionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeTestTopicOptionSource_Enum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'library')
  library_(r'library'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestTopicOptionSource_Enum(this.value);

  final String value;

  @override
  String toString() => value;
}
