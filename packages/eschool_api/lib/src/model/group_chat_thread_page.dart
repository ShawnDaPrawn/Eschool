//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_thread_summary.dart';
import 'package:eschool_api/src/model/group_chat_policy.dart';
import 'package:eschool_api/src/model/group_chat_persona.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_thread_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatThreadPage {
  /// Returns a new [GroupChatThreadPage] instance.
  GroupChatThreadPage({
    required this.items,

    required this.nextBefore,

    required this.policy,

    required this.persona,

    required this.writable,

    required this.maxCharacters,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<GroupChatThreadSummary> items;

  @JsonKey(name: r'next_before', required: true, includeIfNull: true)
  final String? nextBefore;

  @JsonKey(name: r'policy', required: true, includeIfNull: false)
  final GroupChatPolicy policy;

  @JsonKey(
    name: r'persona',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GroupChatPersona.unknownDefaultOpenApi,
  )
  final GroupChatPersona persona;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  // minimum: 1
  // maximum: 10000
  @JsonKey(name: r'max_characters', required: true, includeIfNull: false)
  final int maxCharacters;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatThreadPage &&
          other.items == items &&
          other.nextBefore == nextBefore &&
          other.policy == policy &&
          other.persona == persona &&
          other.writable == writable &&
          other.maxCharacters == maxCharacters;

  @override
  int get hashCode =>
      items.hashCode +
      (nextBefore == null ? 0 : nextBefore.hashCode) +
      policy.hashCode +
      persona.hashCode +
      writable.hashCode +
      maxCharacters.hashCode;

  factory GroupChatThreadPage.fromJson(Map<String, dynamic> json) =>
      _$GroupChatThreadPageFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatThreadPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
