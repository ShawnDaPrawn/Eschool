//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

enum GroupChatThreadKind {
  @JsonValue(r'announcement')
  announcement(r'announcement'),
  @JsonValue(r'question')
  question(r'question'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const GroupChatThreadKind(this.value);

  final String value;

  @override
  String toString() => value;
}
