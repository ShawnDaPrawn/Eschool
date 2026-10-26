//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

enum GroupChatOperationType {
  @JsonValue(r'thread_created')
  threadCreated(r'thread_created'),
  @JsonValue(r'thread_state')
  threadState(r'thread_state'),
  @JsonValue(r'message_sent')
  messageSent(r'message_sent'),
  @JsonValue(r'message_removed')
  messageRemoved(r'message_removed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const GroupChatOperationType(this.value);

  final String value;

  @override
  String toString() => value;
}
