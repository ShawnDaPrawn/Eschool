//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_contribute_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryContributeInput {
  /// Returns a new [SharedLibraryContributeInput] instance.
  SharedLibraryContributeInput({
    required this.practiceItemId,

    required this.libraryTopicId,

    required this.title,

    required this.credit,

    required this.rightsConfirmed,

    required this.licenceAccepted,
  });

  // minimum: 1
  @JsonKey(name: r'practice_item_id', required: true, includeIfNull: false)
  final int practiceItemId;

  // minimum: 1
  @JsonKey(name: r'library_topic_id', required: true, includeIfNull: false)
  final int libraryTopicId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(
    name: r'credit',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SharedLibraryContributeInputCreditEnum.unknownDefaultOpenApi,
  )
  final SharedLibraryContributeInputCreditEnum credit;

  /// The teacher wrote it or has the right to share it.
  @JsonKey(name: r'rights_confirmed', required: true, includeIfNull: false)
  final bool rightsConfirmed;

  /// The teacher shares it under the shared library licence (CC BY 4.0 pending the owner's decision).
  @JsonKey(name: r'licence_accepted', required: true, includeIfNull: false)
  final bool licenceAccepted;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryContributeInput &&
          other.practiceItemId == practiceItemId &&
          other.libraryTopicId == libraryTopicId &&
          other.title == title &&
          other.credit == credit &&
          other.rightsConfirmed == rightsConfirmed &&
          other.licenceAccepted == licenceAccepted;

  @override
  int get hashCode =>
      practiceItemId.hashCode +
      libraryTopicId.hashCode +
      title.hashCode +
      credit.hashCode +
      rightsConfirmed.hashCode +
      licenceAccepted.hashCode;

  factory SharedLibraryContributeInput.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryContributeInputFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryContributeInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SharedLibraryContributeInputCreditEnum {
  @JsonValue(r'named')
  named(r'named'),
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'anonymous')
  anonymous(r'anonymous'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SharedLibraryContributeInputCreditEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
