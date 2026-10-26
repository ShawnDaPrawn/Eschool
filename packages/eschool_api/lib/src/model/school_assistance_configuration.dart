//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceConfiguration {
  /// Returns a new [SchoolAssistanceConfiguration] instance.
  SchoolAssistanceConfiguration({
    required this.canParent,

    required this.canOffice,

    required this.languages,

    required this.answerAvailable,

    required this.translationAvailable,

    required this.schoolId,

    required this.readerId,
  });

  @JsonKey(name: r'can_parent', required: true, includeIfNull: false)
  final bool canParent;

  @JsonKey(name: r'can_office', required: true, includeIfNull: false)
  final bool canOffice;

  @JsonKey(name: r'languages', required: true, includeIfNull: false)
  final List<String> languages;

  @JsonKey(name: r'answer_available', required: true, includeIfNull: false)
  final bool answerAvailable;

  @JsonKey(name: r'translation_available', required: true, includeIfNull: false)
  final bool translationAvailable;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceConfiguration &&
          other.canParent == canParent &&
          other.canOffice == canOffice &&
          other.languages == languages &&
          other.answerAvailable == answerAvailable &&
          other.translationAvailable == translationAvailable &&
          other.schoolId == schoolId &&
          other.readerId == readerId;

  @override
  int get hashCode =>
      canParent.hashCode +
      canOffice.hashCode +
      languages.hashCode +
      answerAvailable.hashCode +
      translationAvailable.hashCode +
      schoolId.hashCode +
      readerId.hashCode;

  factory SchoolAssistanceConfiguration.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
