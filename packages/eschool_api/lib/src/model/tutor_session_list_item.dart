//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_session_list_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorSessionListItem {
  /// Returns a new [TutorSessionListItem] instance.
  TutorSessionListItem({
    required this.id,

    required this.subjectId,

    required this.state,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TutorSessionListItemStateEnum.unknownDefaultOpenApi,
  )
  final TutorSessionListItemStateEnum state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorSessionListItem &&
          other.id == id &&
          other.subjectId == subjectId &&
          other.state == state;

  @override
  int get hashCode => id.hashCode + subjectId.hashCode + state.hashCode;

  factory TutorSessionListItem.fromJson(Map<String, dynamic> json) =>
      _$TutorSessionListItemFromJson(json);

  Map<String, dynamic> toJson() => _$TutorSessionListItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TutorSessionListItemStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorSessionListItemStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
