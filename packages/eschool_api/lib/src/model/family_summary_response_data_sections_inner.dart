//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_summary_response_data_sections_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilySummaryResponseDataSectionsInner {
  /// Returns a new [FamilySummaryResponseDataSectionsInner] instance.
  FamilySummaryResponseDataSectionsInner({
    required this.schoolId,

    required this.schoolName,

    required this.text,

    required this.wording,

    required this.unreadMessages,

    required this.privateUpdates,
  });

  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  @JsonKey(name: r'school_name', required: true, includeIfNull: false)
  final String schoolName;

  /// Plain text; private updates are only counted, never described.
  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @JsonKey(
    name: r'wording',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        FamilySummaryResponseDataSectionsInnerWordingEnum.unknownDefaultOpenApi,
  )
  final FamilySummaryResponseDataSectionsInnerWordingEnum wording;

  // minimum: 0
  @JsonKey(name: r'unread_messages', required: true, includeIfNull: false)
  final int unreadMessages;

  // minimum: 0
  @JsonKey(name: r'private_updates', required: true, includeIfNull: false)
  final int privateUpdates;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilySummaryResponseDataSectionsInner &&
          other.schoolId == schoolId &&
          other.schoolName == schoolName &&
          other.text == text &&
          other.wording == wording &&
          other.unreadMessages == unreadMessages &&
          other.privateUpdates == privateUpdates;

  @override
  int get hashCode =>
      schoolId.hashCode +
      schoolName.hashCode +
      text.hashCode +
      wording.hashCode +
      unreadMessages.hashCode +
      privateUpdates.hashCode;

  factory FamilySummaryResponseDataSectionsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$FamilySummaryResponseDataSectionsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$FamilySummaryResponseDataSectionsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FamilySummaryResponseDataSectionsInnerWordingEnum {
  @JsonValue(r'ai')
  ai(r'ai'),
  @JsonValue(r'template')
  template(r'template'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const FamilySummaryResponseDataSectionsInnerWordingEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
